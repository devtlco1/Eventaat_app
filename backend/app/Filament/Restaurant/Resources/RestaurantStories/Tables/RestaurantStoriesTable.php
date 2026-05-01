<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Tables;

use App\Models\Restaurant;
use App\Models\RestaurantStory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
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
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];

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
                    ->visible(count($restaurantIds) > 1)
                    ->options(fn () => Restaurant::query()->whereIn('id', $restaurantIds)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
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
                Action::make('submit_for_review')
                    ->label('Submit for review')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantStory $record): bool => $record->status === RestaurantStory::STATUS_DRAFT)
                    ->action(function (RestaurantStory $record): void {
                        try {
                            $record->loadMissing('items');

                            if (! $record->hasRenderableContent()) {
                                throw ValidationException::withMessages([
                                    'title' => 'Add at least one story item (or legacy media/body) before submitting.',
                                ]);
                            }

                            $record->applyLifetimeWindowForPublishing();
                            $record->forceFill(['status' => RestaurantStory::STATUS_PENDING_REVIEW])->save();

                            Notification::make()->title('Submitted for review')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Unable to submit')
                                ->danger()
                                ->body(collect($e->errors())->flatten()->first() ?? 'Validation failed.')
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantStory $record): bool => in_array($record->status, [
                        RestaurantStory::STATUS_DRAFT,
                        RestaurantStory::STATUS_PENDING_REVIEW,
                    ], true))
                    ->action(function (RestaurantStory $record): void {
                        $record->forceFill(['status' => RestaurantStory::STATUS_CANCELLED])->save();
                        Notification::make()->title('Story cancelled')->success()->send();
                    }),
            ]);
    }
}
