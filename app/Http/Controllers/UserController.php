<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserAttendanceService;
use App\Services\UserService;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{
    const int PAGINATE = 12;

    const int MAX_ATTENDANCES_PER_PAGE = 5;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $service = app(UserService::class);
        $search = $request->query('search');
        $users = $service->searchUserNames($search ?? '')
            ->paginate(static::PAGINATE)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user, DateTimeZone $tz): View
    {
        $userName = $user->name;

        $date = CarbonImmutable::today($tz);
        try {
            $date = CarbonImmutable::createFromFormat('Y-m', $request->query('ym'), $tz);
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            // ignore
        }

        $currentMonth = $date->firstOfMonth();

        $service = app(UserAttendanceService::class, compact('user', 'date'));
        $attendances = $service->attendances()
            ->orderBy('date')
            ->paginate(static::MAX_ATTENDANCES_PER_PAGE)
            ->withQueryString();

        return view('users.show', compact(
            'user',
            'userName',
            'currentMonth',
            'attendances',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
