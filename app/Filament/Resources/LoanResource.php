<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Loan Details')
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(fn () => Employee::all()->mapWithKeys(fn ($employee) => [
                            $employee->id => $employee->first_name . ' ' . $employee->last_name,
                        ]))
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $employee = Employee::find($state);
                                $set('company_id', $employee?->company_id); // Set directly, handles null
                            } else {
                                $set('company_id', null); // clear company_id if employee is unselected.
                            }
                        }),
                    Select::make('company_id')
                        ->label('Company Name')
                        ->relationship('company', 'company_name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('amount')
                        ->label('Loan Amount')
                        ->numeric()
                        ->required(),

                    TextInput::make('interest')
                        ->label('Interest Rate (%)')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('balance')
                        ->label('Remaining Balance')
                        ->numeric()
                        ->disabled(),

                    DatePicker::make('start_date')
                        ->label('Date Issued')
                        ->default(now()),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending'),
                ])->columns(2),


                Section::make('Installment Info')
                    ->schema([
                        Repeater::make('installments')
                            ->label('Loan Installments')
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Installment Amount')
                                    ->numeric()
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? json_encode($state) : $state)

                    ]),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.last_name')
                    ->label('Second Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')->label('Loan Amount')->money('KES'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->color(fn ($record) => match ($record->status) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('company.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Date Issued')
                    ->sortable()
                    ->date('F j, Y')
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->searchable()
                    ->preload()
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('generate_loan_summary')
                    ->label('Loan Summary Generate')
                    ->icon('heroicon-o-document')
                    ->color('primary')
                    ->action(fn ($record) => redirect(route('loan.summary', $record->id)))
                    ->visible(fn ($record) => $record->installmentRecords()->count() > 0), // Fix: Use installments() query

                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['balance'] = $data['amount']; // Set balance to initial loan amount
    return $data;
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
