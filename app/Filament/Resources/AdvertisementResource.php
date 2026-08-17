<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Platform';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\FileUpload::make('image_url')
                    ->image()
                    ->required(),
                Forms\Components\TextInput::make('redirect_url')
                    ->required(),
                Forms\Components\Select::make('placement')
                    ->options(['sidebar' => 'Sidebar', 'banner' => 'Banner', 'popup' => 'Popup'])
                    ->required(),
                Forms\Components\TextInput::make('impressions_budget')
                    ->numeric(),
                Forms\Components\TextInput::make('impressions_served')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('clicks_budget')
                    ->numeric(),
                Forms\Components\TextInput::make('clicks_served')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('redirect_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('placement')
                    ->searchable(),
                Tables\Columns\TextColumn::make('impressions_budget')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('impressions_served')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clicks_budget')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clicks_served')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
                //
            ])
            ->actions([
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
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
