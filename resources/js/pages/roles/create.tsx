import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { create, index, store } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index() },
    { title: 'Create', href: create() },
];

export default function CreateRole() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create role" />
            <div className="mx-auto w-full max-w-xl p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Create role</CardTitle>
                        <CardDescription>
                            Name a job function. Permissions can be assigned
                            after creation.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()} className="space-y-5">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Role name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                            placeholder="e.g. Cashier"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="flex justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={index()}>Cancel</Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Create role
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
