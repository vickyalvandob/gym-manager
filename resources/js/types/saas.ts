export type SaasPlan = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    currency: string;
    billing_interval: 'monthly' | 'yearly';
    billing_interval_label: string;
    trial_days: number;
    max_members: number | null;
    max_staff: number | null;
    is_active: boolean;
    sort_order: number;
    subscriptions_count: number;
};

export type PlatformSubscription = {
    id: number;
    status: string;
    status_label: string;
    plan_id: number;
    plan_name: string;
    trial_ends_at: string | null;
    current_period_starts_at: string | null;
    current_period_ends_at: string | null;
    grants_access: boolean;
};

export type PlatformGym = {
    id: number;
    name: string;
    slug: string;
    status: 'active' | 'suspended';
    status_label: string;
    onboarding_completed_at: string | null;
    users_count: number;
    members_count: number;
    trainers_count: number;
    created_at: string;
    subscription: PlatformSubscription | null;
    users?: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
    }>;
};

export type PaginatedPlatformGyms = {
    data: PlatformGym[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
