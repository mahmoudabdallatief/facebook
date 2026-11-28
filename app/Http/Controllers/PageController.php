<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $pages = Page::query()
            ->where('type', 'page')
            ->with('owner')
            ->withCount(['members', 'admins'])
            ->visibleTo($user)
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('pages.index', [
            'pages' => $pages,
        ]);
    }

    public function create(): View
    {
        return view('pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:pages,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['sometimes', 'file', 'image', 'max:10240'],
            'avatar' => ['sometimes', 'file', 'image', 'max:5120'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        if (Page::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(4);
        }

        $page = $request->user()->pages()->create([
            'type' => 'page',
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'privacy' => 'public',
        ]);

        $this->attachPageImage($request, $page, 'cover_image');
        $this->attachPageImage($request, $page, 'avatar');

        $page->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'admin',
            'joined_at' => $page->created_at ?? now(),
        ]);

        return redirect()->route('pages.show', $page)
            ->with('status', __('app.page_created'));
    }

    public function show(Page $page, Request $request): View
    {
        $this->authorize('view', $page);
        $user = $request->user();
        $user?->loadMissing(['blocks', 'blockedBy']);
        $page->load(['owner', 'members:id,page_id,user_id,role'])->loadCount(['members', 'admins']);

        $posts = $page->posts()
            ->withFeedRelations($user)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $memberRole = $page->memberRole($user);

        return view('pages.show', [
            'page' => $page,
            'posts' => $posts,
            'memberRole' => $memberRole,
            'isMember' => $memberRole !== null,
        ]);
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:pages,slug,'.$page->id],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'cover_image' => ['sometimes', 'file', 'image', 'max:10240'],
            'avatar' => ['sometimes', 'file', 'image', 'max:5120'],
            'remove_cover' => ['sometimes', 'boolean'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        $page->update([
            'name' => $validated['name'] ?? $page->name,
            'slug' => $validated['slug'] ?? $page->slug,
            'description' => $validated['description'] ?? $page->description,
            'privacy' => 'public',
        ]);

        if ($request->boolean('remove_cover') && $page->cover_image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $page->cover_image));
            $page->update(['cover_image' => null]);
        }

        if ($request->boolean('remove_avatar') && $page->avatar) {
            Storage::disk('public')->delete(str_replace('storage/', '', $page->avatar));
            $page->update(['avatar' => null]);
        }

        $this->attachPageImage($request, $page, 'cover_image');
        $this->attachPageImage($request, $page, 'avatar');

        return redirect()->route('pages.show', $page)
            ->with('status', __('app.page_updated'));
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        if ($page->cover_image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $page->cover_image));
        }
        if ($page->avatar) {
            Storage::disk('public')->delete(str_replace('storage/', '', $page->avatar));
        }

        $page->delete();

        return redirect()->route('pages.index')
            ->with('status', __('app.page_deleted'));
    }

    private function attachPageImage(Request $request, Page $page, string $field): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $path = $request->file($field)->store('pages', 'public');

        $page->update([$field => 'storage/'.$path]);
    }
}
