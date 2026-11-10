{{-- Flash status from Fortify (e.g. "reset link sent", "password reset"). --}}
@if (session('status'))
    <div {{ $attributes->class('mb-6 rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink') }} role="status">
        {{ session('status') }}
    </div>
@endif
