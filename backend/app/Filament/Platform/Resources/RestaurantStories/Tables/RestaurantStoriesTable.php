<?php

namespace App\Filament\Platform\Resources\RestaurantStories\Tables;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStory;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RestaurantStoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                TextColumn::make('story_type')->label('Story type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('starts_at')->label('Starts at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Ends at')->dateTime()->sortable(),
                TextColumn::make('display_order')->label('Display order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantStory::STATUSES, RestaurantStory::STATUSES)),
                SelectFilter::make('story_type')
                    ->label('Story type')
                    ->options(array_combine(RestaurantStory::STORY_TYPES, RestaurantStory::STORY_TYPES)),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
                    )),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $branchId) => $q->where('branch_id', $branchId),
                    )),
                Filter::make('active_now')
                    ->label('Active now')
                    ->query(function (Builder $query): Builder {
                        $now = Carbon::now();

                        return $query
                            ->where(function (Builder $q) use ($now) {
                                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                            })
                            ->where(function (Builder $q) use ($now) {
                                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                            });
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantStory $record): bool => $record->status === RestaurantStory::STATUS_PENDING_REVIEW)
                    ->action(function (RestaurantStory $record): void {
                        try {
                            $record->loadMissing('items');

                            if (! $record->hasRenderableContent()) {
                                throw ValidationException::withMessages([
                                    'title' => 'Story has no renderable items (or legacy media/body).',
                                ]);
                            }

                            $record->applyLifetimeWindowForPublishing();
                            $record->forceFill(['status' => RestaurantStory::STATUS_PUBLISHED])->save();

                            Notification::make()->title('Story approved')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Unable to approve')
                                ->danger()
                                ->body(collect($e->errors())->flatten()->first() ?? 'Validation failed.')
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantStory $record): bool => $record->status === RestaurantStory::STATUS_PENDING_REVIEW)
                    ->action(function (RestaurantStory $record): void {
                        $record->forceFill(['status' => RestaurantStory::STATUS_REJECTED])->save();
                        Notification::make()->title('Story rejected')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantStory $record): bool => in_array($record->status, [
                        RestaurantStory::STATUS_DRAFT,
                        RestaurantStory::STATUS_PENDING_REVIEW,
                        RestaurantStory::STATUS_PUBLISHED,
                        RestaurantStory::STATUS_REJECTED,
                    ], true))
                    ->action(function (RestaurantStory $record): void {
                        $record->forceFill(['status' => RestaurantStory::STATUS_CANCELLED])->save();
                        Notification::make()->title('Story cancelled')->success()->send();
                    }),
            ]);
    }
}
