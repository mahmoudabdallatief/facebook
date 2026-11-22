<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use App\Models\PostMedia;
use App\Services\CommunityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GroupPostController extends Controller
{
    public function index(Group $group, Request $request): RedirectResponse
    {
        $this->authorize('view', $group);

        return redirect()->route('groups.show', $group);
    }

    public function create(Group $group): View
    {
        $this->authorize('create', Post::class);

        if (! $group->isMember(auth()->user())) {
            abort(403, __('app.group_members_only'));
        }

        return view('groups.posts.create', [
            'group' => $group,
        ]);
    }

    public function store(Request $request, Group $group, CommunityNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Post::class);

        if (! $group->isMember(auth()->user())) {
            abort(403, __('app.group_members_only'));
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi,mkv', 'max:2097152'],
            'media_ids' => ['sometimes', 'array', 'max:10'],
            'media_ids.*' => ['integer', 'exists:post_media,id'],
        ]);
        $this->validateMediaLimit($request, $validated);

        $post = $group->posts()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        $this->attachMedia($request, $post);

        if ($request->has('media_ids')) {
            $this->attachExistingMedia($request, $post);
        }

        $post->load(['user', 'media']);

        // Notify group admins/moderators
        $notifications->notifyPostAudience($post, $request->user(), 'post_created', [
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'post_id' => $post->id,
            'group_id' => $group->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.post_created'),
                'post' => $post,
            ]);
        }

        return redirect()->route('groups.show', $group)
            ->with('status', __('app.post_created'));
    }

    public function show(Group $group, Post $post): View
    {
        $this->authorize('view', $post);

        if ($post->group_id !== $group->id) {
            abort(404);
        }

        $post->load(['user', 'media', 'group', 'originalPost.user', 'originalPost.media', 'originalPost.group', 'originalPost.page']);

        $me = auth()->user();
        $memberRole = $group->memberRole($me);
        $canManage = $group->canManage($me);

        return view('groups.posts.show', [
            'group' => $group,
            'post' => $post,
            'memberRole' => $memberRole,
            'canManage' => $canManage,
        ]);
    }

    public function edit(Group $group, Post $post): View
    {
        $this->authorize('update', $post);

        if ($post->group_id !== $group->id) {
            abort(404);
        }

        $post->load('media');

        return view('groups.posts.edit', [
            'group' => $group,
            'post' => $post,
        ]);
    }

    public function update(Request $request, Group $group, Post $post): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $post);

        if ($post->group_id !== $group->id) {
            abort(404);
        }

        if ($post->isShare()) {
            $validated = $request->validate([
                'body' => ['nullable', 'string', 'max:2000'],
                'visibility' => ['sometimes', 'in:public,friends'],
            ]);

            $post->update([
                'body' => $validated['body'] ?? null,
                'visibility' => $validated['visibility'] ?? $post->visibility,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('app.post_updated'),
                    'post' => $post->load(['user', 'originalPost.user', 'originalPost.media']),
                ]);
            }

            return redirect()->route('groups.posts.show', ['group' => $group, 'post' => $post])
                ->with('status', __('app.post_updated'));
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi,mkv', 'max:2097152'],
            'media_ids' => ['sometimes', 'array', 'max:10'],
            'media_ids.*' => ['integer', 'exists:post_media,id'],
            'keep_media' => ['sometimes', 'array', 'max:10'],
            'keep_media.*' => ['integer', 'min:1'],
        ]);
        $this->validateMediaLimit($request, $validated, $post);

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

        return redirect()->route('groups.posts.show', ['group' => $group, 'post' => $post])
            ->with('status', __('app.post_updated'));
    }

    public function destroy(Request $request, Group $group, Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        if ($post->group_id !== $group->id) {
            abort(404);
        }

        foreach ($post->media as $m) {
            if ($m->disk === 'public') {
                Storage::disk('public')->delete($m->path);
            }
        }

        $post->delete();

        return redirect()->route('groups.show', $group)
            ->with('status', __('app.post_deleted'));
    }

    private function attachMedia(Request $request, Post $post): void
    {
        if (! $request->hasFile('media')) {
            return;
        }

        $existing = $post->media()->max('order') ?? 0;

        foreach ($request->file('media') as $index => $file) {
            $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
            $path = $file->store('groups/posts', 'public');
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
            ->where('user_id', $request->user()->id)
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

    private function validateMediaLimit(Request $request, array $validated, ?Post $post = null): void
    {
        $mediaIds = array_values(array_unique($validated['media_ids'] ?? []));
        if ($mediaIds && PostMedia::whereIn('id', $mediaIds)->where('user_id', $request->user()->id)->whereNull('post_id')->where('status', 'completed')->count() !== count($mediaIds)) {
            throw ValidationException::withMessages(['media' => __('app.upload_invalid_selection')]);
        }

        $newCount = count($request->file('media', [])) + count($mediaIds);
        $keptCount = $post
            ? $post->media()->whereIn('id', $validated['keep_media'] ?? [])->count()
            : 0;

        if ($newCount + $keptCount > 10) {
            throw ValidationException::withMessages(['media' => __('app.upload_max_files')]);
        }
    }

}
