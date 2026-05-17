export type ImportProviderType = 'transactions' | 'positions';

export type ImportColumnField =
    | 'date'
    | 'label'
    | 'amount_signed'
    | 'debit'
    | 'credit'
    | 'balance'
    | 'skip';

export type ImportPositionColumnField =
    | 'position_label'
    | 'isin'
    | 'quantity'
    | 'average_price'
    | 'last_price'
    | 'skip';

export type ColumnMappingField = ImportColumnField | ImportPositionColumnField;

export type ColumnMappingEntry = {
    index: number;
    field: ColumnMappingField;
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
    account_ids: number[];
    import_type: ImportProviderType;
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
    value: ColumnMappingField;
    label: string;
};

export type ProviderListItem = {
    id: number;
    name: string;
    logo_url: string | null;
    default_account_id: number | null;
    default_account_name: string | null;
    import_type: ImportProviderType;
    updated_at: string | null;
};

export type ProvidersIndexPageProps = {
    providers: ProviderListItem[];
};

export type ProvidersFormPageProps = {
    provider: ProviderRecord | null;
    accounts: ProviderAccountOption[];
    fieldOptions: ProviderFieldOption[];
    positionFieldOptions: ProviderFieldOption[];
    defaultCsvOptions: CsvOptions;
};

export type NormalizedPreviewRow =
    | { line: number; date: string; label: string; amount: number; balance?: number | null }
    | { line: number; error: string };

export type NormalizedPositionPreviewRow =
    | {
          line: number;
          label: string;
          isin: string;
          quantity: number;
          average_price: number;
          last_price: number | null;
          market_value: number;
      }
    | { line: number; error: string };

export type ProvidersShowPageProps = {
    provider: ProviderRecord;
    accounts: ProviderAccountOption[];
    fieldOptions: ProviderFieldOption[];
    positionFieldOptions: ProviderFieldOption[];
    defaultCsvOptions: CsvOptions;
};

export type DateFormatSuggestion = {
    format: string;
    label: string;
    matched: number;
    total: number;
    confidence: number;
};
