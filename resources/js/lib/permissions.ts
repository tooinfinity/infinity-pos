import { usePage } from '@inertiajs/react';

type AuthUser = {
    permissions: string[];
    roles: string[];
};

type PermissionsApi = {
    can: (permission: string) => boolean;
    canAny: (permissions: string[]) => boolean;
    canAll: (permissions: string[]) => boolean;
    is: (role: string) => boolean;
};

export function usePermissions(): PermissionsApi {
    const { auth } = usePage().props as { auth: { user: AuthUser | null } };
    const permissions = auth.user?.permissions ?? [];
    const roles = auth.user?.roles ?? [];

    return {
        can: (permission) => permissions.includes(permission),
        canAny: (items) =>
            items.some((permission) => permissions.includes(permission)),
        canAll: (items) =>
            items.every((permission) => permissions.includes(permission)),
        is: (role) => roles.includes(role),
    };
}
