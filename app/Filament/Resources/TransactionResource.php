<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only financial transaction ledger. All financial mutations must go
 * through WalletService so every entry here is a verified audit trail;
 * transactions cannot be created or edited from the admin panel directly.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Platform';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('type')->disabled(),
                Forms\Components\TextInput::make('amount')->disabled(),
                Forms\Components\TextInput::make('currency')->disabled(),
                Forms\Components\TextInput::make('amount_usd')->disabled(),
                Forms\Components\TextInput::make('gateway')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\TextInput::make('reference')->disabled(),
                Forms\Components\TextInput::make('description')->disabled(),
                Forms\Components\Textarea::make('meta')->disabled()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'credit' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(['credit' => 'Credit', 'debit' => 'Debit']),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'completed' => 'Completed',
                    'failed' => 'Failed', 'cancelled' => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('gateway')
                    ->options(fn () => Transaction::query()->whereNotNull('gateway')->distinct()->pluck('gateway', 'gateway')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
