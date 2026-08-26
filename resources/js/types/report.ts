import type { SelectOption } from './member';

export type ReportPeriod =
    | 'today'
    | 'yesterday'
    | 'this_week'
    | 'this_month'
    | 'last_month'
    | 'custom';

export type ReportFilters = {
    period: ReportPeriod;
    date_from: string;
    date_to: string;
};

export type ReportRange = {
    period: ReportPeriod;
    label: string;
    date_from: string;
    date_to: string;
    days: number;
};

export type ReportData = {
    revenue: {
        total: string;
        payment_count: number;
        average: string;
        membership_total: string;
        pt_total: string;
        method_breakdown: Array<{
            method: string | null;
            label: string;
            payment_count: number;
            total: string;
        }>;
    };
    members: {
        active: number;
        inactive: number;
        new_in_period: number;
    };
    memberships: {
        active: number;
        expired: number;
        expiring_soon: number;
        started_in_period: number;
        warning_days: number;
    };
    check_ins: {
        total: number;
        unique_members: number;
        daily_average: string;
        top_visitors: Array<{
            member: {
                id: number;
                member_number: string;
                name: string;
            };
            visit_count: number;
        }>;
    };
    personal_training: {
        active_clients: number;
        packages_sold: number;
        revenue: string;
        completed_sessions: number;
        upcoming_sessions: number;
        no_shows: number;
        trainers: Array<{
            id: number;
            trainer_code: string | null;
            name: string;
            active_clients: number;
            completed_sessions: number;
            upcoming_sessions: number;
        }>;
    };
};

export type ReportIndexProps = {
    filters: ReportFilters;
    range: ReportRange;
    periodOptions: SelectOption[];
    report?: ReportData;
};
