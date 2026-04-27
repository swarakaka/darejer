<?php

namespace Darejer\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
        ]);
    }
}
