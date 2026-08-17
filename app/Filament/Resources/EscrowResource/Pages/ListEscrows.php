<?php

namespace App\Filament\Resources\EscrowResource\Pages;

use App\Filament\Resources\EscrowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEscrows extends ListRecords
{
    protected static string $resource = EscrowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
