<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    public function index(): View
    {
        return view('stamp', ['userName' => Auth::user()->name]);
    }
}
