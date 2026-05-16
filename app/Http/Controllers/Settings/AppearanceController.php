<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AppearanceUpdateRequest;
use App\Support\ThemeColors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/appearance', [
            'colors' => ThemeColors::resolve($request->user()),
            'defaults' => ThemeColors::defaults(),
        ]);
    }

    public function update(AppearanceUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $user->theme_colors = $request->validatedColors();
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.settings.colors_saved'),
        ]);

        return to_route('appearance.edit');
    }

    public function reset(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $user->theme_colors = null;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.settings.colors_reset_done'),
        ]);

        return to_route('appearance.edit');
    }
}
