<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }

    public function admin(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalAdmins' => User::where('role', User::ROLE_ADMIN)->count(),
            'totalMembers' => User::where('role', User::ROLE_USER)->count(),
            'recentUsers' => User::latest('id')->limit(5)->get(),
        ]);
    }
}
