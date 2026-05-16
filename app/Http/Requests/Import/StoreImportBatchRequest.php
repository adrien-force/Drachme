<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use App\Models\Account;
use App\Models\ImportProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportBatchRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'import_provider_id' => [
                'required',
                'integer',
                Rule::exists('import_providers', 'id')->where('user_id', $userId),
            ],
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
        ];
    }
}
