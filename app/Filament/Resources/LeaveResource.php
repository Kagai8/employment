<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Select::make('employee_id')
                ->label('Employee')
                ->options(fn () => Employee::all()->mapWithKeys(fn ($employee) => [
                    $employee->id => $employee->first_name . ' ' . $employee->last_name
                    ]))
                ->searchable()
                ->required(),



            Forms\Components\Select::make('leave_type')
                ->label('Leave Type')
                ->options([
                    'Annual' => 'Annual Leave',
                    'Sick' => 'Sick Leave',
                    'Maternity' => 'Maternity Leave',
                    'Paternity' => 'Paternity Leave',
                ])
                ->required(),

            Forms\Components\DatePicker::make('start_date')
                ->label('Start Date')
                ->required(),

            Forms\Components\DatePicker::make('end_date')
                ->label('End Date')
                ->reactive()
                ->required()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $start = Carbon::parse($get('start_date'));
                    $end = Carbon::parse($state);

                    if ($start && $end) {
                        // Count only weekdays (Monday - Friday)
                        $period = CarbonPeriod::create($start, $end);
                        $daysTaken = collect($period)->filter(fn ($date) => !in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]))->count();
                        $set('days_taken', $daysTaken);
                    } else {
                        $set('days_taken', 0);
                    }
                }),

                TextInput::make('created_by')
                    ->label('User ID')
                    ->default(fn () => auth()->user()->id) // Fetch authenticated user name
                    ->disabled() // Prevent editing
                    ->dehydrated(), // Ensure it is saved in the database

            // Placeholder to display calculated days
            Forms\Components\Placeholder::make('days_placeholder')
                ->label('Total Days Taken')
                ->content(fn (Get $get) => $get('days_taken') . ' Days'),

            // Hidden field to store actual leave days in the database
            Forms\Components\Hidden::make('days_taken')
                ->default(0),

            Forms\Components\Textarea::make('reason')
                ->label('Reason')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                ->label('First Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.last_name')
                ->label('Last Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.company.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('d M, Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d M, Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('days_taken')
                    ->label('Days Taken')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded On')
                    ->date('F j, Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('leave_type')
                    ->label('Filter by Leave Type')
                    ->options([
                        'Annual' => 'Annual Leave',
                        'Sick' => 'Sick Leave',
                        'Maternity' => 'Maternity Leave',
                        'Paternity' => 'Paternity Leave',
                    ])
                    ->searchable()
                    ->preload()
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
