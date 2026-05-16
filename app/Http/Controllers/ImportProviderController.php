<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ImportColumnField;
use App\Http\Requests\ImportProviders\PreviewImportProviderRequest;
use App\Http\Requests\ImportProviders\StoreImportProviderRequest;
use App\Http\Requests\ImportProviders\UpdateImportProviderRequest;
use App\Models\Account;
use App\Models\ImportProvider;
use App\Services\AccountService;
use App\Services\ImportProviderService;
use App\Support\DateFormatDetector;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportProviderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ImportProviderService $providers,
        private readonly AccountService $accounts,
        private readonly DateFormatDetector $dateFormats,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ImportProvider::class);

        $providers = ImportProvider::query()
            ->with('defaultAccount:id,name,logo_path')
            ->orderBy('name')
            ->get()
            ->map(fn (ImportProvider $provider): array => $this->serializeProvider($provider));

        return Inertia::render('providers/providers-index', [
            'providers' => $providers,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ImportProvider::class);

        return Inertia::render('providers/providers-form', $this->formPayload(null));
    }

    public function show(ImportProvider $importProvider): Response
    {
        $this->authorize('view', $importProvider);

        $importProvider->load('defaultAccount:id,name,logo_path');

        return Inertia::render('providers/providers-show', $this->formPayload($importProvider));
    }

    public function edit(ImportProvider $importProvider): Response
    {
        $this->authorize('update', $importProvider);

        $importProvider->load('defaultAccount:id,name,logo_path');

        return Inertia::render('providers/providers-form', $this->formPayload($importProvider));
    }

    public function detectDateFormat(Request $request): JsonResponse
    {
        $this->authorize('create', ImportProvider::class);

        /** @var array{samples: list<string|null>} $validated */
        $validated = $request->validate([
            'samples' => ['required', 'array', 'min:1'],
            'samples.*' => ['nullable', 'string'],
        ]);

        return response()->json([
            'suggestion' => $this->dateFormats->detect($validated['samples']),
        ]);
    }

    public function preview(PreviewImportProviderRequest $request): JsonResponse
    {
        /** @var list<list<string|null>> $sampleRows */
        $sampleRows = $request->validated('sample_rows');

        /** @var array<string, mixed> $columnMapping */
        $columnMapping = $request->validated('column_mapping');

        /** @var array<string, mixed>|null $csvOptions */
        $csvOptions = $request->validated('csv_options');

        $rows = $this->providers->previewNormalizedRows(
            $sampleRows,
            $columnMapping,
            $csvOptions ?? [],
        );

        return response()->json(['rows' => $rows]);
    }

    public function store(StoreImportProviderRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        /** @var array{
         *     name: string,
         *     default_account_id?: int|null,
         *     column_mapping: array<string, mixed>,
         *     csv_options?: array<string, mixed>|null,
         * } $data */
        $data = $request->validated();

        $this->providers->create($user, $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.providers.created'),
        ]);

        return to_route('providers.index');
    }

    public function update(UpdateImportProviderRequest $request, ImportProvider $importProvider): RedirectResponse
    {
        $this->authorize('update', $importProvider);

        /** @var array{
         *     name: string,
         *     default_account_id?: int|null,
         *     column_mapping: array<string, mixed>,
         *     csv_options?: array<string, mixed>|null,
         * } $data */
        $data = $request->validated();

        $this->providers->update($importProvider, $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.providers.updated'),
        ]);

        return to_route('providers.index');
    }

    public function destroy(ImportProvider $importProvider): RedirectResponse
    {
        $this->authorize('delete', $importProvider);

        $this->providers->delete($importProvider);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.providers.deleted'),
        ]);

        return to_route('providers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProvider(ImportProvider $provider): array
    {
        $account = $provider->defaultAccount;

        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'logo_url' => $account !== null ? $this->accounts->logoUrl($account) : null,
            'default_account_id' => $provider->default_account_id,
            'default_account_name' => $account?->name,
            'column_mapping' => $provider->column_mapping,
            'csv_options' => $provider->csv_options,
            'updated_at' => $provider->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(?ImportProvider $provider): array
    {
        $accounts = Account::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'logo_path'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'logo_url' => $this->accounts->logoUrl($account),
            ])
            ->values()
            ->all();

        return [
            'provider' => $provider !== null ? $this->serializeProvider($provider) : null,
            'accounts' => $accounts,
            'fieldOptions' => $this->fieldOptions(),
            'defaultCsvOptions' => $this->providers->defaultCsvOptions(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function fieldOptions(): array
    {
        return array_values(array_map(
            static fn (ImportColumnField $field): array => [
                'value' => $field->value,
                'label' => (string) __("ui.providers.fields.{$field->value}"),
            ],
            ImportColumnField::cases(),
        ));
    }
}
