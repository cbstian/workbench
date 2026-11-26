{{--
    Progress bar of closed tasks. Variables: $closed, $total.
--}}
@php($percent = $total > 0 ? (int) round($closed * 100 / $total) : 0)

<div class="space-y-1.5">
    <div class="flex items-baseline justify-between text-xs">
        <span class="text-ink-muted">{{ $closed }} de {{ $total }} {{ $total === 1 ? 'tarea cerrada' : 'tareas cerradas' }}</span>
        <span class="font-semibold text-ink tabular-nums">{{ $percent }}%</span>
    </div>
    <div class="h-1.5 overflow-hidden rounded-full bg-hover" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"
         aria-label="Avance">
        <div class="h-full rounded-full bg-accent transition-[width] duration-500" style="width: {{ $percent }}%"></div>
    </div>
</div>
