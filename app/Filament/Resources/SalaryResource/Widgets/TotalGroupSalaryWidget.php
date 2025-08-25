<?php

namespace App\Filament\Resources\SalaryResource\Widgets;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Salary;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalGroupSalaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPayroll = Salary::whereMonth('created_at', Carbon::now()->month)
                              ->whereYear('created_at', Carbon::now()->year)
                              ->sum('net_salary');
                              $today = Carbon::today();
                              $presentEmployees = Attendance::whereDate('date', $today)->count();

        return [

            Stat::make('Total Employees', Employee::count())
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Total Payroll (Current Month)', 'KES ' . number_format($totalPayroll, 2))
                ->description('Total salaries paid across all companies')
                ->icon('heroicon-o-banknotes'),

                Stat::make('Total Companies', Company::count())
                ->description('Companies under this system')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    protected function getColumns(): int
    {
        return 3; // Forces a horizontal layout
    }
}
