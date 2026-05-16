<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccountService $accounts,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Account $account): array => $this->serializeAccount($account));

        return Inertia::render('accounts/accounts-index', [
            'accounts' => $accounts,
        ]);
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
        $this->accounts->create($request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.created'),
        ]);

        return to_route('accounts.index');
    }

    public function edit(Account $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('accounts/accounts-form', [
            'account' => $this->serializeAccount($account),
            'accountTypes' => $this->accountTypeOptions(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accounts->update($account, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.updated'),
        ]);

        return to_route('accounts.index');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->accounts->archive($account);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.accounts.archived'),
        ]);

        return to_route('accounts.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAccount(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'institution' => $account->institution,
            'type' => $account->type->value,
            'initial_balance' => (float) $account->initial_balance,
            'current_balance' => (float) $account->current_balance,
            'currency' => $account->currency,
            'opened_at' => $account->opened_at?->format('Y-m-d'),
            'is_archived' => $account->is_archived,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function accountTypeOptions(): array
    {
        return collect(AccountType::cases())
            ->map(fn (AccountType $type): array => [
                'value' => $type->value,
                'label' => __("ui.accounts.types.{$type->value}"),
            ])
            ->values()
            ->all();
    }
}
