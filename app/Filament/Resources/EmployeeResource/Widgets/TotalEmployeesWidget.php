<?php

namespace App\Filament\Resources\EmployeeResource\Widgets;

use App\Models\Employee;
use Filament\Forms\Components\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalEmployeesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Employees', Employee::count())
                ->icon('heroicon-o-users')
                ->color('success'),
        ];
    }

    protected function getColumns(): int
    {
        return 3; // Adjust the number of columns to control layout
    }
}
