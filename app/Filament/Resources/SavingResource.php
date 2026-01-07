<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingResource\Pages;
use App\Models\Saving; // The Ledger/Transaction Model
use Filament\Forms\Form; // Still needed for the class declaration, even if method is removed
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction; // ✅ Import the Bulk Export Action

class SavingResource extends Resource
{
    protected static ?string $model = Saving::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Savings Per Month';



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                // ✅ NEW COLUMN: Company Name
                TextColumn::make('company.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salary.created_at')
                    ->label('Payroll Date')
                    ->date('F j, Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Deducted Amount')
                    ->money('KES')
                    ->weight('medium'),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // No individual actions (View, Edit, Delete) for audit data
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // ✅ BULK ACTION: Export selected records to Excel/CSV
                    ExportBulkAction::make()
                        ->label('Export Selected Savings Data'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        // Your streamlined list of pages
        return [
            'index' => Pages\ListSavings::route('/'),
        ];
    }
}
