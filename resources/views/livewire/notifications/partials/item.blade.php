{{--
    One inbox alert. Variables: $notification (DatabaseNotification), $compact (bool).
--}}
@php
    $data = $notification->data;
    $isUnread = $notification->read_at === null;
@endphp

<a href="{{ route('notifications.open', $notification->id) }}"
   @class([
       'group flex min-w-0 flex-1 gap-3 rounded-md px-2 py-2 transition-colors hover:bg-hover',
   ])>
    <span class="relative shrink-0">
        <x-app.avatar :name="$data['actor_name'] ?? '?'" />
        @if ($isUnread)
            <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-elevated bg-accent" aria-label="Sin leer"></span>
        @endif
    </span>
    <span class="min-w-0 flex-1">
        <span @class(['block text-sm', 'text-ink' => $isUnread, 'text-ink-muted' => ! $isUnread])>
            <span class="font-medium">{{ $data['actor_name'] ?? 'Alguien' }}</span>
            {{ \App\Notifications\Comments\CommentNotification::actionForKind($data['kind'] ?? '') }}
            <span class="font-medium">{{ $data['title'] ?? '' }}</span>
        </span>
        @if (filled($data['excerpt'] ?? null))
            <span @class(['mt-0.5 block text-sm text-ink-muted', 'truncate' => $compact, 'line-clamp-2' => ! $compact])>{{ $data['excerpt'] }}</span>
        @endif
        <span class="mt-0.5 block text-xs text-ink-subtle">
            {{ ($data['commentable_type'] ?? '') === 'page' ? 'Página' : 'Tarea' }}
            @if (filled($data['context'] ?? null))
                · {{ $data['context'] }}
            @endif
            · <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
        </span>
    </span>
</a>
