<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BetResource\Pages;
use App\Models\Bet;
use App\Services\AuditLogService;
use App\Services\BettingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BetResource extends Resource
{
    protected static ?string $model = Bet::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Betting';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('match_id')
                    ->relationship('match', 'id')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required(),
                Forms\Components\TextInput::make('odds')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('type')
                    ->options([
                        'winner' => 'Match winner',
                        'frame_winner' => 'Frame winner',
                        'total_points_over_under' => 'Total points over/under',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('selection')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'won' => 'Won',
                        'lost' => 'Lost',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('payout')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('settled_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('match.id')
                    ->label('Match')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Bet $record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('odds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payout')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('settled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'won' => 'Won',
                        'lost' => 'Lost',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('forceSettleWon')
                    ->label('Force win')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Bet $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Bet $record) {
                        app(BettingService::class)->settleBet($record, 'won', auth()->user());
                        app(AuditLogService::class)->record(auth()->user(), 'bet.force_settle_won', $record);

                        Notification::make()->title('Bet force-settled as won')->success()->send();
                    }),
                Tables\Actions\Action::make('forceSettleLost')
                    ->label('Force lose')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Bet $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Bet $record) {
                        app(BettingService::class)->settleBet($record, 'lost', auth()->user());
                        app(AuditLogService::class)->record(auth()->user(), 'bet.force_settle_lost', $record);

                        Notification::make()->title('Bet force-settled as lost')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBets::route('/'),
            'create' => Pages\CreateBet::route('/create'),
            'edit' => Pages\EditBet::route('/{record}/edit'),
        ];
    }
}
