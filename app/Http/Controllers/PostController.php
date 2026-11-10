<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $posts = Post::query()
            ->with(['user', 'media'])
            ->visibleTo($user)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('posts.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('posts.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm', 'max:51200'],
            'media_ids' => ['sometimes', 'array', 'max:10'],
            'media_ids.*' => ['integer', 'exists:post_media,id'],
        ]);

        $post = $request->user()->posts()->create([
            'body' => $validated['body'],
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        $this->attachMedia($request, $post);

        if ($request->has('media_ids')) {
            $this->attachExistingMedia($request, $post);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.post_created'),
                'post' => $post->load(['user', 'media']),
            ]);
        }

        return redirect()->route('posts.index')
            ->with('status', __('app.post_created'));
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        $post->load('media');

        return view('posts.edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm', 'max:51200'],
            'media_ids' => ['sometimes', 'array', 'max:10'],
            'media_ids.*' => ['integer', 'exists:post_media,id'],
            'keep_media' => ['sometimes', 'array'],
            'keep_media.*' => ['integer', 'min:1'],
        ]);

        $post->update([
            'body' => $validated['body'],
            'visibility' => $validated['visibility'] ?? $post->visibility,
        ]);

        if ($request->has('keep_media')) {
            $keep = $request->input('keep_media', []);
            $post->media()->whereNotIn('id', $keep)->each(function (PostMedia $m): void {
                if (! str_starts_with($m->url, 'http://') && ! str_starts_with($m->url, 'https://')) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $m->url));
                }
                $m->delete();
            });
        } else {
            $post->media()->each(function (PostMedia $m): void {
                if (! str_starts_with($m->url, 'http://') && ! str_starts_with($m->url, 'https://')) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $m->url));
                }
                $m->delete();
            });
        }

        $this->attachMedia($request, $post);

        if ($request->has('media_ids')) {
            $this->attachExistingMedia($request, $post);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.post_updated'),
                'post' => $post->load(['user', 'media']),
            ]);
        }

        return redirect()->route('posts.index')
            ->with('status', __('app.post_updated'));
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        foreach ($post->media as $m) {
            if ($m->disk === 'public') {
                Storage::disk('public')->delete($m->path);
            }
        }

        $post->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.post_deleted'),
            ]);
        }

        return redirect()->route('posts.index')
            ->with('status', __('app.post_deleted'));
    }

    public function show(Post $post): View
    {
        $this->authorize('view', $post);

        $post->load(['user', 'media']);

        return view('posts.show', [
            'post' => $post,
        ]);
    }

    private function attachMedia(Request $request, Post $post): void
    {
        if (! $request->hasFile('media')) {
            return;
        }

        $existing = $post->media()->max('order') ?? 0;

        foreach ($request->file('media') as $index => $file) {
            $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
            $path = $file->store('posts', 'public');
            $post->media()->create([
                'type' => $type,
                'disk' => 'public',
                'path' => $path,
                'url' => 'storage/'.$path,
                'order' => $existing + $index + 1,
                'status' => 'completed',
            ]);
        }
    }

    private function attachExistingMedia(Request $request, Post $post): void
    {
        $mediaIds = $request->input('media_ids', []);

        PostMedia::whereIn('id', $mediaIds)
            ->whereNull('post_id')
            ->where('status', 'completed')
            ->get()
            ->each(function (PostMedia $media) use ($post) {
                if ($media->post_id === null) {
                    $maxOrder = $post->media()->max('order') ?? 0;
                    $media->update([
                        'post_id' => $post->id,
                        'order' => $maxOrder + 1,
                    ]);
                }
            });
    }
}
