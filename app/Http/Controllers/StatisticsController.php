<?php

namespace App\Http\Controllers;

use App\Gear\GearCatalog;
use App\Gear\GearStatistics;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(GearCatalog $catalog, GearStatistics $statistics): View
    {
        $overall = $statistics->overall();
        $specs = $statistics->specs();
        $instances = $statistics->instances();

        return view('statistics.index', [
            'meta' => $catalog->meta(),
            'overall' => $overall,
            'specs' => $specs,
            'instances' => $instances,
            'highlights' => GearStatistics::highlights($overall, $specs, $instances),
        ]);
    }

    public function spec(GearCatalog $catalog, GearStatistics $statistics, string $class, string $spec): View
    {
        $selectedClass = config("classes.{$class}") ?? abort(404);

        if (! isset($selectedClass['specs'][$spec])) {
            abort(404);
        }

        return view('statistics.spec', [
            'meta' => $catalog->meta(),
            'class' => $selectedClass,
            'classKey' => $class,
            'specKey' => $spec,
            'overall' => $statistics->overall(),
            'statistics' => $statistics->spec($class, $spec),
        ]);
    }

    public function instance(GearCatalog $catalog, GearStatistics $statistics, int $instance): View
    {
        return view('statistics.instance', [
            'meta' => $catalog->meta(),
            'instance' => $statistics->instance($instance) ?? abort(404),
            'overall' => $statistics->overall(),
        ]);
    }
}
