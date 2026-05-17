<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Transfers\AcceptTransferSuggestionRequest;
use App\Http\Requests\Transfers\DismissTransferSuggestionRequest;
use App\Http\Requests\Transfers\StoreManualTransferRequest;
use App\Models\Transaction;
use App\Services\TransferDetector;
use App\Services\TransferPresenter;
use App\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferDetector $detector,
        private readonly TransferService $transfers,
        private readonly TransferPresenter $presenter,
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        $suggestions = $this->detector->findCandidates($user);

        return Inertia::render('transfers/transfers-index', [
            'suggestions' => $this->presenter->serializeSuggestions($suggestions),
            'accountOptions' => $this->presenter->accountOptions(),
        ]);
    }

    public function accept(AcceptTransferSuggestionRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $this->transfers->linkPair(
                $user,
                $request->outgoingTransaction(),
                $request->incomingTransaction(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'transfer' => $this->transferErrorMessage($exception->getMessage()),
            ]);
        }

        return redirect()
            ->route('transfers.index')
            ->with('success', __('ui.transfers.accepted'));
    }

    public function dismiss(DismissTransferSuggestionRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $outgoing = Transaction::query()->findOrFail((int) $request->input('outgoing_transaction_id'));
        $incoming = Transaction::query()->findOrFail((int) $request->input('incoming_transaction_id'));

        try {
            $this->transfers->dismissPair($user, $outgoing, $incoming);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'transfer' => $this->transferErrorMessage($exception->getMessage()),
            ]);
        }

        return redirect()
            ->route('transfers.index')
            ->with('success', __('ui.transfers.dismissed'));
    }

    public function store(StoreManualTransferRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $this->transfers->createManualPair($user, $request->transferData());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'transfer' => $this->transferErrorMessage($exception->getMessage()),
                ]);
        }

        return redirect()
            ->route('transfers.index')
            ->with('success', __('ui.transfers.created'));
    }

    private function transferErrorMessage(string $key): string
    {
        $messageKey = 'ui.transfers.errors.'.$key;
        $translated = __($messageKey);

        return $translated !== $messageKey ? $translated : __('ui.transfers.errors.generic');
    }
}
