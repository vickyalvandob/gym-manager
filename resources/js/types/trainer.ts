import type { MemberStatus, PaginationLink } from './member';

export type TrainerStatus = 'active' | 'inactive';

export type TrainerLinkedUser = {
    id: number;
    name: string;
    email: string;
};

export type Trainer = {
    id: number;
    trainer_code: string | null;
    user_id: number | null;
    name: string;
    phone: string;
    email: string | null;
    specialization: string | null;
    bio: string | null;
    joined_at: string | null;
    status: TrainerStatus;
    status_label: string;
    notes: string | null;
    members_count: number;
    linked_user: TrainerLinkedUser | null;
    created_at: string | null;
    updated_at: string | null;
};

export type TrainerAccountOption = {
    value: number;
    label: string;
    description: string;
};

export type TrainerMember = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    email?: string | null;
    status: MemberStatus;
    status_label: string;
    assigned_at?: string | null;
    membership?: {
        plan_name: string;
        end_date: string;
    } | null;
};

export type PaginatedTrainers = {
    data: Trainer[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedTrainerMembers = {
    data: TrainerMember[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};
