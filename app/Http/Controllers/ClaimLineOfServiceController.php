<?php

namespace App\Http\Controllers;

use App\Models\ClaimLineOfService;
use Illuminate\Http\Request;

class ClaimLineOfServiceController extends Controller
{
    public function index()
    {
        $lines = ClaimLineOfService::with('claim', 'check')->get();
        return view('claim_lines.index', compact('lines'));
    }
}
