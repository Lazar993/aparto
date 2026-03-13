<x-filament-panels::page class="fi-dashboard-page">
    @php
        $widgetData = [
            ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
            ...$this->getWidgetData(),
        ];
    @endphp

    @if (count($this->getAccountWidgets()))
        <div class="mb-6">
            <x-filament-widgets::widgets
                :columns="1"
                :data="$widgetData"
                :widgets="$this->getAccountWidgets()"
            />
        </div>
    @endif

    @if (method_exists($this, 'filtersForm'))
        {{ $this->filtersForm }}
    @endif

    @if (count($this->getNonAccountWidgets()))
        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :data="$widgetData"
            :widgets="$this->getNonAccountWidgets()"
        />
    @endif
</x-filament-panels::page>
