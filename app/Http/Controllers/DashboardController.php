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

    public function user(Request $request): View
    {
        $user = $request->user();
        $templateCounts = $user->whatsappTemplates()
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')->pluck('total', 'approval_status');
        $campaignCounts = $user->whatsappBroadcasts()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $blockedDrafts = $user->whatsappBroadcasts()->where('status', WhatsappBroadcast::STATUS_DRAFT)
            ->whereHas('template', fn ($query) => $query->where('approval_status', '!=', WhatsappTemplate::APPROVED))
            ->count();

        return view('user.dashboard', [
            'templateCounts' => $templateCounts,
            'campaignCounts' => $campaignCounts,
            'totalTemplates' => $templateCounts->sum(),
            'totalCampaigns' => $campaignCounts->sum(),
            'blockedDrafts' => $blockedDrafts,
        ]);
    }

    public function admin(): View
    {
        return view('admin.dashboard', [
            'pendingTemplates' => WhatsappTemplate::where('approval_status', WhatsappTemplate::PENDING)->count(),
            'pendingBroadcasts' => WhatsappBroadcast::where('status', WhatsappBroadcast::STATUS_PENDING)->count(),
            'approvedBroadcasts' => WhatsappBroadcast::where('status', WhatsappBroadcast::STATUS_APPROVE)->count(),
            'totalUsers' => User::count(),
            'totalAdmins' => User::where('role', User::ROLE_ADMIN)->count(),
            'totalMembers' => User::where('role', User::ROLE_USER)->count(),
            'recentUsers' => User::latest('id')->limit(5)->get(),
        ]);
    }
}
