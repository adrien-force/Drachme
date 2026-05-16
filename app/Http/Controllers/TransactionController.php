<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use App\Http\Requests\Transactions\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AccountService;
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
        private readonly AccountService $accounts,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $items = Transaction::query()
            ->with('account:id,name,logo_path')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Transaction $transaction): array => $this->serializeTransaction($transaction));

        return Inertia::render('transactions/transactions-index', [
            'transactions' => $items,
        ]);
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

        return Inertia::render('transactions/transactions-form', $this->formPayload(null, $presetAccount));
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

    public function edit(Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);
        $transaction->load('account:id,name,logo_path');

        return Inertia::render('transactions/transactions-form', $this->formPayload($transaction, null));
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
     * @return array<string, mixed>
     */
    private function formPayload(?Transaction $transaction, ?Account $presetAccount): array
    {
        $accountOptions = Account::query()
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
            'transaction' => $transaction !== null ? $this->serializeTransaction($transaction) : null,
            'accounts' => $accountOptions,
            'presetAccountId' => $presetAccount?->id,
            'typeOptions' => $this->typeOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(Transaction $transaction): array
    {
        $account = $transaction->account;
        $type = $transaction->type;
        $date = $transaction->date;

        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $account?->name,
            'account_logo_url' => $account !== null ? $this->accounts->logoUrl($account) : null,
            'date' => $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : (string) $date,
            'label' => $transaction->label,
            'amount' => (float) $transaction->amount,
            'type' => $type instanceof TransactionType ? $type->value : (string) $type,
            'notes' => $transaction->notes,
            'is_transfer_linked' => $transaction->transfer_pair_id !== null,
            'import_batch_id' => $transaction->import_batch_id,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
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
     * @return array<string, string>
     */
    private function mapServiceError(InvalidArgumentException $exception): array
    {
        $key = $exception->getMessage();

        return match ($key) {
            'transaction_amount_zero' => ['amount' => __('ui.transactions.errors.amount_zero')],
            'transaction_transfer_linked' => ['transaction' => __('ui.transactions.errors.transfer_linked')],
            'transaction_account_forbidden' => ['account_id' => __('ui.transactions.errors.account_forbidden')],
            default => ['transaction' => __('ui.transactions.errors.generic')],
        };
    }
}
