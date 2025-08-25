<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
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

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

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
                ->required(),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('status')
                ->options([
                    'Present' => 'Present',
                    'Absent' => 'Absent',
                    'Late' => 'Late',
                    'On Leave' => 'On Leave',
                ])
                ->required(),

            TimePicker::make('clock_in')
                ->label('Clock In')
                ->default(null),

            TimePicker::make('clock_out')
                ->label('Clock Out')
                ->default(null),

            Textarea::make('remarks')
                ->label('Remarks')
                ->nullable(),

            Hidden::make('recorded_by')
                ->default(fn () => auth()->id())
                ->dehydrated(),
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

                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->date('F j, Y'),

                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->searchable(),

                TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->sortable(),

                TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->sortable(),
                TextColumn::make('employee.company.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('recorded_by')
                    ->label('Recorded By')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->recorder->name ?? 'Unknown'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Present' => 'Present',
                        'Absent' => 'Absent',
                        'Late' => 'Late',
                        'On Leave' => 'On Leave',
                    ]),
                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('employee.company', 'company_name') // ✅ Navigate through Employee to Company
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
