export type ImportWizardProvider = {
    id: number;
    name: string;
    default_account_id: number | null;
    logo_url: string | null;
};

export type ImportWizardAccount = {
    id: number;
    name: string;
    institution: string | null;
    logo_url: string | null;
};

export type ImportPreviewRow = {
    line: number;
    date?: string;
    label?: string;
    amount?: number;
    balance?: number | null;
    import_hash?: string;
    is_duplicate?: boolean;
    existing_transaction_id?: number | null;
    status: 'ok' | 'error';
    error?: string;
};

export type ImportBatchPayload = {
    id: number;
    status: 'draft' | 'preview' | 'completed' | 'cancelled';
    import_provider_id: number;
    account_id: number;
    provider_name?: string;
    account_name?: string;
    account_current_balance?: string | null;
    original_filename?: string | null;
    preview_rows: ImportPreviewRow[];
    maps_balance: boolean;
    imported_count: number;
    skipped_count: number;
    replaced_count: number;
    error_count: number;
    completed_at?: string | null;
};

export type ImportDuplicateAction = 'skip' | 'import' | 'replace';

export type ImportWizardPageProps = {
    providers: ImportWizardProvider[];
    accounts: ImportWizardAccount[];
    batch: ImportBatchPayload | null;
};
