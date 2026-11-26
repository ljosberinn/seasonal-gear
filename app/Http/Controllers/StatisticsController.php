<?php

namespace App\Http\Controllers;

use App\Gear\GearCatalog;
use App\Gear\GearStatistics;
use App\Gear\PagePreview;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(GearCatalog $catalog, GearStatistics $statistics): View
    {
        $overall = $statistics->overall();
        $specs = $statistics->specs();
        $instances = $statistics->instances();
        $meta = $catalog->meta();

        return view('statistics.index', [
            'meta' => $meta,
            'preview' => PagePreview::statistics($overall, count($specs), $meta['season']['name']),
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

        $specStatistics = $statistics->spec($class, $spec);

        return view('statistics.spec', [
            'meta' => $catalog->meta(),
            'preview' => PagePreview::specStatistics($specStatistics, $selectedClass, $spec),
            'class' => $selectedClass,
            'classKey' => $class,
            'specKey' => $spec,
            'overall' => $statistics->overall(),
            'statistics' => $specStatistics,
        ]);
    }

    public function instance(GearCatalog $catalog, GearStatistics $statistics, int $instance): View
    {
        $instanceStatistics = $statistics->instance($instance) ?? abort(404);

        return view('statistics.instance', [
            'meta' => $catalog->meta(),
            'preview' => PagePreview::instanceStatistics($instanceStatistics),
            'instance' => $instanceStatistics,
            'overall' => $statistics->overall(),
        ]);
    }
}
