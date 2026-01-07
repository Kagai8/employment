<?php

namespace App\Filament\Resources\EmployeeSavingsPlanResource\Pages;

use App\Filament\Resources\EmployeeSavingsPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeSavingsPlan extends EditRecord
{
    protected static string $resource = EmployeeSavingsPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
