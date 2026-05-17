<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Http\Requests\Accounts\ShowAccountRequest;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AccountBalanceHistoryService;
use App\Services\AccountService;
use App\Services\BalanceEngine;
use App\Services\TransactionListService;
use App\Services\CategoryService;
use App\Services\TransactionCategoryRuleApplier;
use App\Services\TransactionFormPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccountService $accounts,
        private readonly AccountBalanceHistoryService $balanceHistory,
        private readonly TransactionListService $transactionList,
        private readonly CategoryService $categories,
        private readonly TransactionFormPresenter $transactionForm,
        private readonly TransactionCategoryRuleApplier $categoryRuleApplier,
        private readonly BalanceEngine $balanceEngine,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        $showArchived = $request->boolean('archived');

        $query = Account::query()
            ->withMax('transactions', 'date')
            ->orderBy('name');

        if (! $showArchived) {
            $query->active();
        }

        $accounts = $query
            ->get()
            ->map(fn (Account $account): array => $this->serializeAccount($account));

        return Inertia::render('accounts/accounts-index', [
            'accounts' => $accounts,
            'filters' => [
                'archived' => $showArchived,
            ],
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function show(ShowAccountRequest $request, Account $account): Response
    {
        $this->authorize('view', $account);

        $account->loadMax('transactions', 'date');

        $transactionFilters = $request->transactionFilters();
        $paginator = $this->transactionList->paginateForAccount($account, $transactionFilters);

        $user = $request->user();
        if ($user !== null) {
            $this->categories->seedDefaultsForUser($user);
        }

        return Inertia::render('accounts/accounts-show', [
            'account' => $this->serializeAccount($account),
            'transactions' => [
                'data' => collect($paginator->items())
                    ->map(fn (Transaction $transaction): array => $this->serializeTransaction($transaction))
                    ->values()
                    ->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            'transactionFilters' => $request->transactionFiltersForFrontend(),
            'transactionTypeOptions' => $this->transactionTypeOptions(),
            'categoryOptions' => $user !== null ? $this->categories->flatSelectOptions($user) : [],
            'perPageOptions' => ShowAccountRequest::perPageOptions(),
            'balanceHistory' => $this->balanceHistory->build(
                $account,
                $request->chartFrom(),
                $request->chartTo(),
                $request->chartAllTime(),
            ),
            'transactionEdit' => $this->resolveTransactionEditForAccount($request, $account),
            'uncategorizedCount' => $user !== null
                ? $this->categoryRuleApplier->countUncategorized($user, $account->id)
                : 0,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTransactionEditForAccount(ShowAccountRequest $request, Account $account): ?array
    {
        $editId = $request->editTransactionId();
        if ($editId === null) {
            return null;
        }

        $transaction = Transaction::query()
            ->where('account_id', $account->id)
            ->whereKey($editId)
            ->first();

        if ($transaction === null) {
            return null;
        }

        $this->authorize('update', $transaction);
        $transaction->load(['account:id,name,logo_path', 'category:id,name,color']);

        return $this->transactionForm->payload($transaction, null);
    }

    public function create(): Response
    {
        $this->authorize('create', Account::class);

        return Inertia::render('accounts/accounts-form', [
            'account' => null,
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        /** @var array{
         *     name: string,
         *     institution?: string|null,
         *     type: AccountType|string,
         *     initial_balance: float|string,
         *     opened_at?: string|null,
         * } $data */
        $data = $request->validated();

        $account = $this->accounts->create($user, $data, $request->file('logo'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.created'),
        ]);

        return to_route('accounts.show', $account);
    }

    public function edit(Account $account): Response
    {
        $this->authorize('update', $account);
        $this->balanceEngine->recalculateAccount($account);
        $account->refresh();

        return Inertia::render('accounts/accounts-form', [
            'account' => $this->serializeAccount($account),
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        /** @var array{
         *     name: string,
         *     institution?: string|null,
         *     type: AccountType|string,
         *     opened_at?: string|null,
         *     actual_balance?: float|string|null,
         * } $data */
        $data = $request->validated();

        $this->accounts->update(
            $account,
            $data,
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.updated'),
        ]);

        return to_route('accounts.show', $account);
    }

    public function archive(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->accounts->archive($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.archived'),
        ]);

        return to_route('accounts.index');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->accounts->delete($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.deleted'),
        ]);

        return to_route('accounts.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAccount(Account $account): array
    {
        $type = $account->type;
        $openedAt = $account->opened_at;

        return [
            'id' => $account->id,
            'name' => $account->name,
            'logo_url' => $this->accounts->logoUrl($account),
            'institution' => $account->institution,
            'type' => $type instanceof AccountType ? $type->value : (string) $type,
            'initial_balance' => (float) $account->initial_balance,
            'current_balance' => (float) $account->current_balance,
            'transactions_net' => (float) $this->balanceEngine->transactionSum($account),
            'currency' => $account->currency,
            'opened_at' => $openedAt instanceof \DateTimeInterface
                ? $openedAt->format('Y-m-d')
                : ($openedAt !== null ? \Illuminate\Support\Carbon::parse((string) $openedAt)->format('Y-m-d') : null),
            'is_archived' => $account->is_archived,
            'last_activity_at' => $this->formatDateField($account->transactions_max_date),
        ];
    }

    private function formatDateField(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(Transaction $transaction): array
    {
        $type = $transaction->type;
        $date = $transaction->date;
        $category = $transaction->category;

        return [
            'id' => $transaction->id,
            'date' => $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : (string) $date,
            'label' => $transaction->label,
            'amount' => (float) $transaction->amount,
            'type' => $type instanceof TransactionType ? $type->value : (string) $type,
            'is_transfer_linked' => $transaction->transfer_pair_id !== null,
            'category_id' => $transaction->category_id,
            'category_name' => $category?->name,
            'category_color' => $category?->color,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function accountTypeOptions(): array
    {
        return array_values(array_map(
            static fn (AccountType $type): array => [
                'value' => $type->value,
                'label' => (string) __("ui.accounts.types.{$type->value}"),
            ],
            AccountType::cases(),
        ));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function transactionTypeOptions(): array
    {
        return array_values(array_map(
            static fn (TransactionType $type): array => [
                'value' => $type->value,
                'label' => (string) __("ui.transactions.types.{$type->value}"),
            ],
            TransactionType::cases(),
        ));
    }
}
