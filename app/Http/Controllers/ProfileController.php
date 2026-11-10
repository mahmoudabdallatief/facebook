<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', File::image()->max(2048)],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar));
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = 'storage/'.$path;
        }

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.profile_updated'),
                'user' => [
                    'name' => $user->name,
                    'avatar' => $user->avatarUrl(),
                    'bio' => $user->bio,
                ],
            ]);
        }

        return redirect()->route('dashboard')
            ->with('status', __('app.profile_updated'));
    }

    public function show(User $user): View
    {
        return view('profile.show', [
            'profileUser' => $user,
        ]);
    }
}
