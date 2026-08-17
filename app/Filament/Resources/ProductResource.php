<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Marketplace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'cue' => 'Cue',
                        'booster' => 'Booster',
                        'table_skin' => 'Table skin',
                        'avatar_frame' => 'Avatar frame',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_url')
                    ->image(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                Forms\Components\TextInput::make('currency')
                    ->required(),
                Forms\Components\KeyValue::make('stats_bonus')
                    ->label('Stat bonuses')
                    ->helperText('e.g. aim: 5, control: 3, xp_multiplier: 1.5')
                    ->columnSpanFull(),
                Forms\Components\Section::make('Cue appearance')
                    ->description('Customize how this cue is rendered in the digital game engine.')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'cue')
                    ->columns(4)
                    ->schema([
                        Forms\Components\ColorPicker::make('appearance.shaft_color')
                            ->label('Shaft color')
                            ->default('#c1935c'),
                        Forms\Components\ColorPicker::make('appearance.wrap_color')
                            ->label('Grip/wrap color')
                            ->default('#4a301d'),
                        Forms\Components\ColorPicker::make('appearance.tip_color')
                            ->label('Tip color')
                            ->default('#2b6cb0'),
                        Forms\Components\ColorPicker::make('appearance.butt_color')
                            ->label('Butt cap color')
                            ->default('#1f140c'),
                    ]),
                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric(),
                Forms\Components\Toggle::make('is_giftable')
                    ->required(),
                Forms\Components\Toggle::make('is_tradeable')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
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
                Tables\Columns\ColorColumn::make('appearance.shaft_color')
                    ->label('Cue color')
                    ->placeholder('—'),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_giftable')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_tradeable')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cue' => 'Cue',
                        'booster' => 'Booster',
                        'table_skin' => 'Table skin',
                        'avatar_frame' => 'Avatar frame',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
