<?php

use App\Http\Controllers\GearController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', GearController::class)->name('gear.index');

// registered before the class route, which would otherwise read "statistics" as a class
Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
Route::get('/statistics/instances/{instance}', [StatisticsController::class, 'instance'])
    ->whereNumber('instance')
    ->name('statistics.instance');
Route::get('/statistics/{class}/{spec}', [StatisticsController::class, 'spec'])
    ->where(['class' => '[a-z-]+', 'spec' => '[a-z-]+'])
    ->name('statistics.spec');

Route::get('/{class}/{spec?}', GearController::class)
    ->where(['class' => '[a-z-]+', 'spec' => '[a-z-]+'])
    ->name('gear.class');
