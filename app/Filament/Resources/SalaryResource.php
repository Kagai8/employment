<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Filament\Resources\SalaryResource\RelationManagers;
use App\Models\Advance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Salary;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('EMPLOYEE SELECTION')
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
                                $set('basic_salary', $employee?->basic_salary ?? 0);
                                $set('bank_name', $employee?->bank_name ?? '');
                                $set('bank_account_number', $employee?->bank_account_number ?? '');
                                $set('bank_branch', $employee?->bank_branch ?? '');
                                $set('mpesa_number', $employee?->phone ?? '');
                                $set('company_id', $employee?->company_id ?? '');

                                // ✅ Fetch active loan
                                $loan = Loan::where('employee_id', $employee->id ?? null)
                                    ->where('status', 'pending')
                                    ->first();

                                if ($loan) {
                                    $installment = Installment::where('loan_id', $loan->id)
                                        ->where('status', 'unpaid')
                                        ->orderBy('due_date')
                                        ->first();

                                    if ($installment) {
                                        $set('loan_installment_id', $installment->id); // Store ID
                                        $set('loan_deduction', $installment->amount); // Store amount
                                    } else {
                                        $set('loan_installment_id', null);
                                        $set('loan_deduction', 0);
                                    }

                                    // ✅ Set Loan Amount & Balance Fields
                                    $set('loan_id', $loan->id);
                                    $set('loan_amount', $loan->amount);  // Loan total amount
                                    $set('loan_balance', $loan->balance); // Loan remaining balance

                                } else {
                                    $set('loan_installment_id', null);
                                    $set('loan_deduction', 0);

                                    // ✅ No Loan: Set Fields to Null
                                    $set('loan_amount', null);
                                    $set('loan_balance', null);
                                }

                                // ✅ Fetch Active Advances
                                $advances = Advance::where('employee_id', $employee->id ?? null)
                                ->where('remaining_balance', '>', 0)
                                ->get();

                                $totalAdvances = $advances->sum('remaining_balance'); // Sum remaining advances
                                $set('advance_balance', $totalAdvances); // Show balance
                                $set('advance_deduction', min($totalAdvances, $set('net_salary', 0)));
                            }
                        }),

                        Hidden::make('company_id')
                            ->dehydrated() // Ensure it's saved to the database
                            ->default(fn (Get $get) => $get('company_id')),
                    ])
                    ->columns(2),
                Section::make('PAYMENT METHOD')
                    ->schema([
                        Forms\Components\Radio::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'bank' => 'Bank',
                                'mpesa' => 'M-Pesa',
                            ])
                            ->reactive()
                            ->default('bank')
                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                $employee = Employee::find($get('employee_id'));

                                if ($state === 'mpesa' && $employee) {
                                    $set('mpesa_number', $employee->phone ?? '');
                                } else {
                                    $set('mpesa_number', ''); // Clear if Bank is selected
                                }
                            }),

                        // Bank Details (Only Visible When "Bank" is Selected)
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->disabled()
                            ->hidden(fn (Get $get) => $get('payment_method') !== 'bank'),

                        TextInput::make('bank_branch')
                            ->label('Bank Branch')
                            ->disabled()
                            ->hidden(fn (Get $get) => $get('payment_method') !== 'bank'),

                        TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->disabled()
                            ->hidden(fn (Get $get) => $get('payment_method') !== 'bank'),

                        // M-Pesa Number (Editable, But Auto-filled from Employee Phone)
                        TextInput::make('mpesa_number')
                            ->label('M-Pesa Number')
                            ->numeric()
                            ->minLength(10)
                            ->maxLength(10)
                            ->required()
                            ->hidden(fn (Get $get) => $get('payment_method') !== 'mpesa')
                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                if ($get('payment_method') === 'mpesa' && empty($state)) {
                                    $employee = Employee::find($get('employee_id'));
                                    $set('mpesa_number', $employee?->phone ?? '');
                                }
                            }),
                    ])
                    ->columns(2),

            Section::make('EARNINGS')
                ->schema([
                    Section::make('Basic Earnings')
                        ->schema([
                            TextInput::make('basic_salary')
                                ->numeric()
                                ->required()
                                ->disabled(fn ($context) => $context === 'edit') // Only disable in edit mode
                                ->dehydrated(), // Ensure it's included in the form submission

                            TextInput::make('house_allowance')
                                ->numeric()
                                ->default(0)
                                ->debounce(500),

                            TextInput::make('transport_allowance')
                                ->numeric()
                                ->default(0)
                                ->debounce(500),
                    ])->columns(3),
                    Section::make('Other Earnings')
                        ->schema([
                            Repeater::make('extra_earnings')
                                ->schema([
                                    TextInput::make('name'),
                                    TextInput::make('amount')->numeric(),
                                ])->columns(2)
                                ->label('Additional Earnings')
                                ->columnSpanFull()
                                ->debounce(500),
                        ]),


                ])
                ->columns(2),

            Section::make('DEDUCTIONS')
                ->schema([
                    Section::make('Taxes')
                        ->schema([
                            TextInput::make('paye_tax')
                                ->numeric()
                                ->required()
                                ->debounce(500),

                            TextInput::make('sha_contribution')
                                ->numeric()
                                ->required()
                                ->debounce(500),

                            TextInput::make('nssf_contribution')
                                ->numeric()
                                ->required()
                                ->debounce(500),
                        ])->columns(3),

                    Section::make('Other Deductions')
                        ->schema([
                            Repeater::make('extra_deductions')
                            ->schema([
                                TextInput::make('name'),
                                TextInput::make('amount')->numeric(),
                            ])->columns(2)
                            ->label('Additional Deductions')
                            ->columnSpanFull()
                            ->debounce(500),
                        ]),
                    Section::make('Loans')
                        ->schema([
                            Radio::make('deduct_loan')
                                ->label('Deduct Loan Installment?')
                                ->options([
                                    'yes' => 'Yes',
                                    'no' => 'No',
                                ])
                                ->default('no')
                                ->required() // ✅ Ensures it cannot be null
                                ->dehydrated() // ✅ Ensures it is saved in the database
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                    if ($state === 'yes') {
                                        $installment = Installment::find($get('loan_installment_id'));

                                        if ($installment) {
                                            $set('loan_deduction', $installment->amount); // ✅ Set correct amount
                                        }
                                    } else {
                                        $set('loan_deduction', 0); // ✅ Set to 0 when "No"
                                    }
                                }),
                            TextInput::make('loan_deduction')
                                ->label('Loan Deduction')
                                ->numeric()
                                ->dehydrated()
                                ->live(),

                            Hidden::make('loan_id')
                                ->dehydrated(), // Ensure it's saved to the database

                            TextInput::make('loan_amount')
                                ->label('Loan Amount')
                                ->numeric()
                                ->disabled(),

                            TextInput::make('loan_balance')
                                ->label('Loan Balance')
                                ->numeric()
                                ->disabled(),
                        ])->columns(2),
                    Section::make('Advances')
                        ->schema([
                            TextInput::make('advance_deduction')
                                ->label('Advance Deduction')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                    $netSalary = max(($get('net_salary') ?? 0) - $state, 0);
                                    $set('net_salary', $netSalary);
                                }),
                            TextInput::make('advance_balance')
                                ->label('Advance Balance')
                                ->numeric()
                                ->default(0)
                                ->disabled(), // Show advance balance but prevent changes
                        ])->columns(2),

                    ]),


                Section::make('NET Pay')
                    ->schema([
                        Placeholder::make('net_salary_placeholder')
                            ->label('Net Salary')
                            ->content(function (Get $get, Set $set) {
                                $total = (float) ($get('basic_salary') ?? 0) +
                                        (float) ($get('house_allowance') ?? 0) +
                                        (float) ($get('transport_allowance') ?? 0) +
                                        (float) collect($get('extra_earnings') ?? [])->sum(fn ($item) => (float) $item['amount']) -
                                        ((float) ($get('paye_tax') ?? 0) +
                                        (float) ($get('sha_contribution') ?? 0) +
                                        (float) ($get('nssf_contribution') ?? 0) +
                                        (float) collect($get('extra_deductions') ?? [])->sum(fn ($item) => (float) $item['amount'])+
                                        (float) ($get('loan_deduction') ?? 0));
                                        ;

                                // Deduct advance deduction
                                $advanceDeduction = (float) ($get('advance_deduction') ?? 0);
                                $total -= $advanceDeduction;

                                $set('net_salary', max($total, 0));

                                            return Number::currency($total, 'KES'); // Display formatted salary
                            }),

                        Hidden::make('net_salary')
                            ->default(0) // Default value to prevent null issues
                            ->dehydrated() // Ensure it's stored in the database
                            ->live(), // Ensure real-time updates
                        ]),


        ])
        ;
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

                TextColumn::make('basic_salary'),
                // Total Earnings (Basic Salary + House Allowance + Transport Allowance + Extra Earnings)
                TextColumn::make('total_earnings')
                    ->label('Total Earnings')
                    ->getStateUsing(fn ($record) =>
                        $record->basic_salary +
                        $record->house_allowance +
                        $record->transport_allowance +
                        collect($record->extra_earnings)->sum('amount')
                    ),
                // Total Deductions (PAYE + SHA + NSSF + Extra Deductions)
                TextColumn::make('total_deductions')
                    ->label('Total Deductions')
                    ->getStateUsing(fn ($record) =>
                        $record->paye_tax +
                        $record->sha_contribution +
                        $record->nssf_contribution +
                        collect($record->extra_deductions)->sum('amount')
                    ),
                TextColumn::make('payment_method')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('net_salary')
                    ->label('Net Salary')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date Issued')
                    ->sortable()
                    ->searchable()
                    ->date('F j, Y')
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'company_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('payment_method')
                    ->label('Filter by Payment Method')
                    ->options([
                        'bank' => 'Bank',
                        'mpesa' => 'M-Pesa',
                    ])
                    ->searchable()
                    ->preload()
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('Generate Payslip')
                        ->icon('heroicon-o-folder-arrow-down')
                        ->url(fn ($record) => route('payroll.pdf', $record->id))
                        ->openUrlInNewTab()
                        ->color('primary'),
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
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }
}
