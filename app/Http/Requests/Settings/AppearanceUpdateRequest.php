<?php

namespace App\Http\Requests\Settings;

use App\Support\ThemeColors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AppearanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hex = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'colors' => ['required', 'array'],
            ...collect(ThemeColors::KEYS)
                ->mapWithKeys(fn (string $key) => ["colors.{$key}" => $hex])
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validatedColors(): array
    {
        /** @var array<string, string> $colors */
        $colors = $this->validated('colors');

        return array_map(
            static fn (string $hex): string => strtolower($hex),
            $colors,
        );
    }
}
