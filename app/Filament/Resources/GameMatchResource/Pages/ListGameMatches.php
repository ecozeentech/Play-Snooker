<?php

namespace App\Filament\Resources\GameMatchResource\Pages;

use App\Filament\Resources\GameMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameMatches extends ListRecords
{
    protected static string $resource = GameMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
