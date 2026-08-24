import type { PaginationLink } from './member';

export type MembershipDurationUnit = 'day' | 'week' | 'month' | 'year';

export type MembershipPlan = {
    id: number;
    name: string;
    duration: number;
    duration_unit: MembershipDurationUnit;
    duration_unit_label: string;
    duration_label: string;
    price: string;
    description: string | null;
    is_active: boolean;
    status_label: string;
    created_at: string | null;
    updated_at: string | null;
};

export type PaginatedMembershipPlans = {
    data: MembershipPlan[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};
