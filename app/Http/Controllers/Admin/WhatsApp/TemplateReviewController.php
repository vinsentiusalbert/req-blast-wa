<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Actions\WhatsApp\ReviewTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\ReviewTemplateRequest;
use App\Models\WhatsappTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(WhatsappTemplate::APPROVAL_LABELS), 'not_approved'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? '';
        $templates = WhatsappTemplate::query()->with('user')
            ->when($status, fn ($query) => $status === 'not_approved'
                ? $query->where('approval_status', '!=', WhatsappTemplate::APPROVED)
                : $query->where('approval_status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest('updated_at')->latest('id')->paginate(10)->withQueryString();

        return view('admin.whatsapp.templates.index', compact('templates', 'filters', 'status'));
    }

    public function show(WhatsappTemplate $template): View
    {
        Gate::authorize('review', $template);
        $template->load(['user', 'reviewer']);

        return view('admin.whatsapp.templates.show', compact('template'));
    }

    public function update(ReviewTemplateRequest $request, WhatsappTemplate $template, ReviewTemplate $action): RedirectResponse
    {
        $action->handle($request->user(), $template, $request->validated());
        $message = $request->validated('decision') === WhatsappTemplate::APPROVED
            ? 'Template disetujui dan sekarang dapat digunakan oleh pemiliknya untuk broadcast.'
            : 'Template ditolak. User dapat memperbaiki dan mengajukannya kembali.';

        return to_route('admin.whatsapp.templates.show', $template)->with('status', $message);
    }
}
