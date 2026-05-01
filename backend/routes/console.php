<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\RestaurantOffer;
use App\Models\RestaurantStory;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('offers:expire', function () {
    $now = Carbon::now();

    $count = RestaurantOffer::query()
        ->where('status', RestaurantOffer::STATUS_PUBLISHED)
        ->whereNotNull('ends_at')
        ->where('ends_at', '<', $now)
        ->update(['status' => RestaurantOffer::STATUS_EXPIRED]);

    $this->info("Expired {$count} offer(s).");

    return 0;
})->purpose('Mark ended published offers as expired');

Artisan::command('stories:expire', function () {
    $now = Carbon::now();

    $count = RestaurantStory::query()
        ->where('status', RestaurantStory::STATUS_PUBLISHED)
        ->whereNotNull('ends_at')
        ->where('ends_at', '<', $now)
        ->update(['status' => RestaurantStory::STATUS_EXPIRED]);

    $this->info("Expired {$count} story(ies).");

    return 0;
})->purpose('Mark ended published stories as expired');
