<?php

declare(strict_types=1);


namespace App\Http\Requests\ImportProviders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DetectDateFormatRequest extends FormRequest
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
        return [
            'samples' => ['required', 'array', 'min:1', 'max:20'],
            'samples.*' => ['nullable', 'string', 'max:64'],
        ];
    }
}
