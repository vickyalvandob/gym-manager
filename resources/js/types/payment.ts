import type { PaginationLink, SelectOption } from './member';

export type PaymentStatus = 'pending' | 'paid';
export type PaymentType = 'membership' | 'personal_training';
export type PaymentMethod =
    'cash' | 'bank_transfer' | 'debit_card' | 'credit_card' | 'e_wallet';

export type PaymentReceivedBy = {
    id: number;
    name: string;
};

export type MembershipPayment = {
    id: number;
    invoice_number: string;
    amount: string;
    status: PaymentStatus;
    status_label: string;
    method: PaymentMethod | null;
    method_label: string | null;
    paid_at: string | null;
    notes: string | null;
    received_by: PaymentReceivedBy | null;
    created_at: string | null;
};

export type PaymentListItem = MembershipPayment & {
    type: PaymentType;
    type_label: string;
    member: {
        id: number;
        member_number: string;
        name: string;
        phone: string;
    };
    membership: {
        id: number;
        plan_name: string;
        start_date: string;
        end_date: string;
    } | null;
    personal_training: {
        id: number;
        package_name: string;
        trainer_name: string;
        total_sessions: number;
        start_date: string;
        expires_at: string | null;
    } | null;
};

export type PaginatedPayments = {
    data: PaymentListItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type PaymentFilters = {
    search: string;
    status: string;
    method: string;
    type: string;
    date_from: string;
    date_to: string;
    per_page: number;
};

export type PaymentSummary = {
    paid_total: string;
    outstanding_total: string;
    paid_count: number;
    pending_count: number;
    membership_revenue: string;
    pt_revenue: string;
};

export type PaymentIndexProps = {
    payments: PaginatedPayments;
    filters: PaymentFilters;
    summary: PaymentSummary;
    statusOptions: SelectOption[];
    methodOptions: SelectOption[];
    typeOptions: SelectOption[];
};
