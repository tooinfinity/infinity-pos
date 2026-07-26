import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
import { create, index, store } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type Props = {
    roles: Array<{ value: string; label: string }>;
    canAssignRoles: boolean;
};
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index() },
    { title: 'Create', href: create() },
];

export default function CreateUser({ roles, canAssignRoles }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create user" />
            <div className="mx-auto w-full max-w-3xl p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Create employee account</CardTitle>
                        <CardDescription>
                            Set initial credentials and access roles. The
                            employee can change their password later.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...store.form()}
                            transform={(data) => ({
                                ...data,
                                roles: Array.isArray(data.roles)
                                    ? data.roles
                                    : [],
                            })}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Full name
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                                autoFocus
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                required
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="password">
                                                Temporary password
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
                                    </div>
                                    <fieldset className="space-y-3">
                                        <legend className="text-sm font-medium">
                                            Roles
                                        </legend>
                                        {canAssignRoles && roles.length > 0 ? (
                                            <div className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
                                                {roles.map((role) => (
                                                    <label
                                                        key={role.value}
                                                        className="flex items-center gap-3 text-sm"
                                                    >
                                                        <Checkbox
                                                            name="roles[]"
                                                            value={role.value}
                                                        />
                                                        {role.label}
                                                    </label>
                                                ))}
                                            </div>
                                        ) : null}
                                        <InputError message={errors.roles} />
                                    </fieldset>
                                    <div className="flex justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={index()}>Cancel</Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Create user
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
