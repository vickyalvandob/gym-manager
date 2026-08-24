export type MemberStatus = 'active' | 'inactive';
export type MemberGender = 'male' | 'female';

export type SelectOption = {
    value: string;
    label: string;
};

export type MemberSummary = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    email: string | null;
    photo_url: string | null;
    status: MemberStatus;
    status_label: string;
    created_at: string | null;
};

export type MemberDetail = MemberSummary & {
    gender: MemberGender | null;
    gender_label: string | null;
    birth_date: string | null;
    address: string | null;
    emergency_contact: string | null;
    notes: string | null;
    updated_at: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedMembers = {
    data: MemberSummary[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};
