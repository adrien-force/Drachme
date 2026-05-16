<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Import\CommitImportBatchRequest;
use App\Http\Requests\Import\ParseImportBatchRequest;
use App\Http\Requests\Import\StoreImportBatchRequest;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Services\AccountService;
use App\Services\ImportBatchService;
use App\Services\ImportProviderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ImportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ImportBatchService $imports,
        private readonly AccountService $accounts,
        private readonly ImportProviderService $importProviders,
    ) {}

    public function create(Request $request): Response
    {
        $this->authorize('create', ImportBatch::class);

        $batch = null;
        $batchId = $request->integer('batch');
        if ($batchId > 0) {
            $batch = ImportBatch::query()
                ->with(['importProvider', 'account'])
                ->find($batchId);
            if ($batch !== null) {
                $this->authorize('view', $batch);
            }
        }

        return Inertia::render('import/import-wizard', $this->wizardPayload($batch));
    }

    public function store(StoreImportBatchRequest $request): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $provider = ImportProvider::query()->findOrFail($request->integer('import_provider_id'));
        $account = Account::query()->findOrFail($request->integer('account_id'));

        $batch = $this->imports->createDraft($user, $provider, $account);

        return redirect()->route('import.show', $batch);
    }

    public function show(ImportBatch $importBatch): Response
    {
        $this->authorize('view', $importBatch);
        $importBatch->load(['importProvider', 'account']);
        $importBatch->account?->refresh();

        return Inertia::render('import/import-wizard', $this->wizardPayload($importBatch));
    }

    public function parse(ParseImportBatchRequest $request, ImportBatch $importBatch): RedirectResponse
    {
        $this->authorize('update', $importBatch);

        try {
            $this->imports->buildPreview($importBatch, $request->file('file'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'file' => __('ui.import.errors.'.$exception->getMessage(), [], app()->getLocale()),
            ]);
        }

        return redirect()->route('import.show', $importBatch);
    }

    public function commit(CommitImportBatchRequest $request, ImportBatch $importBatch): RedirectResponse
    {
        $this->authorize('update', $importBatch);

        /** @var list<array{line: int, action: string}> $decisions */
        $decisions = $request->validated('decisions') ?? [];

        try {
            $this->imports->commit($importBatch, $decisions);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'import' => __('ui.import.errors.'.$exception->getMessage(), [], app()->getLocale()),
            ]);
        }

        return redirect()->route('import.show', $importBatch->fresh());
    }

    public function destroy(ImportBatch $importBatch): RedirectResponse
    {
        $this->authorize('delete', $importBatch);
        $this->imports->cancel($importBatch);

        return redirect()->route('import.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function wizardPayload(?ImportBatch $batch): array
    {
        $userId = Auth::id();

        $providers = ImportProvider::query()
            ->with('defaultAccount')
            ->orderBy('name')
            ->get()
            ->map(fn (ImportProvider $provider): array => [
                'id' => $provider->id,
                'name' => $provider->name,
                'default_account_id' => $provider->default_account_id,
                'logo_url' => $provider->defaultAccount !== null
                    ? $this->accounts->logoUrl($provider->defaultAccount)
                    : null,
            ]);

        $accounts = Account::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution,
                'logo_url' => $this->accounts->logoUrl($account),
            ]);

        return [
            'providers' => $providers,
            'accounts' => $accounts,
            'batch' => $batch !== null ? $this->serializeBatch($batch) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBatch(ImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'status' => $batch->status->value,
            'import_provider_id' => $batch->import_provider_id,
            'account_id' => $batch->account_id,
            'provider_name' => $batch->importProvider?->name,
            'account_name' => $batch->account?->name,
            'account_current_balance' => $batch->account?->current_balance,
            'original_filename' => $batch->original_filename,
            'preview_rows' => $batch->preview_rows ?? [],
            'maps_balance' => $batch->importProvider !== null
                && $this->importProviders->mapsBalanceColumn($batch->importProvider),
            'imported_count' => $batch->imported_count,
            'skipped_count' => $batch->skipped_count,
            'replaced_count' => $batch->replaced_count,
            'error_count' => $batch->error_count,
            'completed_at' => $batch->completed_at?->toIso8601String(),
        ];
    }
}
