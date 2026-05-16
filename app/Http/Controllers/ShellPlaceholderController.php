<?php

declare(strict_types=1);


namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ShellPlaceholderController extends Controller
{
    public function accounts(): Response
    {
        return $this->render('accounts');
    }

    public function transactions(): Response
    {
        return $this->render('transactions');
    }

    public function providers(): Response
    {
        return $this->render('providers');
    }

    public function import(): Response
    {
        return $this->render('import');
    }

    public function investments(): Response
    {
        return $this->render('investments');
    }

    /**
     * @param  'accounts'|'transactions'|'providers'|'import'|'investments'  $page
     */
    private function render(string $page): Response
    {
        $keys = match ($page) {
            'accounts' => ['shell.accounts_title', 'shell.accounts_description'],
            'transactions' => ['shell.transactions_title', 'shell.transactions_description'],
            'providers' => ['shell.providers_title', 'shell.providers_description'],
            'import' => ['shell.import_title', 'shell.import_description'],
            'investments' => ['shell.investments_title', 'shell.investments_description'],
        };

        return Inertia::render('shell/placeholder-page', [
            'titleKey' => $keys[0],
            'descriptionKey' => $keys[1],
        ]);
    }
}
