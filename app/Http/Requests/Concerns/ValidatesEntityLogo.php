<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait ValidatesEntityLogo
{
    /**
     * @return array<string, mixed>
     */
    protected function entityLogoRules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
