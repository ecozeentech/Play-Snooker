<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscrowResource\Pages;
use App\Models\Escrow;
use App\Services\AuditLogService;
use App\Services\EscrowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EscrowResource extends Resource
{
    protected static ?string $model = Escrow::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Marketplace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seller_id')
                    ->relationship('seller', 'name')
                    ->required(),
                Forms\Components\Select::make('buyer_id')
                    ->relationship('buyer', 'name'),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name'),
                Forms\Components\Select::make('inventory_item_id')
                    ->relationship('inventoryItem', 'id'),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required(),
                Forms\Components\TextInput::make('fee_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'disputed' => 'Disputed',
                        'released' => 'Released',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('dispute_reason')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('resolution_notes')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('released_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buyer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventoryItem.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fee_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disputed' => 'danger',
                        'released' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('released_at')
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
                        'pending' => 'Pending',
                        'disputed' => 'Disputed',
                        'released' => 'Released',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('release')
                    ->label('Release to seller')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Escrow $record) => in_array($record->status, ['pending', 'disputed']) && $record->buyer_id)
                    ->requiresConfirmation()
                    ->action(function (Escrow $record) {
                        app(EscrowService::class)->release($record, auth()->user());
                        app(AuditLogService::class)->record(auth()->user(), 'escrow.release', $record);

                        Notification::make()->title('Escrow released to seller')->success()->send();
                    }),
                Tables\Actions\Action::make('refund')
                    ->label('Refund buyer')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Escrow $record) => $record->buyer_id && $record->status !== 'released')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (Escrow $record, array $data) {
                        app(EscrowService::class)->refund($record, $data['reason'], auth()->user());
                        app(AuditLogService::class)->record(auth()->user(), 'escrow.refund', $record);

                        Notification::make()->title('Escrow refunded to buyer')->success()->send();
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
            'index' => Pages\ListEscrows::route('/'),
            'create' => Pages\CreateEscrow::route('/create'),
            'edit' => Pages\EditEscrow::route('/{record}/edit'),
        ];
    }
}
