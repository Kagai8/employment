<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeSavingsPlanResource\Pages;
use App\Filament\Resources\EmployeeSavingsPlanResource\RelationManagers\SavingsTransactionsRelationManager;
use App\Models\Employee;
use App\Models\EmployeeSavingsPlan;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction; // ✅ Excel export

class EmployeeSavingsPlanResource extends Resource
{
    protected static ?string $model = EmployeeSavingsPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Savings Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Savings Plan Setup')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn () => Employee::all()->mapWithKeys(fn ($employee) => [
                                $employee->id => "{$employee->first_name} {$employee->last_name}",
                            ]))
                            ->searchable()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('company_id', Employee::find($state)?->company_id)
                            ),

                        Forms\Components\Hidden::make('company_id')->dehydrated(),

                        TextInput::make('total_amount')
                            ->label('Total Savings Amount')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('This field is automatically updated after every salary deduction.'),

                        Select::make('status')
                            ->label('Plan Status')
                            ->options([
                                'active' => 'Active (Deductions ON)',
                                'paused' => 'Paused (Deductions OFF)',
                                'withdrawn' => 'Withdrawn (Account Closed)',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(2),
            ]);
    }

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

                TextColumn::make('company.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Plan Status')
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'withdrawn' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_amount')
                    ->label('Total Amount Saved')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'withdrawn' => 'Withdrawn',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('View / Edit'),

                    Action::make('generate_savings_summary')
                        ->label('Print Savings Record')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->url(fn (EmployeeSavingsPlan $record) => route('savings.summary', $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn (EmployeeSavingsPlan $record) => $record->total_amount > 0),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected Plans'), // ✅ Excel export
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'company']);
    }

    // ✅ THIS enables installment view per plan
    public static function getRelations(): array
    {
        return [
            SavingsTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeSavingsPlans::route('/'),
            'edit' => Pages\EditEmployeeSavingsPlan::route('/{record}/edit'),
        ];
    }
}
