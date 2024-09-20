<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RebateSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('RebateSetting/RebateSetting');
    }
}
