<?php

namespace App\Filament\Resources\AttendanceResource\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalGroupAttendanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $presentEmployees = Attendance::whereDate('date', $today)->count();

        return [
            Stat::make('Employees Present Today', $presentEmployees)
                ->description('Employees marked present today across all companies')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
