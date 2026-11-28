<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = Group::query()
            ->with('owner')
            ->withCount(['members', 'admins'])
            ->visibleTo($user)
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('groups.index', [
            'groups' => $groups,
        ]);
    }

    public function create(): View
    {
        return view('groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:groups,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['sometimes', 'in:public,private'],
            'cover_image' => ['sometimes', 'file', 'image', 'max:10240'],
            'avatar' => ['sometimes', 'file', 'image', 'max:5120'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        if (Group::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(4);
        }

        $group = $request->user()->groups()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'privacy' => $validated['privacy'] ?? 'public',
        ]);

        $this->attachGroupImage($request, $group, 'cover_image');
        $this->attachGroupImage($request, $group, 'avatar');

        $group->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'admin',
            'joined_at' => $group->created_at ?? now(),
        ]);

        return redirect()->route('groups.show', $group)
            ->with('status', __('app.group_created'));
    }

    public function show(Group $group, Request $request): View
    {
        $this->authorize('view', $group);

        $user = $request->user();
        $user?->loadMissing(['blocks', 'blockedBy']);
        $group->load(['owner', 'members:id,group_id,user_id,role'])->loadCount(['members', 'admins']);

        $memberRole = $group->memberRole($user);
        $posts = $memberRole !== null
            ? $group->posts()
                ->withFeedRelations($user)
                ->visibleTo($user)
                ->latest()
                ->paginate(12)
                ->withQueryString()
            : Post::query()->whereRaw('1 = 0')->paginate(12);

        return view('groups.show', [
            'group' => $group,
            'posts' => $posts,
            'memberRole' => $memberRole,
            'isMember' => $memberRole !== null,
        ]);
    }

    public function edit(Group $group): View
    {
        $this->authorize('update', $group);

        return view('groups.edit', [
            'group' => $group,
        ]);
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:groups,slug,'.$group->id],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'privacy' => ['sometimes', 'required', 'in:public,private'],
            'cover_image' => ['sometimes', 'file', 'image', 'max:10240'],
            'avatar' => ['sometimes', 'file', 'image', 'max:5120'],
            'remove_cover' => ['sometimes', 'boolean'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        $group->update([
            'name' => $validated['name'] ?? $group->name,
            'slug' => $validated['slug'] ?? $group->slug,
            'description' => $validated['description'] ?? $group->description,
            'privacy' => $validated['privacy'] ?? $group->privacy,
        ]);

        if ($request->boolean('remove_cover') && $group->cover_image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $group->cover_image));
            $group->update(['cover_image' => null]);
        }

        if ($request->boolean('remove_avatar') && $group->avatar) {
            Storage::disk('public')->delete(str_replace('storage/', '', $group->avatar));
            $group->update(['avatar' => null]);
        }

        $this->attachGroupImage($request, $group, 'cover_image');
        $this->attachGroupImage($request, $group, 'avatar');

        return redirect()->route('groups.show', $group)
            ->with('status', __('app.group_updated'));
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        if ($group->cover_image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $group->cover_image));
        }
        if ($group->avatar) {
            Storage::disk('public')->delete(str_replace('storage/', '', $group->avatar));
        }

        $group->delete();

        return redirect()->route('groups.index')
            ->with('status', __('app.group_deleted'));
    }

    private function attachGroupImage(Request $request, Group $group, string $field): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $path = $request->file($field)->store('groups', 'public');

        $group->update([$field => 'storage/'.$path]);
    }
}
