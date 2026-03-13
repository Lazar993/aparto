<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $view = 'filament.pages.dashboard';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('time_range')
                    ->label('Statistics period')
                    ->options([
                        'last_day' => 'Last day',
                        'last_3_days' => 'Last 3 days',
                        'last_7_days' => 'Last 7 days',
                        'last_month' => 'Last month',
                        'all_time' => 'All time',
                    ])
                    ->default('all_time')
                    ->native(false)
                    ->selectablePlaceholder(false),
            ]);
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getAccountWidgets(): array
    {
        return array_values(array_filter(
            $this->getVisibleWidgets(),
            fn (string | WidgetConfiguration $widget): bool => $this->resolveWidgetClass($widget) === AccountWidget::class,
        ));
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getNonAccountWidgets(): array
    {
        return array_values(array_filter(
            $this->getVisibleWidgets(),
            fn (string | WidgetConfiguration $widget): bool => $this->resolveWidgetClass($widget) !== AccountWidget::class,
        ));
    }

    /**
     * @param  class-string<Widget> | WidgetConfiguration  $widget
     * @return class-string<Widget>
     */
    protected function resolveWidgetClass(string | WidgetConfiguration $widget): string
    {
        if ($widget instanceof WidgetConfiguration) {
            return $widget->widget;
        }

        return $widget;
    }
}
