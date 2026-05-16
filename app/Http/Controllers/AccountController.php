<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
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
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        $showArchived = $request->boolean('archived');

        $query = Account::query()->orderBy('name');

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

    public function show(Account $account): Response
    {
        $this->authorize('view', $account);

        return Inertia::render('accounts/accounts-show', [
            'account' => $this->serializeAccount($account),
            'transactions' => [],
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
            'currency' => $account->currency,
            'opened_at' => $openedAt instanceof \DateTimeInterface
                ? $openedAt->format('Y-m-d')
                : ($openedAt !== null ? \Illuminate\Support\Carbon::parse((string) $openedAt)->format('Y-m-d') : null),
            'is_archived' => $account->is_archived,
            'last_activity_at' => null,
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
}
