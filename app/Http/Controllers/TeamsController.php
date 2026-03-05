<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeamsController extends Controller
{
    public function index(Request $request)
    {
        return view('teams.tab');
    }
}