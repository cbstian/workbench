<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Mentions inside plain-text comments (RN-10).
 *
 * People type `@Nombre Apellido`; the body is stored with `@[user-uuid]`
 * tokens so a mention survives a rename and points at one person. Only
 * users who can see the commented element are recognised.
 */
class Mentions
{
    public const string TOKEN_PATTERN = '/@\[([0-9a-fA-F-]{36})\]/';

    /**
     * Turn typed `@Name` mentions into tokens.
     *
     * @param  iterable<int, User>  $mentionable
     * @return array{body: string, user_ids: list<int>}
     */
    public function canonicalize(string $body, iterable $mentionable): array
    {
        $userIds = [];

        $users = Collection::make($mentionable)
            ->filter(fn (User $user): bool => filled($user->name))
            ->sortByDesc(fn (User $user): int => mb_strlen($user->name));

        foreach ($users as $user) {
            $pattern = '/(?<![\p{L}\p{N}_])@'.preg_quote($user->name, '/').'(?![\p{L}\p{N}_])/iu';

            $body = preg_replace_callback($pattern, function () use ($user, &$userIds): string {
                $userIds[] = $user->id;

                return '@['.$user->uuid.']';
            }, $body) ?? $body;
        }

        foreach ($this->uuids($body) as $uuid) {
            $known = $users->firstWhere('uuid', $uuid);

            if ($known !== null) {
                $userIds[] = $known->id;
            }
        }

        return ['body' => $body, 'user_ids' => array_values(array_unique($userIds))];
    }

    /**
     * Uuids of the users mentioned in a stored body.
     *
     * @return list<string>
     */
    public function uuids(string $body): array
    {
        preg_match_all(self::TOKEN_PATTERN, $body, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    /**
     * Current names of the users mentioned in several bodies, keyed by uuid.
     *
     * @param  iterable<int, string>  $bodies
     * @return array<string, string>
     */
    public function names(iterable $bodies): array
    {
        $uuids = Collection::make($bodies)->flatMap(fn (string $body): array => $this->uuids($body))->unique()->values();

        if ($uuids->isEmpty()) {
            return [];
        }

        return User::withTrashed()->whereIn('uuid', $uuids)->pluck('name', 'uuid')
            ->mapWithKeys(fn (string $name, string $uuid): array => [strtolower($uuid) => $name])
            ->all();
    }

    /**
     * The body as typed text again, to edit it.
     *
     * @param  array<string, string>|null  $names
     */
    public function toPlain(string $body, ?array $names = null): string
    {
        $names ??= $this->names([$body]);

        return preg_replace_callback(self::TOKEN_PATTERN, fn (array $match): string => '@'.($names[strtolower($match[1])] ?? 'usuario'), $body) ?? $body;
    }

    /**
     * Safe HTML: escaped text, mentions as chips and links made clickable.
     * Line breaks are kept by the `whitespace-pre-line` of the container.
     *
     * @param  array<string, string>|null  $names
     */
    public function render(string $body, ?array $names = null): HtmlString
    {
        $names ??= $this->names([$body]);
        $html = e($body);

        $html = preg_replace_callback(
            '~\bhttps?://[^\s<>"\']+~iu',
            function (array $match): string {
                $url = rtrim($match[0], '.,;:!?)');
                $tail = substr($match[0], strlen($url));

                return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer nofollow" class="text-accent underline underline-offset-2 hover:no-underline">'.$url.'</a>'.$tail;
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            self::TOKEN_PATTERN,
            fn (array $match): string => '<span class="rounded bg-hover px-1 font-medium text-ink">@'.e($names[strtolower($match[1])] ?? 'usuario').'</span>',
            $html,
        ) ?? $html;

        return new HtmlString($html);
    }

    /**
     * One-line excerpt for notifications.
     */
    public function excerpt(string $body, int $limit = 140): string
    {
        return Str::limit(Str::squish($this->toPlain($body)), $limit);
    }
}
