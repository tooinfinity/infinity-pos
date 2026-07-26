import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: FlashToast;
        };
        sharedPageProps: {
            name: string;
            auth: {
                user: {
                    id: string;
                    name: string;
                    email: string;
                    is_active: boolean;
                    roles: string[];
                    permissions: string[];
                    created_at: string;
                    updated_at: string;
                    avatar?: string;
                } | null;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
