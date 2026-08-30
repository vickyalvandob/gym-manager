export type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'trainer';
    role_label: string;
    status: 'active' | 'inactive';
    status_label: string;
    is_account_active: boolean;
    trainer_id: number | null;
    trainer_code: string | null;
    created_at: string;
};

export type PaginatedStaff = {
    data: StaffMember[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
