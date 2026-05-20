<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Client;
use App\Models\Service;
use App\Models\StockPanel;
use App\Models\StockCanto;
use App\Models\Consumable;

class PosController extends Controller
{
    public function index()
    {
        return Inertia::render('PosApp', [
            'initialClients' => Client::orderBy('name')->get(),
            'initialServices' => Service::all(),
            'initialPanels' => StockPanel::orderBy('type')->get(),
            'initialCantos' => StockCanto::orderBy('color_code')->get(),
            'initialConsumables' => Consumable::orderBy('name')->get(),
        ]);
    }
}
