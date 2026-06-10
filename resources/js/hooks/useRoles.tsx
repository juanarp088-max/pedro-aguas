// hooks/useRoles.ts
import { usePage } from '@inertiajs/react';

export function useRoles() {
    const { auth } = usePage<any>().props;
    const userRoles = auth.user?.roles || [];
    
    return {
        isAdmin: userRoles.includes('admin'),
        isConsulta: userRoles.includes('consulta'),
        isRegistro: userRoles.includes('registro'),
    };
}