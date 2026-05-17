<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Http\Requests\Recurring\ConfirmRecurringRequest;
use App\Http\Requests\Recurring\DismissRecurringRequest;
use App\Http\Requests\Recurring\IndexRecurringRequest;
use App\Models\RecurringPattern;
use App\Services\RecurringDetector;
use App\Services\RecurringListService;
use App\Services\RecurringPatternService;
use App\Services\RecurringPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class RecurringController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RecurringDetector $detector,
        private readonly RecurringPatternService $patterns,
        private readonly RecurringPresenter $presenter,
        private readonly RecurringListService $list,
    ) {}

    public function index(IndexRecurringRequest $request): Response
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        $filters = $request->listFilters();
        $suggestions = $this->detector->findSuggestions($user);

        $confirmedPaginator = $this->list->paginateConfirmed($user, $filters);
        $suggestionsPaginator = $this->list->paginateSuggestions($user, $suggestions, $filters);

        return Inertia::render('recurring/recurring-index', [
            'confirmed' => $this->presenter->serializeConfirmedPaginator($confirmedPaginator),
            'suggestions' => $this->presenter->serializeSuggestionsPaginator($suggestionsPaginator),
            'summary' => $this->list->buildSummary($user, $filters),
            'filters' => $request->listFiltersForFrontend(),
            'perPageOptions' => IndexRecurringRequest::perPageOptions(),
            'frequencyOptions' => array_map(
                static fn (RecurringFrequency $frequency): string => $frequency->value,
                RecurringFrequency::cases(),
            ),
            'categoryOptions' => $this->presenter->categoryOptions($user),
        ]);
    }

    public function confirm(ConfirmRecurringRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $payload = $request->suggestionPayload();

        try {
            $suggestion = new RecurringSuggestion(
                labelPattern: $payload['label_pattern'],
                displayLabel: $payload['display_label'],
                expectedAmount: $payload['expected_amount'],
                frequency: $payload['frequency'],
                transactionType: $payload['transaction_type'],
                occurrenceCount: $payload['occurrence_count'],
                score: 100,
                suggestedCategoryId: $payload['suggested_category_id'],
                accountId: $payload['account_id'],
                sampleTransactions: [],
            );

            $this->patterns->confirm(
                $user,
                $suggestion,
                $payload['suggested_category_id'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'recurring' => $this->errorMessage($exception->getMessage()),
            ]);
        }

        return back()->with('success', __('ui.recurring.confirmed'));
    }

    public function dismiss(DismissRecurringRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $this->patterns->dismiss(
            $user,
            (string) $request->input('label_pattern'),
            $request->transactionType(),
        );

        return back()->with('success', __('ui.recurring.dismissed'));
    }

    public function destroy(RecurringPattern $recurringPattern): RedirectResponse
    {
        $this->authorize('delete', $recurringPattern);

        $recurringPattern->delete();

        return back()->with('success', __('ui.recurring.removed'));
    }

    private function errorMessage(string $key): string
    {
        $messageKey = 'ui.recurring.errors.'.$key;
        $translated = __($messageKey);

        return $translated !== $messageKey ? $translated : __('ui.recurring.errors.generic');
    }
}
