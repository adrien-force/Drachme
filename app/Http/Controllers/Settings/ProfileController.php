<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\UserProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function __construct(
        private readonly UserProfileService $profiles,
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profile' => [
                'avatar_url' => $this->profiles->avatarUrl($user),
                'month_start_day' => (int) ($user->month_start_day ?? 1),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        /** @var array{
         *     name: string,
         *     email: string,
         *     locale: string,
         *     month_start_day: int,
         * } $data */
        $data = $request->validated();
        $emailChanged = $user->email !== $data['email'];

        $this->profiles->update(
            $user,
            $data,
            $request->file('avatar'),
            $request->boolean('remove_avatar'),
        );

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('ui.settings.saved')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
