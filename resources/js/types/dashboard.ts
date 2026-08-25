import type { PaymentStatus } from './payment';

export type DashboardMetrics = {
    active_members: number;
    expired_members: number;
    expiring_soon: number;
    new_members_this_month: number;
    check_ins_today: number;
    revenue_today: string | null;
    revenue_this_month: string | null;
};

export type DashboardRecentCheckIn = {
    id: number;
    checked_in_at: string;
    member: {
        id: number;
        member_number: string;
        name: string;
    };
    membership: {
        plan_name: string;
    };
};

export type DashboardRecentPayment = {
    id: number;
    invoice_number: string;
    amount: string;
    status: PaymentStatus;
    status_label: string;
    method_label: string | null;
    paid_at: string | null;
    created_at: string | null;
    member: {
        id: number;
        member_number: string;
        name: string;
    };
};

export type DashboardSnapshot = {
    metrics: DashboardMetrics;
    recent_check_ins: DashboardRecentCheckIn[];
    recent_payments: DashboardRecentPayment[];
    generated_at: string;
};

export type DashboardProps = {
    snapshot?: DashboardSnapshot;
};
