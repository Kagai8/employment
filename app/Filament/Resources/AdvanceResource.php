<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceResource\Pages;
use App\Filament\Resources\AdvanceResource\RelationManagers;
use App\Models\Advance;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AdvanceResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Select::make('employee_id')
                ->label('Employee')
                ->options(fn () => Employee::all()->mapWithKeys(fn ($employee) => [
                    $employee->id => $employee->first_name . ' ' . $employee->last_name
                    ]))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $employee = Employee::find($state);
                            $set('company_id', $employee?->company_id ?? '');
                        }
                    }),

            Hidden::make('company_id')
                    ->dehydrated() // Ensure it's saved to the database
                    ->default(fn (Get $get) => $get('company_id')),

            TextInput::make('amount')
                ->numeric()
                ->required(),

            TextInput::make('remaining_balance')
                ->numeric()
                ->disabled(), // Cannot be manually edited

            DatePicker::make('request_date')
                ->required(),

            DatePicker::make('expected_deduction_date')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                ->label('Employee')
                ->searchable()
                ->sortable(),

                TextColumn::make('employee.last_name')
                ->label('Employee')
                ->searchable()
                ->sortable(),

            TextColumn::make('company.company_name')
                ->label('Company')
                ->sortable(),

            TextColumn::make('amount')
                ->label('Advance Amount')
                ->sortable()
                ->color('warning'),

            TextColumn::make('remaining_balance')
                ->label('Remaining Balance')
                ->sortable()
                ->color(fn ($record) => $record->remaining_balance > 0 ? 'danger' : 'gray'),

            // ✅ Date of Advance (Correct Format for Filament v3)
            TextColumn::make('created_at')
                ->label('Date Issued')
                ->sortable()
                ->date('F j, Y'),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvances::route('/'),
            'create' => Pages\CreateAdvance::route('/create'),
            'edit' => Pages\EditAdvance::route('/{record}/edit'),
        ];
    }
}
