<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $theme = $request->string('theme')->toString();
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = 'system';
        }

        $request->session()->put('theme', $theme);

        if ($request->user()) {
            $request->user()->update(['theme' => $theme]);
        }

        return redirect()->back(fallback: route('home'));
    }
}
