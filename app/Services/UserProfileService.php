<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\BillingPeriod;
use App\Support\LogoUploadService;
use Illuminate\Http\UploadedFile;

class UserProfileService
{
    public function __construct(
        private readonly LogoUploadService $logos,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     locale: string,
     *     month_start_day?: int|null,
     * }  $data
     */
    public function update(
        User $user,
        array $data,
        ?UploadedFile $avatar = null,
        bool $removeAvatar = false,
    ): User {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => $data['locale'],
        ]);

        if (array_key_exists('month_start_day', $data)) {
            $user->month_start_day = BillingPeriod::normalizeStartDay((int) $data['month_start_day']);
        }

        $user->avatar_path = $this->logos->sync(
            $user->avatar_path,
            $avatar,
            $removeAvatar,
            "logos/users/{$user->id}",
        );

        $user->save();

        return $user;
    }

    public function avatarUrl(User $user): ?string
    {
        return $this->logos->url($user->avatar_path);
    }
}
