import { usePage } from '@inertiajs/react';
import managementRoutes from '@/routes/pt-sessions';
import trainerRoutes from '@/routes/trainer/pt-sessions';

export function usePtSessionRoutes() {
    const { auth } = usePage().props;

    return auth.role === 'trainer' ? trainerRoutes : managementRoutes;
}
