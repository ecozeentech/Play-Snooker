<?php

namespace App\Filament\Resources\BetResource\Pages;

use App\Filament\Resources\BetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBet extends EditRecord
{
    protected static string $resource = BetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
