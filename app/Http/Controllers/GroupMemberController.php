<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupMemberController extends Controller
{
    public function index(Group $group, Request $request): View
    {
        $this->authorize('view', $group);
        abort_if($group->privacy === 'private' && ! $group->isMember($request->user()), 403);

        $members = $group->members()
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->with('user')->latest()->paginate(20)->withQueryString();

        return view('groups.members', [
            'group' => $group,
            'members' => $members,
        ]);
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('join', $group);

        $group->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return back()->with('status', __('app.group_joined'));
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('leave', $group);

        $group->members()->where('user_id', $request->user()->id)->delete();

        return back()->with('status', __('app.group_left'));
    }

    public function update(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group->members()->where('user_id', $user->id)->firstOrFail());

        $validated = $request->validate([
            'role' => ['required', 'in:admin,moderator,member'],
        ]);

        $group->members()->where('user_id', $user->id)->update(['role' => $validated['role']]);

        return back()->with('status', __('app.group_role_updated'));
    }

    public function remove(Request $request, Group $group, User $user): RedirectResponse
    {
        $member = $group->members()->where('user_id', $user->id)->firstOrFail();

        $this->authorize('delete', $member);

        $member->delete();

        return back()->with('status', __('app.group_member_removed'));
    }
}
