<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageMemberController extends Controller
{
    public function index(Page $page, Request $request): View
    {
        $this->authorize('view', $page);

        $members = $page->members()
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->with('user')->latest()->paginate(20)->withQueryString();

        return view('pages.members', [
            'page' => $page,
            'members' => $members,
        ]);
    }

    public function store(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('join', $page);

        $page->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return back()->with('status', __('app.page_joined'));
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('leave', $page);

        $page->members()->where('user_id', $request->user()->id)->delete();

        return back()->with('status', __('app.page_left'));
    }

    public function update(Request $request, Page $page, User $user): RedirectResponse
    {
        $this->authorize('update', $page->members()->where('user_id', $user->id)->firstOrFail());

        $validated = $request->validate([
            'role' => ['required', 'in:admin,moderator,member'],
        ]);

        $page->members()->where('user_id', $user->id)->update(['role' => $validated['role']]);

        return back()->with('status', __('app.page_role_updated'));
    }

    public function remove(Request $request, Page $page, User $user): RedirectResponse
    {
        $member = $page->members()->where('user_id', $user->id)->firstOrFail();

        $this->authorize('delete', $member);

        $member->delete();

        return back()->with('status', __('app.page_member_removed'));
    }
}
