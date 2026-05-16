<?php

declare(strict_types=1);

namespace App\Http\Requests\ImportProviders;

use App\Http\Requests\ImportProviders\Concerns\ValidatesImportProviderPayload;
use App\Models\ImportProvider;
use Illuminate\Foundation\Http\FormRequest;

class StoreImportProviderRequest extends FormRequest
{
    use ValidatesImportProviderPayload;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ImportProvider::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->importProviderPayloadRules();
    }
}
