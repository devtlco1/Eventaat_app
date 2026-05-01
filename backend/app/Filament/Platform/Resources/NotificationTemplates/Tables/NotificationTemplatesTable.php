<?php

namespace App\Filament\Platform\Resources\NotificationTemplates\Tables;

use App\Filament\Platform\Resources\NotificationTemplates\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotificationTemplateRenderer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationTemplatesTable
{
    /**
     * @return array<string, mixed>
     */
    public static function sampleData(): array
    {
        return [
            'customer_name' => 'Alex Johnson',
            'customer_phone' => '+15551234567',
            'restaurant_name' => 'Eventaat Restaurant',
            'branch_name' => 'Downtown Branch',
            'booking_id' => 12345,
            'booking_status' => 'pending',
            'starts_at' => '2026-05-02T18:30:00+03:00',
            'party_size' => 2,
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')->badge()->sortable()->searchable(),
                TextColumn::make('is_active')->label('Active')->badge()->sortable(),
                TextColumn::make('title_template')->label('Title')->limit(40)->searchable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('event', 'asc')
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Template preview (sample data)')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (NotificationTemplate $record) {
                        $renderer = app(NotificationTemplateRenderer::class);
                        $data = self::sampleData();

                        return view('filament.platform.notification-templates.preview', [
                            'event' => $record->event,
                            'data' => $data,
                            'renderedTitle' => $renderer->render((string) $record->title_template, $data),
                            'renderedBody' => $renderer->render((string) $record->body_template, $data),
                        ]);
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

