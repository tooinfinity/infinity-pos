import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, UserRoundCheck, UserRoundX } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, edit, index } from '@/routes/users';
import { update as updateStatus } from '@/routes/users/status';
import type { BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    is_active: boolean;
    deleted_at: string | null;
    roles: string[];
    can_manage_status: boolean;
    can_manage: boolean;
};

type Props = {
    users: {
        data: ManagedUser[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    can: {
        create: boolean;
    };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index() }];

export default function UsersIndex({ users, can }: Props) {
    const changeStatus = (user: ManagedUser) => {
        router.put(
            updateStatus(user.id),
            { is_active: !user.is_active },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
                            Administration
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                            Team accounts
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Control employee access without sharing credentials.
                        </p>
                    </div>
                    {can.create && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                New user
                            </Link>
                        </Button>
                    )}
                </header>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="border-b bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">User</th>
                                    <th className="px-4 py-3">Roles</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="hover:bg-muted/20"
                                    >
                                        <td className="px-4 py-4">
                                            <div className="font-medium">
                                                {user.name}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {user.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.length > 0 ? (
                                                    user.roles.map((role) => (
                                                        <Badge
                                                            key={role}
                                                            variant="secondary"
                                                        >
                                                            {role}
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        No role
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex flex-col gap-1">
                                                <Badge
                                                    variant={
                                                        user.is_active
                                                            ? 'outline'
                                                            : 'destructive'
                                                    }
                                                >
                                                    {user.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </Badge>
                                                {user.deleted_at && (
                                                    <Badge variant="secondary">
                                                        Archived
                                                    </Badge>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex justify-end gap-2">
                                                {user.can_manage_status && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            changeStatus(user)
                                                        }
                                                    >
                                                        {user.is_active ? (
                                                            <UserRoundX />
                                                        ) : (
                                                            <UserRoundCheck />
                                                        )}
                                                        {user.is_active
                                                            ? 'Deactivate'
                                                            : 'Activate'}
                                                    </Button>
                                                )}
                                                {user.can_manage && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(user.id)}
                                                        >
                                                            <Pencil />
                                                            Manage
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {users.data.length === 0 && (
                        <p className="p-10 text-center text-sm text-muted-foreground">
                            No team accounts yet.
                        </p>
                    )}
                </div>

                <nav className="flex flex-wrap gap-2" aria-label="Pagination">
                    {users.links.map((link) =>
                        link.url ? (
                            <Button
                                key={`${link.label}-${link.url}`}
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                                asChild
                            >
                                <Link
                                    href={link.url}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </Button>
                        ) : (
                            <Button
                                key={link.label}
                                size="sm"
                                variant="outline"
                                disabled
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </nav>
            </div>
        </AppLayout>
    );
}
