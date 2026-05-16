<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class ParseImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }
}
