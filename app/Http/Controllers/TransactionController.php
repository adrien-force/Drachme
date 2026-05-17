<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Transactions\ApplyCategoryRulesRequest;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use App\Http\Requests\Transactions\UpdateTransactionRequest;
use App\Services\CategoryService;
use App\Services\TransactionCategoryRuleApplier;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionFormPresenter;
use App\Services\TransactionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly TransactionFormPresenter $formPresenter,
        private readonly CategoryService $categories,
        private readonly TransactionCategoryRuleApplier $categoryRuleApplier,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();
        if ($user !== null) {
            $this->categories->seedDefaultsForUser($user);
        }

        $query = Transaction::query()
            ->with(['account:id,name,logo_path', 'category:id,name,color']);

        $categoryFilter = $request->input('category_id');
        if ($categoryFilter === 'uncategorized') {
            $query->whereNull('category_id');
        } elseif (is_string($categoryFilter) && $categoryFilter !== '') {
            $query->where('category_id', (int) $categoryFilter);
        }

        $items = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Transaction $transaction): array => $this->formPresenter->serializeTransaction($transaction));

        $transactionEdit = $this->resolveTransactionEditPayload($request);

        return Inertia::render('transactions/transactions-index', [
            'transactions' => $items,
            'categoryOptions' => $user !== null ? $this->categories->flatSelectOptions($user) : [],
            'filters' => [
                'category_id' => is_string($categoryFilter) ? $categoryFilter : null,
            ],
            'transactionEdit' => $transactionEdit ?? null,
            'uncategorizedCount' => $user !== null
                ? $this->categoryRuleApplier->countUncategorized($user)
                : 0,
        ]);
    }

    public function applyCategoryRules(ApplyCategoryRulesRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $accountId = $request->accountId();
        $result = $this->categoryRuleApplier->applyToUncategorized($user, $accountId);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.rules_bulk_applied', [
                'matched' => $result['matched'],
                'scanned' => $result['scanned'],
            ]),
        ]);

        return back();
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        $accountId = $request->integer('account_id');
        $presetAccount = $accountId > 0
            ? Account::query()->active()->find($accountId)
            : null;

        if ($presetAccount !== null) {
            $this->authorize('view', $presetAccount);
        }

        return Inertia::render('transactions/transactions-form', $this->formPresenter->payload(null, $presetAccount));
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        /** @var array{
         *     account_id: int,
         *     date: string,
         *     label: string,
         *     amount: float|string,
         *     type?: string|null,
         *     notes?: string|null,
         *     category_id?: int|null,
         *     apply_category_rules?: bool,
         * } $data */
        $data = $request->validated();

        try {
            $transaction = $this->transactions->create($user, $data);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.created'),
        ]);

        return to_route('accounts.show', $transaction->account_id);
    }

    public function edit(Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        return to_route('accounts.show', [
            'account' => $transaction->account_id,
            'edit_transaction' => $transaction->id,
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        /** @var array{
         *     account_id: int,
         *     date: string,
         *     label: string,
         *     amount: float|string,
         *     type?: string|null,
         *     notes?: string|null,
         *     category_id?: int|null,
         *     apply_category_rules?: bool,
         * } $data */
        $data = $request->validated();

        try {
            $transaction = $this->transactions->update($transaction, $data);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.updated'),
        ]);

        return to_route('accounts.show', $transaction->account_id);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTransactionEditPayload(Request $request): ?array
    {
        $editId = $request->integer('edit_transaction');
        if ($editId <= 0) {
            return null;
        }

        $transaction = Transaction::query()->whereKey($editId)->first();
        if ($transaction === null) {
            return null;
        }

        $this->authorize('update', $transaction);
        $transaction->load(['account:id,name,logo_path', 'category:id,name,color']);

        return $this->formPresenter->payload($transaction, null);
    }

    /**
     * @return array<string, string>
     */
    private function mapServiceError(InvalidArgumentException $exception): array
    {
        $key = $exception->getMessage();

        return match ($key) {
            'transaction_amount_zero' => ['amount' => __('ui.transactions.errors.amount_zero')],
            'transaction_transfer_linked' => ['transaction' => __('ui.transactions.errors.transfer_linked')],
            'transaction_account_forbidden' => ['account_id' => __('ui.transactions.errors.account_forbidden')],
            'transaction_category_forbidden' => ['category_id' => __('ui.transactions.errors.category_forbidden')],
            default => ['transaction' => __('ui.transactions.errors.generic')],
        };
    }
}
