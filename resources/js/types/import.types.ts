import type { ImportProviderType } from '@/types/provider.types';

export type ImportWizardProvider = {
    id: number;
    name: string;
    default_account_id: number | null;
    import_type: ImportProviderType;
    logo_url: string | null;
};

export type ImportWizardAccount = {
    id: number;
    name: string;
    type: string;
    institution: string | null;
    logo_url: string | null;
};

export type ImportTransactionPreviewRow = {
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

export type ImportPositionPreviewRow = {
    line: number;
    label?: string;
    isin?: string;
    quantity?: number;
    average_price?: number;
    last_price?: number | null;
    market_value?: number;
    is_duplicate?: boolean;
    existing_position_id?: number | null;
    status: 'ok' | 'error';
    error?: string;
};

export type ImportPreviewRow = ImportTransactionPreviewRow | ImportPositionPreviewRow;

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
    import_type: ImportProviderType;
    is_positions_import: boolean;
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
