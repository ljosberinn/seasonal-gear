<?php

use App\Http\Controllers\GearController;
use Illuminate\Support\Facades\Route;

Route::get('/', GearController::class)->name('gear.index');

Route::get('/{class}/{spec?}', GearController::class)
    ->where(['class' => '[a-z-]+', 'spec' => '[a-z-]+'])
    ->name('gear.class');
