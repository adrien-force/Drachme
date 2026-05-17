<?php

declare(strict_types=1);


namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Http\Requests\Concerns\ResolvesAuthenticatedUser;
use App\Http\Requests\Concerns\ValidatesEntityLogo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;
    use ResolvesAuthenticatedUser;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->authenticatedUser()->id),
            'locale' => $this->localeRules(),
            'month_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
