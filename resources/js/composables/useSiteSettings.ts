import { usePage } from '@inertiajs/vue3'
import type { SharedData } from '@/types/auth'

export function useSiteSettings() {
    const page = usePage<SharedData>()

    return {
        siteName:
            page.props.site?.name ?? 'Unclad Collection',

        tagline:
            page.props.site?.tagline ?? '',

        theme:
            page.props.site?.theme ?? 'professional',
    }
}