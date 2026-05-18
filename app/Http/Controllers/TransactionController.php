<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Transactions\ApplyCategoryRulesRequest;
use App\Http\Requests\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use App\Http\Requests\Transactions\UpdateTransactionCategoryRequest;
use App\Http\Requests\Transactions\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Http\Requests\Transactions\MarkTransactionRecurringRequest;
use App\Services\RecurringPatternService;
use App\Services\TransactionCategoryRuleApplier;
use App\Services\TransactionFormPresenter;
use App\Services\TransactionListService;
use App\Services\TransactionSankeyService;
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
        private readonly TransactionListService $transactionList,
        private readonly TransactionSankeyService $transactionSankey,
        private readonly CategoryService $categories,
        private readonly TransactionCategoryRuleApplier $categoryRuleApplier,
        private readonly RecurringPatternService $recurringPatterns,
        private readonly AccountService $accounts,
    ) {}

    public function index(IndexTransactionsRequest $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();
        if ($user !== null) {
            $this->categories->seedDefaultsForUser($user);
        }

        $listFilters = $request->listFilters();
        $paginator = $user !== null
            ? $this->transactionList->paginateForUser($user, $listFilters)
            : null;

        $transactionEdit = $this->resolveTransactionEditPayload($request);

        $accountOptions = $user !== null
            ? Account::query()
                ->active()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'logo_path'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'logo_url' => $this->accounts->logoUrl($account),
                ])
                ->values()
                ->all()
            : [];

        return Inertia::render('transactions/transactions-index', [
            'transactions' => $paginator !== null
                ? $this->formPresenter->serializePaginator($paginator)
                : ['data' => [], 'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => IndexTransactionsRequest::perPageOptions()[1],
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ]],
            'categoryOptions' => $user !== null ? $this->categories->flatSelectOptions($user) : [],
            'accountOptions' => $accountOptions,
            'filters' => $request->listFiltersForFrontend(),
            'transactionEdit' => $transactionEdit ?? null,
            'uncategorizedCount' => $user !== null
                ? $this->categoryRuleApplier->countUncategorized($user)
                : 0,
            'perPageOptions' => IndexTransactionsRequest::perPageOptions(),
            'typeOptions' => $this->transactionTypeOptions(),
            'sankeyFlow' => $user !== null
                ? $this->transactionSankey->buildForUser($user, $listFilters)
                : ['nodes' => [], 'links' => []],
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

    public function markRecurring(
        MarkTransactionRecurringRequest $request,
        Transaction $transaction,
    ): RedirectResponse {
        $this->authorize('update', $transaction);

        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $this->recurringPatterns->confirmFromTransaction(
                $user,
                $transaction,
                $request->frequency(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapRecurringError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.marked_recurring'),
        ]);

        return back();
    }

    public function unmarkRecurring(Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $user = Auth::user();
        abort_if($user === null, 403);

        try {
            $this->recurringPatterns->removeForTransaction($user, $transaction);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapRecurringError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.unmarked_recurring'),
        ]);

        return back();
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        $accountId = $request->integer('account_id');
        $presetAccount = $accountId > 0
            ? Account::query()->active()->whereKey($accountId)->first()
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

    public function updateCategory(
        UpdateTransactionCategoryRequest $request,
        Transaction $transaction,
    ): RedirectResponse {
        try {
            $this->transactions->updateCategory($transaction, $request->categoryId());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapServiceError($exception));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.category_updated'),
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTransactionEditPayload(IndexTransactionsRequest $request): ?array
    {
        $editId = $request->editTransactionId();
        if ($editId === null) {
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
     * @return list<array{value: string, label: string}>
     */
    private function transactionTypeOptions(): array
    {
        return array_values(array_map(
            static fn (\App\Enums\TransactionType $type): array => [
                'value' => $type->value,
                'label' => (string) __("ui.transactions.types.{$type->value}"),
            ],
            \App\Enums\TransactionType::cases(),
        ));
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

    /**
     * @return array<string, string>
     */
    private function mapRecurringError(InvalidArgumentException $exception): array
    {
        $key = $exception->getMessage();
        $messageKey = 'ui.recurring.errors.'.$key;
        $translated = __($messageKey);

        $message = $translated !== $messageKey
            ? $translated
            : __('ui.transactions.errors.generic');

        return ['transaction' => $message];
    }
}
