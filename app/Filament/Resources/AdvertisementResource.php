<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Lets admins create and manage the ad banners served across the
 * platform (sidebar, in-page banner, and one-time popup placements — see
 * <x-ad-banner> / <x-ad-popup> and Advertisement::scopeActive/scopePlacement).
 * Given its own top-level "Advertising" nav group so advertisers'
 * campaigns are easy for admins to find and manage.
 */
class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Advertising';

    protected static ?string $navigationLabel = 'Ad Banners';

    protected static ?string $modelLabel = 'ad banner';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Creative')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Banner image')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('advertisements')
                            ->helperText('Recommended: 1200x400 for banners, 600x600 for popups/sidebar.')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('redirect_url')
                            ->label('Advertiser destination URL')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('placement')
                            ->options([
                                'sidebar' => 'Sidebar (dashboard)',
                                'banner' => 'In-page banner (shop, lobby)',
                                'popup' => 'One-time popup',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Schedule & budget')
                    ->description('The ad automatically stops serving once any budget is exhausted or the end date passes.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->default(now()->addMonth()),
                        Forms\Components\TextInput::make('impressions_budget')
                            ->label('Impressions budget (optional)')
                            ->numeric(),
                        Forms\Components\TextInput::make('clicks_budget')
                            ->label('Clicks budget (optional)')
                            ->numeric(),
                        Forms\Components\TextInput::make('impressions_served')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('clicks_served')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->getStateUsing(fn (Advertisement $record) => $record->displayImageUrl()),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('placement')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('impressions_served')
                    ->label('Impressions')
                    ->formatStateUsing(fn (Advertisement $record) => $record->impressions_budget
                        ? "{$record->impressions_served} / {$record->impressions_budget}"
                        : (string) $record->impressions_served)
                    ->sortable(),
                Tables\Columns\TextColumn::make('clicks_served')
                    ->label('Clicks')
                    ->formatStateUsing(fn (Advertisement $record) => $record->clicks_budget
                        ? "{$record->clicks_served} / {$record->clicks_budget}"
                        : (string) $record->clicks_served)
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->options([
                        'sidebar' => 'Sidebar',
                        'banner' => 'Banner',
                        'popup' => 'Popup',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->excludeAttributes(['impressions_served', 'clicks_served'])
                    ->beforeReplicaSaved(function (Advertisement $replica) {
                        $replica->title = "{$replica->title} (copy)";
                        $replica->impressions_served = 0;
                        $replica->clicks_served = 0;
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
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
