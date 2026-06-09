import { usePage } from '@inertiajs/vue3'

type AuthUser = {
    id: number
    name: string
    email: string
    roles: string[]
    permissions: string[]
}

type PageProps = {
    auth?: {
        user?: AuthUser | null
    }
}

export function useAuth() {
    const page = usePage()

    const user = (page.props as PageProps).auth?.user ?? null

    function hasRole(role: string): boolean {
        return user?.roles?.includes(role) ?? false
    }

    function can(permission: string): boolean {
        return user?.permissions?.includes(permission) ?? false
    }

    return {
        user,
        hasRole,
        can,
    }
}