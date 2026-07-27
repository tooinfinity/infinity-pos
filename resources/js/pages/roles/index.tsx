import { Head, Link, router } from '@inertiajs/react';
import { LockKeyhole, Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, destroy, edit, index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

type Role = {
    id: string;
    name: string;
    is_protected: boolean;
    permissions: string[];
    users_count: number;
};

type Props = {
    roles: Role[];
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        assign_permissions: boolean;
    };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index() }];

export default function RolesIndex({ roles, can }: Props) {
    const removeRole = (role: Role) => {
        if (window.confirm(`Delete the ${role.name} role?`)) {
            router.delete(destroy(role.id), { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
                            Access control
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                            Roles
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Bundle fixed application capabilities into job
                            profiles.
                        </p>
                    </div>
                    {can.create && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                New role
                            </Link>
                        </Button>
                    )}
                </header>

                <div className="grid gap-4 lg:grid-cols-2">
                    {roles.map((role) => (
                        <article
                            key={role.id}
                            className="rounded-xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h2 className="font-semibold">
                                            {role.name}
                                        </h2>
                                        {role.is_protected && (
                                            <Badge variant="outline">
                                                <LockKeyhole />
                                                Protected
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {role.users_count}{' '}
                                        {role.users_count === 1
                                            ? 'user'
                                            : 'users'}
                                    </p>
                                </div>
                                <div className="flex gap-1">
                                    {(can.update || can.assign_permissions) && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={edit(role.id)}
                                                aria-label={`Edit ${role.name}`}
                                            >
                                                <Pencil />
                                            </Link>
                                        </Button>
                                    )}
                                    {can.delete && !role.is_protected && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            onClick={() => removeRole(role)}
                                            aria-label={`Delete ${role.name}`}
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-1.5">
                                {role.permissions.length > 0 ? (
                                    role.permissions.map((permission) => (
                                        <Badge
                                            key={permission}
                                            variant="secondary"
                                        >
                                            {permission}
                                        </Badge>
                                    ))
                                ) : (
                                    <span className="text-sm text-muted-foreground">
                                        No permissions assigned.
                                    </span>
                                )}
                            </div>
                        </article>
                    ))}
                </div>

                {roles.length === 0 && (
                    <div className="rounded-xl border border-dashed p-12 text-center text-sm text-muted-foreground">
                        No roles yet. Create one to start delegating access.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
