<?php

namespace App\Filament\Resources\BetResource\Pages;

use App\Filament\Resources\BetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBets extends ListRecords
{
    protected static string $resource = BetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
