<?php

namespace Darejer\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Default landing page for authenticated users. Renders an ERPNext-style
 * module index — the same NavigationManager tree shared with the sidebar,
 * but presented as a tile grid so the home view itself doubles as the
 * navigation hub. The Dashboard moves to a dedicated route and shows up
 * here as just another tile.
 */
class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home', [
            'breadcrumbs' => [
                ['label' => __('Home')],
            ],
        ]);
    }
}
