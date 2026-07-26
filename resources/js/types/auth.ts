export type User = {
    id: string;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
    avatar?: string;
    created_at: string;
    updated_at: string;
};

export type Auth = {
    user: User | null;
};
