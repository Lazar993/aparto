<?php

namespace App\Filament\Resources\HostRequestResource\Pages;

use App\Filament\Resources\HostRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListHostRequests extends ListRecords
{
    protected static string $resource = HostRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
