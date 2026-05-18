<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Transactions\ProcessTransactionTriageRequest;
use App\Models\Transaction;
use App\Services\CategoryService;
use App\Services\TransactionTriageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransactionTriageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TransactionTriageService $triage,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();
        abort_if($user === null, 403);

        $this->categories->seedDefaultsForUser($user);

        /** @var list<int> $skipIds */
        $skipIds = array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->input('skip_ids', []),
        ));

        $remaining = $this->triage->countUncategorized($user);
        $current = $this->triage->nextUncategorized($user, $skipIds);

        return Inertia::render('transactions/transactions-triage', [
            'transaction' => $this->triage->serializeTransaction($user, $current),
            'remaining' => max(0, $remaining - ($current !== null ? 1 : 0)),
            'totalUncategorized' => $remaining,
            'skipIds' => $skipIds,
            'categoryOptions' => $this->categories->flatSelectableOptions($user),
        ]);
    }

    public function process(
        ProcessTransactionTriageRequest $request,
        Transaction $transaction,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $skipIds = $request->skipIds();

        if ($request->action() === 'skip') {
            $skipIds[] = $transaction->id;

            return redirect()->route('transactions.triage', [
                'skip_ids' => array_values(array_unique($skipIds)),
            ]);
        }

        $categoryId = $request->categoryId();

        if ($categoryId === null) {
            return back()->withErrors(['category_id' => (string) __('ui.transactions.triage.category_required')]);
        }

        try {
            $auto = $this->triage->categorize(
                $user,
                $transaction,
                $categoryId,
                $request->createRule(),
                $request->selectedTokens(),
                $request->recurringFrequency(),
                $request->ruleFlow(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors($this->mapError($exception));
        }

        if ($auto['auto_matched'] > 0) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('ui.transactions.triage.auto_categorized', [
                    'count' => $auto['auto_matched'],
                ]),
            ]);
        }

        return redirect()->route('transactions.triage', [
            'skip_ids' => $skipIds,
        ]);
    }

    public function applyRules(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();
        abort_if($user === null, 403);

        $result = $this->triage->applyAllRules($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.transactions.rules_bulk_applied', [
                'matched' => $result['matched'],
                'scanned' => $result['scanned'],
            ]),
        ]);

        return redirect()->route('transactions.triage', [
            'skip_ids' => $request->input('skip_ids', []),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function mapError(InvalidArgumentException $exception): array
    {
        return match ($exception->getMessage()) {
            'category_rule_tokens_invalid' => ['selected_tokens' => (string) __('ui.category_rules.errors.tokens_invalid')],
            'recurring_transfer_forbidden', 'recurring_label_generic', 'recurring_amount_zero' => [
                'recurring_frequency' => (string) __('ui.transactions.triage.recurring_not_allowed'),
            ],
            default => ['transaction' => (string) __('ui.transactions.errors.generic')],
        };
    }
}
