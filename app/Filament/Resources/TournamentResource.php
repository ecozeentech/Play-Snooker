<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TournamentResource\Pages;
use App\Models\Tournament;
use App\Services\TournamentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TournamentResource extends Resource
{
    protected static ?string $model = Tournament::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Tournaments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options(['physical' => 'Physical', 'digital' => 'Digital'])
                    ->required(),
                Forms\Components\Select::make('format')
                    ->options([
                        'single_elimination' => 'Single elimination',
                        'double_elimination' => 'Double elimination',
                        'round_robin' => 'Round robin',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'finished' => 'Finished',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('entry_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('prize_pool')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('currency')
                    ->required(),
                Forms\Components\TextInput::make('max_players')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('registration_closes_at'),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('finished_at'),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\Toggle::make('is_user_created')
                    ->required(),
                Forms\Components\TextInput::make('hosting_fee_paid')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('check_in_enabled')
                    ->required(),
                Forms\Components\DateTimePicker::make('check_in_opens_at'),
                Forms\Components\Textarea::make('bracket_data')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('format')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upcoming' => 'gray',
                        'ongoing' => 'success',
                        'finished' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('registrations_count')
                    ->label('Players')
                    ->counts('registrations'),
                Tables\Columns\TextColumn::make('entry_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prize_pool')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_players')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_closes_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_user_created')
                    ->boolean(),
                Tables\Columns\TextColumn::make('hosting_fee_paid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('check_in_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('check_in_opens_at')
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
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'finished' => 'Finished',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['physical' => 'Physical', 'digital' => 'Digital']),
            ])
            ->actions([
                Tables\Actions\Action::make('shuffleAndSeed')
                    ->label('Shuffle & seed')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->visible(fn (Tournament $record) => $record->status === 'upcoming')
                    ->requiresConfirmation()
                    ->action(function (Tournament $record) {
                        app(TournamentService::class)->shuffleAndSeed($record);

                        Notification::make()
                            ->title('Bracket shuffled and seeded')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTournaments::route('/'),
            'create' => Pages\CreateTournament::route('/create'),
            'edit' => Pages\EditTournament::route('/{record}/edit'),
        ];
    }
}
