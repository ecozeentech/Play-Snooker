<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only ledger of every administrative action taken on the platform,
 * kept separate from financial Transaction records per the platform's
 * audit-trail requirements.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Audit Log';

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
                Forms\Components\TextInput::make('action')
                    ->disabled(),
                Forms\Components\TextInput::make('auditable_type')
                    ->disabled(),
                Forms\Components\TextInput::make('auditable_id')
                    ->disabled(),
                Forms\Components\Textarea::make('before')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('after')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->disabled(),
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
                    ->label('Admin')
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Subject type')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('Subject ID'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(fn () => AuditLog::query()->distinct()->pluck('action', 'action')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
