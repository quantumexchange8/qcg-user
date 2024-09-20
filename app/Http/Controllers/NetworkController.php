<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    public function index()
    {
        return Inertia::render('Network/Network');
    }
}
