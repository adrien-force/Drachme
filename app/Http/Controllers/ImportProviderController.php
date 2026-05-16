<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProviders\StoreImportProviderRequest;
use App\Http\Requests\ImportProviders\UpdateImportProviderRequest;
use App\Models\ImportProvider;
use App\Services\ImportProviderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportProviderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ImportProviderService $providers,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ImportProvider::class);

        $providers = ImportProvider::query()
            ->with('defaultAccount:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (ImportProvider $provider): array => $this->serializeProvider($provider));

        return Inertia::render('providers/providers-index', [
            'providers' => $providers,
        ]);
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
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'default_account_id' => $provider->default_account_id,
            'default_account_name' => $provider->defaultAccount?->name,
            'column_mapping' => $provider->column_mapping,
            'csv_options' => $provider->csv_options,
        ];
    }
}
