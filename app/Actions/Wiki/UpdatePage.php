<?php

namespace App\Actions\Wiki;

use App\Enums\PageActivityEvent;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\User;
use App\Models\WikiLabel;
use Illuminate\Support\Facades\DB;

/**
 * Saves a page and records each change in its history (RN-09): title, icon
 * and visibility as `updated`; the body as `body_updated` with its previous
 * and new length, not its content (no versions, P-15); the parent as `moved`;
 * labels one by one.
 */
class UpdatePage
{
    /**
     * @var list<string>
     */
    private const array DATA_FIELDS = ['title', 'icon', 'is_visible_to_client'];

    /**
     * @param  array{title: string, icon: string|null, body: string|null, is_visible_to_client: bool, parent_id: int|null}  $attributes
     * @param  list<int>  $labelIds
     */
    public function handle(Page $page, User $actor, array $attributes, array $labelIds): void
    {
        DB::transaction(function () use ($page, $actor, $attributes, $labelIds): void {
            $page->fill([...$attributes, 'updated_by_id' => $actor->id]);

            $changes = [];

            foreach (self::DATA_FIELDS as $field) {
                if ($page->isDirty($field)) {
                    $changes[$field] = ['old' => $page->getOriginal($field), 'new' => $page->getAttribute($field)];
                }
            }

            $bodyChanged = $page->isDirty('body');
            $oldLength = mb_strlen((string) $page->getOriginal('body'));
            $moved = $page->isDirty('parent_id');

            $page->save();

            if ($changes !== []) {
                PageActivity::record($page, $actor, PageActivityEvent::Updated, $changes);
            }

            if ($bodyChanged) {
                PageActivity::record($page, $actor, PageActivityEvent::BodyUpdated, metadata: [
                    'old_length' => $oldLength,
                    'new_length' => mb_strlen((string) $page->body),
                ]);
            }

            if ($moved) {
                PageActivity::record($page, $actor, PageActivityEvent::Moved, metadata: [
                    'parent_id' => $page->parent_id,
                    'parent_title' => $page->parent?->title,
                ]);
            }

            $this->syncLabels($page, $actor, $labelIds);
        });
    }

    /**
     * @param  list<int>  $labelIds
     */
    private function syncLabels(Page $page, User $actor, array $labelIds): void
    {
        $current = $page->labels()->pluck('wiki_labels.id')->map(fn ($id): int => (int) $id)->all();
        $added = array_values(array_diff($labelIds, $current));
        $removed = array_values(array_diff($current, $labelIds));

        if ($removed !== []) {
            $page->labels()->detach($removed);
        }

        if ($added !== []) {
            $page->labels()->attach($added);
        }

        $names = WikiLabel::withTrashed()->whereKey([...$added, ...$removed])->pluck('name', 'id');

        foreach ($added as $labelId) {
            PageActivity::record($page, $actor, PageActivityEvent::LabelAdded, metadata: ['label_id' => $labelId, 'label_name' => $names[$labelId] ?? null]);
        }

        foreach ($removed as $labelId) {
            PageActivity::record($page, $actor, PageActivityEvent::LabelRemoved, metadata: ['label_id' => $labelId, 'label_name' => $names[$labelId] ?? null]);
        }
    }
}
