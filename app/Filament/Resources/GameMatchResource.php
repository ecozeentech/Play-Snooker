<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameMatchResource\Pages;
use App\Models\GameMatch;
use App\Services\AuditLogService;
use App\Services\BettingService;
use App\Services\TournamentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameMatchResource extends Resource
{
    protected static ?string $model = GameMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Tournaments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tournament_id')
                    ->relationship('tournament', 'name'),
                Forms\Components\Select::make('player1_id')
                    ->relationship('player1', 'name'),
                Forms\Components\Select::make('player2_id')
                    ->relationship('player2', 'name'),
                Forms\Components\TextInput::make('round')
                    ->numeric(),
                Forms\Components\TextInput::make('current_frame')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('frames_to_win')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'live' => 'Live',
                        'finished' => 'Finished',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\Select::make('winner_id')
                    ->relationship('winner', 'name'),
                Forms\Components\Textarea::make('frame_scores')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('odds_data')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_streamed')
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('ended_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tournament.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player1.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player2.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('round')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_frame')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frames_to_win')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('winner.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_streamed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'live' => 'Live',
                        'finished' => 'Finished',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('forceFinish')
                    ->label('Force finish & settle')
                    ->icon('heroicon-o-flag')
                    ->color('danger')
                    ->visible(fn (GameMatch $record) => $record->status !== 'finished' && $record->player1_id && $record->player2_id)
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('winner_id')
                            ->label('Winner')
                            ->options(fn (GameMatch $record) => collect([
                                $record->player1_id => $record->player1?->name,
                                $record->player2_id => $record->player2?->name,
                            ])->filter())
                            ->required(),
                    ])
                    ->action(function (GameMatch $record, array $data) {
                        $record->update([
                            'status' => 'finished',
                            'winner_id' => $data['winner_id'],
                            'ended_at' => now(),
                        ]);

                        app(BettingService::class)->settleMatchBets($record);

                        if ($record->tournament) {
                            app(TournamentService::class)->advanceWinner($record->tournament, $record);
                        }

                        app(AuditLogService::class)->record(auth()->user(), 'match.force_finish', $record, [], $data);

                        Notification::make()->title('Match finished and bets settled')->success()->send();
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
            'index' => Pages\ListGameMatches::route('/'),
            'create' => Pages\CreateGameMatch::route('/create'),
            'edit' => Pages\EditGameMatch::route('/{record}/edit'),
        ];
    }
}
