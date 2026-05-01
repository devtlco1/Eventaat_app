<?php

namespace App\Filament\Platform\Resources\SupportTickets\RelationManagers;

use App\Filament\Platform\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicketActivity;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupportTicketActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Internal activity';

    // Non-lazy so table header actions register on first Livewire render (tests + predictable UX).
    protected static bool $isLazy = false;

    protected static bool $shouldSkipAuthorization = true;

    /**
     * Allow append-only internal notes on ticket view pages (panel default treats view relation managers as read-only).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('—'),
                TextColumn::make('old_status')
                    ->label('Old status')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('new_status')
                    ->label('New status')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->label('Message')
                    ->wrap()
                    ->placeholder('—')
                    ->limit(120),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => SupportTicketResource::canEdit($this->getOwnerRecord()))
                    ->label('Add internal note')
                    ->modalHeading('Add internal note')
                    ->createAnother(false)
                    ->schema([
                        Textarea::make('message')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'type' => SupportTicketActivity::TYPE_NOTE,
                        'is_internal' => true,
                        'user_id' => Filament::auth()->id(),
                    ]),
            ])
            ->recordActions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
