<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use App\Enums\ImportDuplicateAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommitImportBatchRequest extends FormRequest
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
            'decisions' => ['nullable', 'array'],
            'decisions.*.line' => ['required', 'integer', 'min:1'],
            'decisions.*.action' => [
                'required',
                'string',
                Rule::enum(ImportDuplicateAction::class),
            ],
        ];
    }
}
