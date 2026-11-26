<?php

use App\Http\Controllers\ForeverController;
use App\Http\Controllers\GearController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// A temporary redirect, so "/" can later become a game version picker without browsers having cached the move.
Route::get('/', fn (Request $request): RedirectResponse => redirect()->to(route('gear.index', $request->query())))->name('home');

Route::prefix('retail')->group(function (): void {
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
});

Route::get('/forever', ForeverController::class)->name('forever.index');

// Retail pages lived at the root before game versions got their own prefix; shared links still point there.
Route::get('/{path}', fn (Request $request, string $path): RedirectResponse => redirect()->to(
    '/retail/'.$path.($request->getQueryString() === null ? '' : '?'.$request->getQueryString()),
    301,
))->where('path', '(statistics|'.implode('|', array_map(preg_quote(...), array_keys(config('classes')))).')(/[a-z0-9-]+)*');
