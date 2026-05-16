export type ImportColumnField =
    | 'date'
    | 'label'
    | 'amount_signed'
    | 'debit'
    | 'credit'
    | 'balance'
    | 'skip';

export type ColumnMappingEntry = {
    index: number;
    field: ImportColumnField;
};

export type CsvOptions = {
    delimiter: string;
    enclosure: string;
    encoding: string;
    skip_rows: number;
    date_format: string;
};

export type ProviderRecord = {
    id: number;
    name: string;
    logo_url: string | null;
    default_account_id: number | null;
    default_account_name: string | null;
    column_mapping: {
        columns: ColumnMappingEntry[];
    };
    csv_options: CsvOptions;
    updated_at: string | null;
};

export type ProviderAccountOption = {
    id: number;
    name: string;
    logo_url: string | null;
};

export type ProviderFieldOption = {
    value: ImportColumnField;
    label: string;
};

export type ProviderListItem = {
    id: number;
    name: string;
    logo_url: string | null;
    default_account_id: number | null;
    default_account_name: string | null;
    updated_at: string | null;
};

export type ProvidersIndexPageProps = {
    providers: ProviderListItem[];
};

export type ProvidersFormPageProps = {
    provider: ProviderRecord | null;
    accounts: ProviderAccountOption[];
    fieldOptions: ProviderFieldOption[];
    defaultCsvOptions: CsvOptions;
};

export type NormalizedPreviewRow =
    | { line: number; date: string; label: string; amount: number }
    | { line: number; error: string };

export type DateFormatSuggestion = {
    format: string;
    label: string;
    matched: number;
    total: number;
    confidence: number;
};
