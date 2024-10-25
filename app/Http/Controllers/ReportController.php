<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Report/Report');
    }

    public function getRebateBreakdown(Request $request)
    {
        $user = Auth::user();

        // Fetch rebate details
        $rebate_details = $user->rebateAllocations()
        ->where('account_type_id', $request->account_type_id)
        ->with(['symbol_group:id,display'])  // Eager load symbol_group relation
        ->get();

        return response()->json([
            'rebateDetails' => $rebate_details
        ]);
    }
}
