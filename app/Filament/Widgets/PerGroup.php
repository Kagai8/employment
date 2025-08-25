<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerGroup extends BaseWidget
{
    protected function getStats(): array
    {
        // Get the first three companies
        $companies = Company::limit(3)->get();

        // Define colors for each company (cycle through if there are more than 3)
        $colors = ['primary', 'success', 'warning', 'danger', 'info'];

        $cards = [];

        foreach ($companies as $index => $company) {
            $employeeCount = Employee::where('company_id', $company->id)->count();

            $cards[] = Stat::make("Employees in {$company->company_name}", $employeeCount)
                ->description('Total Employees')
                ->color($colors[$index % count($colors)]); // Cycle through colors
        }

        return $cards;
    }
}
