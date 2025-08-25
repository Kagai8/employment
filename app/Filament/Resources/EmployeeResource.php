<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Salary;
use Doctrine\DBAL\Schema\Schema as SchemaSchema;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\ViewAction as ActionsViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                ->schema([
                    TextInput::make('first_name')->required(),
                    TextInput::make('last_name')->required(),
                    TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('phone'),
                    TextInput::make('position')->required(),
                    TextInput::make('department')->required(),
                    Select::make('contract_type')
                        ->options([
                            'full-time' => 'Full-Time',
                            'part-time' => 'Part-Time',
                            'contract' => 'Contract',
                        ])
                        ->required(),
                    Select::make('company_id')
                        ->label('Company Name')
                        ->relationship('company', 'company_name')
                        ->searchable()
                        ->preload()
                        ->required(),
                        TextInput::make('sha_no')->required()->unique(ignoreRecord: true),
                        TextInput::make('nssf_no')->required()->unique(ignoreRecord: true),
                        TextInput::make('kra_no')->required()->unique(ignoreRecord: true),
                    TextInput::make('basic_salary')
                        ->numeric()
                        ->required(),

                ])->columns(3),

            Section::make('Bank Details')
                ->schema([
                    TextInput::make('bank_name'),
                    TextInput::make('bank_account_number'),
                    TextInput::make('bank_branch'),
                ])->columns(3),

            Section::make('Employee Documents')
                ->schema([
                    FileUpload::make('id_document')
                        ->label('ID Document')
                        ->directory('employees/id_docs'),
                    FileUpload::make('nssf_document')
                        ->label('NSSF Document')
                        ->directory('employees/nssf'),
                    FileUpload::make('nhif_document')
                        ->label('SHA Document')
                        ->directory('employees/nhif'),
                    FileUpload::make('passport_photo')
                        ->label('Passport Photo')
                        ->image()
                        ->directory('employees/photos'),
                    FileUpload::make('birth_certificate')
                        ->label('Birth Certificate')
                        ->directory('employees/birth_certificates'),
                ])->collapsible()->columns(3), // This makes the section collapsible for better UI
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('first_name')
                ->sortable()
                ->searchable(),

            TextColumn::make('last_name')
                ->sortable()
                ->searchable(),

            TextColumn::make('created_at')
                ->label('Date Created')
                ->sortable()
                ->date('F j, Y'),

            TextColumn::make('company.company_name')
                ->label('Company')
                ->sortable()
                ->searchable(),

            // File Uploads - Display as download links
            Tables\Columns\TextColumn::make('id_document')
                ->label('ID Document')
                ->formatStateUsing(fn ($state) => $state
                    ? '<a href="' . asset('storage/' . $state) . '" target="_blank" class="text-blue-500 hover:underline">📄 View</a>'
                    : '<span class="px-2 py-1 text-xs font-semibold text-red-500 bg-red-100 rounded">Not Uploaded</span>')
                ->html(),

            Tables\Columns\TextColumn::make('nssf_document')
                ->label('NSSF Document')
                ->formatStateUsing(fn ($state) => $state
                    ? '<a href="' . asset('storage/' . $state) . '" target="_blank" class="text-blue-500 hover:underline">📄 View</a>'
                    : '<span class="px-2 py-1 text-xs font-semibold text-red-500 bg-red-100 rounded">Not Uploaded</span>')
                ->html(),

            Tables\Columns\TextColumn::make('nhif_document')
                ->label('NHIF Document')
                ->formatStateUsing(fn ($state) => $state
                    ? '<a href="' . asset('storage/' . $state) . '" target="_blank" class="text-blue-500 hover:underline">📄 View</a>'
                    : '<span class="px-2 py-1 text-xs font-semibold text-red-500 bg-red-100 rounded">Not Uploaded</span>')
                ->html(),

            Tables\Columns\TextColumn::make('birth_certificate')
                ->label('Birth Certificate')
                ->formatStateUsing(fn ($state) => $state
                    ? '<a href="' . asset('storage/' . $state) . '" target="_blank" class="text-blue-500 hover:underline">📄 View</a>'
                    : '<span class="px-2 py-1 text-xs font-semibold text-red-500 bg-red-100 rounded">Not Uploaded</span>')
                ->html(),

            Tables\Columns\ImageColumn::make('passport_photo')
                ->label('Passport Photo'),
        ])

            ->filters([
                SelectFilter::make('company_id')
                ->label('Filter by Company')
                ->preload()
                ->relationship('company', 'company_name')
                ->searchable(),
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

            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),

        ];
    }
    protected static function getAllColumns(): array
{
    return Schema::getColumnListing('employees'); // ✅ Automatically fetches all columns from the 'employees' table
}

}
