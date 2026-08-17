<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\WalletService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Platform';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create'),
                Forms\Components\TextInput::make('wallet_balance')
                    ->label('Wallet balance (USD equivalent, read-only)')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric(),
                Forms\Components\Select::make('currency_preference')
                    ->options([
                        'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR',
                        'NGN' => 'NGN', 'BTC' => 'BTC', 'USDT' => 'USDT',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('referral_code')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\Toggle::make('is_admin')
                    ->required(),
                Forms\Components\TextInput::make('locale')
                    ->default('en')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Balance (USD)')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency_preference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deletion_requested_at')
                    ->label('Deletion requested')
                    ->dateTime()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_admin'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('approveDeletion')
                    ->label('Approve deletion')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->deletion_requested_at !== null && ! $record->trashed())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->delete();
                        app(AuditLogService::class)->record(auth()->user(), 'user.approve_deletion', $record);

                        Notification::make()->title('Account deletion approved')->success()->send();
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (User $record) => $record->trashed() || ! $record->is_active)
                    ->action(function (User $record) {
                        if ($record->trashed()) {
                            $record->restore();
                        }

                        $record->update(['is_active' => true, 'deletion_requested_at' => null]);
                        app(AuditLogService::class)->record(auth()->user(), 'user.reactivate', $record);

                        Notification::make()->title('Account reactivated')->success()->send();
                    }),
                Tables\Actions\Action::make('adjustWallet')
                    ->label('Adjust wallet')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->helperText('Positive to credit, negative to debit.')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR',
                                'NGN' => 'NGN', 'BTC' => 'BTC', 'USDT' => 'USDT',
                            ])
                            ->default('USD')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        app(WalletService::class)->adjustBalance($record, (string) $data['amount'], $data['currency'], $data['reason'], auth()->user());
                        app(AuditLogService::class)->record(auth()->user(), 'wallet.adjust', $record, [], $data);

                        Notification::make()->title('Wallet adjusted')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
