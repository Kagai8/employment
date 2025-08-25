<?php

namespace App\Filament\Resources\SalaryResource\Widgets;

use App\Models\Salary;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalaryChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';
    protected static ?int $sort = 2;

    protected function getFilters(): array
    {
        return [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'monthly';
        $data = [];

        if ($filter === 'monthly') {
            $data = $this->getMonthlyData();
        } else {
            $data = $this->getYearlyData();
        }

        return [
            'datasets' => [
                [
                    'label' => $filter === 'monthly' ? 'Monthly Payroll' : 'Yearly Payroll',
                    'data' => $data['amounts'],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    private function getMonthlyData()
    {
        $salaries = Salary::selectRaw("SUM(net_salary) as total, strftime('%m', created_at) as month")
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $salaries->pluck('month')->map(fn ($m) => Carbon::create()->month((int) $m)->format('F'))->toArray(),
            'amounts' => $salaries->pluck('total')->toArray(),
        ];
    }

    private function getYearlyData()
    {
        $salaries = Salary::selectRaw("SUM(net_salary) as total, strftime('%Y', created_at) as year")
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return [
            'labels' => $salaries->pluck('year')->toArray(),
            'amounts' => $salaries->pluck('total')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getColumnSpan(): int | string | array
    {
        return 'full'; // Ensures full-width in Filament v3
    }
}
