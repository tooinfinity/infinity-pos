import { Form, Head, Link, router } from '@inertiajs/react';
import { LockKeyhole, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { destroy, edit, index, update } from '@/routes/roles';
import { sync } from '@/routes/roles/permissions';
import type { BreadcrumbItem } from '@/types';

type Props = {
    role: {
        id: number;
        name: string;
        is_protected: boolean;
        permissions_locked: boolean;
        permissions: string[];
    };
    permissions: Array<{ value: string; label: string; group: string }>;
    can: { update: boolean; delete: boolean; assign_permissions: boolean };
};

export default function EditRole({ role, permissions, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roles', href: index() },
        { title: role.name, href: edit(role.id) },
    ];

    const groupedPermissions = permissions.reduce<
        Record<string, typeof permissions>
    >((groups, permission) => {
        const group = (groups[permission.group] ??= []);
        group.push(permission);
        return groups;
    }, {});

    const removeRole = () => {
        if (window.confirm(`Delete the ${role.name} role?`)) {
            router.delete(destroy(role.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${role.name}`} />
            <div className="mx-auto grid w-full max-w-5xl gap-6 p-4 md:grid-cols-2 md:p-6">
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role identity</CardTitle>
                            <CardDescription>
                                Use a recognizable job title.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {role.is_protected ? (
                                <Alert>
                                    <LockKeyhole />
                                    <AlertTitle>Protected role</AlertTitle>
                                    <AlertDescription>
                                        Default POS roles keep stable names. The
                                        bootstrap command manages their catalog.
                                    </AlertDescription>
                                </Alert>
                            ) : can.update ? (
                                <Form
                                    {...update.form(role.id)}
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    Role name
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    defaultValue={role.name}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>
                                            <Button disabled={processing}>
                                                Save name
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    You cannot rename this role.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {can.delete && (
                        <Button variant="destructive" onClick={removeRole}>
                            <Trash2 />
                            Delete role
                        </Button>
                    )}

                    <Button variant="ghost" asChild>
                        <Link href={index()}>Back to roles</Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Permissions</CardTitle>
                        <CardDescription>
                            Capabilities are fixed in code; this role selects
                            which ones it grants.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {role.permissions_locked || !can.assign_permissions ? (
                            <div className="flex flex-wrap gap-2">
                                {role.permissions.map((permission) => (
                                    <span
                                        key={permission}
                                        className="rounded-md bg-muted px-2 py-1 text-xs"
                                    >
                                        {permission}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <Form
                                {...sync.form(role.id)}
                                transform={(data) => ({
                                    ...data,
                                    permissions: Array.isArray(data.permissions)
                                        ? data.permissions
                                        : [],
                                })}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        {Object.entries(groupedPermissions).map(
                                            ([group, items]) => (
                                                <fieldset
                                                    key={group}
                                                    className="space-y-3"
                                                >
                                                    <legend className="font-medium">
                                                        {group}
                                                    </legend>
                                                    <div className="grid gap-3 sm:grid-cols-2">
                                                        {items.map(
                                                            (permission) => (
                                                                <label
                                                                    key={
                                                                        permission.value
                                                                    }
                                                                    className="flex items-center gap-3 rounded-lg border p-3 text-sm"
                                                                >
                                                                    <Checkbox
                                                                        name="permissions[]"
                                                                        value={
                                                                            permission.value
                                                                        }
                                                                        defaultChecked={role.permissions.includes(
                                                                            permission.value,
                                                                        )}
                                                                    />
                                                                    {
                                                                        permission.label
                                                                    }
                                                                </label>
                                                            ),
                                                        )}
                                                    </div>
                                                </fieldset>
                                            ),
                                        )}
                                        <InputError
                                            message={errors.permissions}
                                        />
                                        <Button disabled={processing}>
                                            Save permissions
                                        </Button>
                                    </>
                                )}
                            </Form>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
