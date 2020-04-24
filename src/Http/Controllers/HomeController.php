<?php

namespace Isotopes\Profiler\Http\Controllers;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Isotopes\Profiler\Profiler;

class HomeController extends Controller
{
    /**
     * Display the Profiler view.
     *
     * @return Application|Factory|View
     * @throws Exception
     */
    public function index()
    {
        return view('profiler::layout', [
            'cssFile' => Profiler::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'profilerScriptVariables' => Profiler::scriptVariables(),
            'assetsAreCurrent' => Profiler::assetsAreCurrent(),
        ]);
    }
}
