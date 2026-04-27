import { computed } from 'vue'
import { usePage }  from '@inertiajs/vue3'
import type { DarejerSharedProps } from '@/types/darejer'

/**
 * Client-side permission checking composable.
 *
 * Reads `auth.user.permissions` + `auth.user.roles` from Inertia shared props
 * (populated by DarejerServiceProvider). UI-only — never trust these for
 * security, always enforce on the server.
 */
export function usePermissions() {
    const page = usePage<DarejerSharedProps>()

    const user = computed(() => page.props.auth?.user ?? null)

    const permissions = computed((): string[] =>
        user.value?.permissions ?? []
    )

    const roles = computed((): string[] =>
        user.value?.roles ?? []
    )

    const isSuperAdmin = computed(() =>
        !!user.value?.isSuperAdmin || roles.value.includes('super-admin')
    )

    function can(permission: string): boolean {
        if (isSuperAdmin.value) return true
        return permissions.value.includes(permission)
    }

    function canAny(...perms: string[]): boolean {
        if (isSuperAdmin.value) return true
        return perms.some(p => permissions.value.includes(p))
    }

    function canAll(...perms: string[]): boolean {
        if (isSuperAdmin.value) return true
        return perms.every(p => permissions.value.includes(p))
    }

    function hasRole(role: string): boolean {
        return roles.value.includes(role)
    }

    function hasAnyRole(...roleList: string[]): boolean {
        return roleList.some(r => roles.value.includes(r))
    }

    return {
        user,
        permissions,
        roles,
        isSuperAdmin,
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
    }
}
