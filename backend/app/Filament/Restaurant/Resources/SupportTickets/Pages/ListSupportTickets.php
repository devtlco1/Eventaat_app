<?php

namespace App\Filament\Restaurant\Resources\SupportTickets\Pages;

use App\Filament\Restaurant\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
