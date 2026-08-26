import type { PaginationLink } from './member';

export type PtPackage = {
    id: number;
    name: string;
    session_count: number;
    validity_days: number | null;
    price: string;
    description: string | null;
    is_active: boolean;
    status_label: string;
    sales_count: number;
    created_at: string | null;
    updated_at: string | null;
};

export type PtPackageOption = Pick<
    PtPackage,
    'id' | 'name' | 'session_count' | 'validity_days' | 'price'
>;

export type PtTrainerOption = {
    id: number;
    trainer_code: string | null;
    name: string;
    specialization: string | null;
};

export type MemberPtPackageSummary = {
    id: number;
    name: string;
    trainer: PtTrainerOption;
    total_sessions: number;
    used_sessions: number;
    scheduled_sessions: number;
    available_sessions: number;
    start_date: string;
    expires_at: string | null;
    price: string;
    status: 'pending' | 'active' | 'completed' | 'expired' | 'cancelled';
    status_label: string;
    payment_status: 'pending' | 'paid';
};

export type PtSessionStatus =
    'scheduled' | 'completed' | 'cancelled' | 'no_show';

export type PtSessionListItem = {
    id: number;
    scheduled_at: string;
    duration_minutes: number;
    status: PtSessionStatus;
    status_label: string;
    completed_at: string | null;
    notes: string | null;
    cancellation_reason: string | null;
    quota_consumed: boolean;
    member: {
        id: number;
        member_number: string;
        name: string;
        phone: string;
    };
    trainer: PtTrainerOption;
    pt_package: {
        id: number;
        name: string;
    };
};

export type PtSessionDetail = PtSessionListItem & {
    package: Pick<
        MemberPtPackageSummary,
        | 'id'
        | 'total_sessions'
        | 'used_sessions'
        | 'scheduled_sessions'
        | 'available_sessions'
        | 'start_date'
        | 'expires_at'
        | 'status'
    >;
};

export type PaginatedPtPackages = {
    data: PtPackage[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedPtSessions = {
    data: PtSessionListItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type TrainerWorkspaceMember = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    status: 'active' | 'inactive';
    status_label: string;
    membership: {
        plan_name: string;
        end_date: string;
    } | null;
    pt_package: {
        id: number;
        name: string;
        total_sessions: number;
        used_sessions: number;
        scheduled_sessions: number;
        available_sessions: number;
        expires_at: string | null;
        status: string;
    } | null;
    next_session: {
        id: number;
        scheduled_at: string;
        duration_minutes: number;
        status: PtSessionStatus;
        status_label: string;
        notes: string | null;
        pt_package_name: string;
    } | null;
    sessions?: Array<{
        id: number;
        scheduled_at: string;
        duration_minutes: number;
        status: PtSessionStatus;
        status_label: string;
        notes: string | null;
        pt_package_name: string;
    }>;
};

export type PaginatedTrainerWorkspaceMembers = {
    data: TrainerWorkspaceMember[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};
