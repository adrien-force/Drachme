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
use App\Services\CreditCardSettlementService;
use App\DataTransferObjects\CreditCardSettlementSyncResult;
use App\Services\CreditCardSettlementSyncService;
use App\Enums\SettlementPeriodMode;
use App\Services\BalanceEngine;
use App\Services\PortfolioValuationService;
use App\Services\TransactionListService;
use App\Services\CategoryService;
use App\Services\TransactionCategoryRuleApplier;
use App\Services\TransactionFormPresenter;
use App\Support\AccountNetWorth;
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
        private readonly CreditCardSettlementService $creditCardSettlements,
        private readonly CreditCardSettlementSyncService $creditCardSettlementSync,
        private readonly PortfolioValuationService $portfolioValuation,
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

        $account->load(['settlementAccount:id,name,type']);
        $account->loadMax('transactions', 'date');

        $transactionFilters = $request->transactionFilters();
        $paginator = $this->transactionList->paginateForAccount($account, $transactionFilters);

        $user = $request->user();
        if ($user !== null) {
            $this->categories->seedDefaultsForUser($user);
        }

        $accountType = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);
        $isCreditCard = $accountType === AccountType::CreditCard;
        $isInvest = $accountType === AccountType::Invest;

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
            'categoryOptions' => $user !== null ? $this->categories->flatSelectableOptions($user) : [],
            'perPageOptions' => ShowAccountRequest::perPageOptions(),
            'balanceHistory' => $isCreditCard || $isInvest
                ? null
                : $this->balanceHistory->build(
                    $account,
                    $request->chartFrom(),
                    $request->chartTo(),
                    $request->chartAllTime(),
                ),
            'creditCardSettlements' => $isCreditCard
                ? $this->creditCardSettlements->build($account)
                : null,
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

        $user = request()->user();

        return Inertia::render('accounts/accounts-form', [
            'account' => null,
            'accountTypes' => $this->accountTypeOptions(),
            'settlementAccountOptions' => $user !== null
                ? $this->settlementAccountOptions($user)
                : [],
            'settlementPeriodModeOptions' => $this->settlementPeriodModeOptions(),
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
        $this->maybeSyncCreditCardSettlements($account);

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

        $user = request()->user();

        return Inertia::render('accounts/accounts-form', [
            'account' => $this->serializeAccount($account),
            'accountTypes' => $this->accountTypeOptions(),
            'settlementAccountOptions' => $user !== null
                ? $this->settlementAccountOptions($user)
                : [],
            'settlementPeriodModeOptions' => $this->settlementPeriodModeOptions(),
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

        $account = $this->accounts->update(
            $account,
            $data,
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );
        $syncResult = $this->maybeSyncCreditCardSettlements($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $syncResult !== null
                ? __('ui.accounts.settlement_sync_toast', [
                    'linked' => $syncResult->linkedPairs,
                    'marked' => $syncResult->markedSettlements,
                ])
                : __('ui.accounts.updated'),
        ]);

        return to_route('accounts.show', $account);
    }

    public function syncSettlements(Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $account->refresh();
        $result = $this->creditCardSettlementSync->syncForCard($account);

        if ($result->skippedMissingConfig > 0) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('ui.accounts.settlement_sync_missing_config'),
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('ui.accounts.settlement_sync_toast', [
                    'linked' => $result->linkedPairs,
                    'marked' => $result->markedSettlements,
                ]),
            ]);
        }

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
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);
        $openedAt = $account->opened_at;
        $currentBalance = (float) $account->current_balance;

        return [
            'id' => $account->id,
            'name' => $account->name,
            'logo_url' => $this->accounts->logoUrl($account),
            'institution' => $account->institution,
            'type' => $type->value,
            'initial_balance' => (float) $account->initial_balance,
            'current_balance' => $currentBalance,
            'amount_owed' => $type === AccountType::CreditCard
                ? AccountNetWorth::creditCardAmountOwed($currentBalance)
                : null,
            'current_period_spend' => $type === AccountType::CreditCard
                ? $this->creditCardSettlements->currentPeriodSpend($account)
                : null,
            'transactions_net' => (float) $this->balanceEngine->transactionSum($account),
            'currency' => $account->currency,
            'opened_at' => $openedAt instanceof \DateTimeInterface
                ? $openedAt->format('Y-m-d')
                : ($openedAt !== null ? \Illuminate\Support\Carbon::parse((string) $openedAt)->format('Y-m-d') : null),
            'is_archived' => $account->is_archived,
            'last_activity_at' => $this->formatDateField($account->transactions_max_date),
            'settlement_account_id' => $account->settlement_account_id,
            'billing_day' => $account->billing_day,
            'settlement_label_pattern' => $account->settlement_label_pattern,
            'settlement_period_mode' => $account->settlement_period_mode instanceof SettlementPeriodMode
                ? $account->settlement_period_mode->value
                : (string) ($account->settlement_period_mode ?? SettlementPeriodMode::SinceLastSettlement->value),
            'settlement_account' => $account->relationLoaded('settlementAccount') && $account->settlementAccount !== null
                ? [
                    'id' => $account->settlementAccount->id,
                    'name' => $account->settlementAccount->name,
                ]
                : null,
            'positions_value' => $type === AccountType::Invest
                ? $this->portfolioValuation->totalForAccount($account)
                : null,
        ];
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function settlementAccountOptions(\App\Models\User $user): array
    {
        /** @var list<array{value: int, label: string}> $options */
        $options = Account::query()
            ->where('user_id', $user->id)
            ->active()
            ->where('type', AccountType::Checking)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Account $account): array => [
                'value' => $account->id,
                'label' => $account->name,
            ])
            ->values()
            ->all();

        return $options;
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
            'is_card_settlement' => (bool) $transaction->is_card_settlement,
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

    /**
     * @return list<array{value: string, label: string}>
     */
    private function settlementPeriodModeOptions(): array
    {
        return array_values(array_map(
            static fn (SettlementPeriodMode $mode): array => [
                'value' => $mode->value,
                'label' => (string) __("ui.accounts.settlement_period_modes.{$mode->value}"),
            ],
            SettlementPeriodMode::cases(),
        ));
    }

    private function maybeSyncCreditCardSettlements(Account $account): ?CreditCardSettlementSyncResult
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        if ($type !== AccountType::CreditCard) {
            return null;
        }

        return $this->creditCardSettlementSync->syncForCard($account);
    }
}
