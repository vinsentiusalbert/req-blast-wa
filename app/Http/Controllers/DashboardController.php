<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappTemplate;
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
            ...$this->whatsappStats(),
        ]);
    }

    public function user(Request $request): View
    {
        return view('user.dashboard', $this->whatsappStats($request->user()->id));
    }

    private function whatsappStats(?int $userId = null): array
    {
        $templates = WhatsappTemplate::query()->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total', 'approval_status');
        $broadcasts = WhatsappBroadcast::query()->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'totalTemplates' => $templates->sum(),
            'templateCounts' => collect(WhatsappTemplate::APPROVAL_LABELS)->map(fn ($label, $status) => (int) $templates->get($status, 0)),
            'totalBroadcasts' => $broadcasts->sum(),
            'broadcastCounts' => collect([WhatsappBroadcast::STATUS_DRAFT => 0])->merge($broadcasts)->sortKeys(),
        ];
    }
}
