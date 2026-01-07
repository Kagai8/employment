<?php

namespace App\Filament\Resources\EmployeeSavingsPlanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SavingsTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'savingsTransactions';

    protected static ?string $title = 'Savings Installments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
