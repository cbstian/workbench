{{--
    ApexCharts chart, themed with the design tokens (light/dark aware).
    ApexCharts is lazy-loaded only on pages that render this component.

    Props:
    - options: ApexCharts options (https://apexcharts.com/docs/options/). Declare only
               what is specific to the chart; colors, fonts, grid and tooltip come
               from resources/js/modules/chart.js.
    - height:  chart height in pixels (default 280).

    Prepare the options in the Livewire class (a #[Computed] method), not in the view:

        <x-ui.chart :options="$this->projectsPerMonthChart" height="240" />

    The component is wire:ignore'd and keyed by its options, so a Livewire
    re-render with new data recreates the chart instead of morphing its SVG.
--}}
@props([
    'options' => [],
    'height' => 280,
])

@php($options = array_replace_recursive(['chart' => ['height' => (int) $height]], $options))

<div wire:ignore
     wire:key="chart-{{ md5(json_encode($options)) }}"
     x-data="wbChart(@js($options))"
     {{ $attributes->class('relative w-full') }}
     style="min-height: {{ (int) $height }}px">
    <div x-ref="canvas"></div>
</div>
