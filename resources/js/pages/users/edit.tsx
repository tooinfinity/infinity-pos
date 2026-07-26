import { Form, Head, Link, router } from '@inertiajs/react';
import { Archive, ArchiveRestore } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index, restore, update } from '@/routes/users';
import { reset as resetPassword } from '@/routes/users/password';
import { sync as syncRoles } from '@/routes/users/roles';
import type { BreadcrumbItem } from '@/types';

type Props = {
    user: {
        id: string;
        name: string;
        email: string;
        is_active: boolean;
        deleted_at: string | null;
        roles: string[];
    };
    isDeleted: boolean;
    roles: Array<{ value: string; label: string }>;
    can: {
        update: boolean;
        assign_roles: boolean;
        reset_password: boolean;
        archive: boolean;
        restore: boolean;
    };
};

export default function EditUser({ user, isDeleted, roles, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: index() },
        { title: user.name, href: edit(user.id) },
    ];

    const archiveUser = () => {
        if (!window.confirm(`Archive the account for ${user.name}?`)) {
            return;
        }

        router.delete(destroy(user.id), { preserveScroll: true });
    };

    const restoreUser = () => {
        router.put(restore(user.id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />
            <div className="mx-auto grid w-full max-w-5xl gap-6 p-4 md:grid-cols-2 md:p-6">
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle>Account details</CardTitle>
                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant={
                                        user.is_active
                                            ? 'outline'
                                            : 'destructive'
                                    }
                                >
                                    {user.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                                {isDeleted && (
                                    <Badge variant="secondary">Archived</Badge>
                                )}
                            </div>
                        </div>
                        <CardDescription>
                            Identity used for sign-in and audit records.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...update.form(user.id)} className="space-y-5">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={user.name}
                                            required
                                            disabled={isDeleted}
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={user.email}
                                            required
                                            disabled={isDeleted}
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <Button
                                        disabled={
                                            !can.update ||
                                            processing ||
                                            isDeleted
                                        }
                                    >
                                        Save details
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Access roles</CardTitle>
                            <CardDescription>
                                Permissions are inherited from selected roles.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...syncRoles.form(user.id)}
                                transform={(data) => ({
                                    ...data,
                                    roles: Array.isArray(data.roles)
                                        ? data.roles
                                        : [],
                                })}
                                className="space-y-5"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-3">
                                            {roles.map((role) => (
                                                <label
                                                    key={role.value}
                                                    className="flex items-center gap-3 rounded-lg border p-3 text-sm"
                                                >
                                                    <Checkbox
                                                        name="roles[]"
                                                        value={role.value}
                                                        defaultChecked={user.roles.includes(
                                                            role.value,
                                                        )}
                                                        disabled={
                                                            !can.assign_roles ||
                                                            isDeleted
                                                        }
                                                    />
                                                    {role.label}
                                                </label>
                                            ))}
                                        </div>
                                        <InputError message={errors.roles} />
                                        <Button
                                            disabled={
                                                !can.assign_roles ||
                                                processing ||
                                                isDeleted
                                            }
                                        >
                                            Update roles
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    {can.reset_password && !isDeleted && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Reset password</CardTitle>
                                <CardDescription>
                                    Set a temporary password and deliver it
                                    through a secure channel.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...resetPassword.form(user.id)}
                                    resetOnSuccess
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="password">
                                                    New password
                                                </Label>
                                                <PasswordInput
                                                    id="password"
                                                    name="password"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">
                                                    Confirm password
                                                </Label>
                                                <PasswordInput
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    required
                                                />
                                            </div>
                                            <Button
                                                variant="secondary"
                                                disabled={processing}
                                            >
                                                Reset password
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {can.archive && !isDeleted && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Danger zone</CardTitle>
                                <CardDescription>
                                    Archive this employee account and sign out
                                    all open sessions. Audit history is kept.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="destructive"
                                    onClick={archiveUser}
                                >
                                    <Archive />
                                    Archive user
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {can.restore && isDeleted && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Restore account</CardTitle>
                                <CardDescription>
                                    Restore sign-in eligibility while keeping
                                    the account's current active status and
                                    roles.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button onClick={restoreUser}>
                                    <ArchiveRestore />
                                    Restore user
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <Button
                    variant="ghost"
                    asChild
                    className="md:col-span-2 md:justify-self-start"
                >
                    <Link href={index()}>Back to users</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
