<?php

declare(strict_types=1);

namespace App\Http\Requests\ImportProviders;

use App\Http\Requests\ImportProviders\Concerns\ValidatesImportProviderPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImportProviderRequest extends FormRequest
{
    use ValidatesImportProviderPayload;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('importProvider')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->importProviderPayloadRules();
    }
}
