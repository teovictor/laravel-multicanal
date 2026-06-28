<?php

use App\Schedules\DeactivateOutOfStockProductsTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(DeactivateOutOfStockProductsTask::class)
    ->name('products:deactivate-out-of-stock')
    ->daily();
