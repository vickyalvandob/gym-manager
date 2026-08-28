import type { PaymentStatus } from './payment';
import type { PtSessionStatus } from './personal-training';

export type DashboardMetrics = {
    active_members: number;
    expired_members: number;
    expiring_soon: number;
    new_members_this_month: number;
    check_ins_today: number;
    revenue_today: string | null;
    revenue_this_month: string | null;
    pending_payments_count: number | null;
    pending_payments_amount: string | null;
};

export type DashboardRevenuePoint = {
    date: string;
    amount: string;
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

export type TrainerDashboardWorkspace = {
    trainer: {
        id: number;
        name: string;
        specialization: string | null;
    } | null;
    assigned_members_count: number;
    today_sessions_count: number;
    upcoming_sessions_count: number;
    active_pt_clients_count: number;
    today_sessions: Array<{
        id: number;
        scheduled_at: string;
        duration_minutes: number;
        status: PtSessionStatus;
        status_label: string;
        member: { id: number; member_number: string; name: string };
        pt_package_name: string;
    }>;
    session_members: Array<{
        id: number;
        member: { id: number; member_number: string; name: string };
        pt_package_name: string;
        remaining_sessions: number;
        available_sessions: number;
        expires_at: string | null;
    }>;
};

export type DashboardSnapshot = {
    metrics: DashboardMetrics;
    revenue_trend: DashboardRevenuePoint[] | null;
    recent_check_ins: DashboardRecentCheckIn[];
    recent_payments: DashboardRecentPayment[];
    trainer_workspace: TrainerDashboardWorkspace | null;
    generated_at: string;
};

export type DashboardProps = {
    snapshot?: DashboardSnapshot;
};
