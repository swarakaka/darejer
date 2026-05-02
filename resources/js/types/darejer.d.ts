// ─── Ziggy `route()` global ──────────────────────────────────────────────────
// ZiggyVue registers `route` on window and as a Vue global property.
import type { route as ziggyRoute } from 'ziggy-js'

declare global {
    // eslint-disable-next-line no-var
    var route: typeof ziggyRoute
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute
    }
}

// ─── Inertia Shared Props ────────────────────────────────────────────────────
//
// Note: Inertia v3 flash data lives at the top-level page key
// (`usePage().flash`), not under shared props. See FlashProps below.
export interface DarejerSharedProps {
    darejer: {
        languages:        string[]
        default_language: string
        locale?:          string
        direction?:       'ltr' | 'rtl'
        is_rtl?:          boolean
        directions?:      Record<string, 'ltr' | 'rtl'>
    }
    auth: {
        user: DarejerUser | null
    }
    navigation:  NavItem[]
    breadcrumbs: Breadcrumb[]
    [key: string]: unknown
}

export interface NavItem {
    label:       string
    icon?:       string
    route?:      string
    url?:        string
    active?:     boolean
    group?:      string
    badge?:      string
    badgeColor?: 'info' | 'success' | 'warning' | 'danger'
    children?:   NavItem[]
}

export interface Breadcrumb {
    label: string
    url?:  string
}

export interface DarejerUser {
    id:           number
    username:     string
    email:        string
    permissions:  string[]
    roles:        string[]
    isSuperAdmin: boolean
}

// ─── Screen Props ─────────────────────────────────────────────────────────────
export interface ScreenProps {
    title:       string
    dialog:      boolean
    dialogSize?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full' | null
    fullWidth?:  boolean | null
    record:      Record<string, unknown>
    components:  DarejerComponent[]
    actions:     DarejerAction[]
    errors?:     Record<string, string>
    breadcrumbs?: Breadcrumb[]
}

export interface DarejerComponent {
    type:      string
    name:      string
    label:     string
    visible:   boolean
    required?: boolean
    hint?:     string
    tooltip?:  string
    dependOn?: DependOnRule
    [key: string]: unknown
}

export interface DarejerAction {
    type:        string
    label:       string
    url?:        string
    method?:     string
    dialog?:     boolean
    icon?:       string
    confirm?:    string
    variant?:    'default' | 'destructive' | 'outline' | 'ghost' | 'secondary' | 'link'
    disabled?:   boolean
    tooltip?:    string
    fullWidth?:  boolean
    external?:   boolean
    modalSize?:  string
    form?:       {                   // ModalToggle with inline form
        title:      string
        components: DarejerComponent[]
        actions:    DarejerAction[]
        record:     Record<string, unknown>
    }
    items?:      DarejerAction[]     // Dropdown
    batchUrl?:   string              // BulkAction
    batchParam?: string              // BulkAction
    dependOn?:   DependOnRule        // client-side visibility
    [key: string]: unknown
}

// ── DependOn ──────────────────────────────────────────────────────────────
export type DependOnOperator =
    | 'eq'
    | 'neq'
    | 'gt'
    | 'gte'
    | 'lt'
    | 'lte'
    | 'in'
    | 'notIn'
    | 'contains'
    | 'notEmpty'
    | 'empty'

export interface DependOnCondition {
    field:    string
    operator: DependOnOperator
    value?:   unknown
}

export interface DependOnRule {
    // Simple form — single condition. Backwards compatible with 08's shape.
    field?:    string
    operator?: DependOnOperator
    value?:    unknown

    // Complex form — multiple conditions combined with AND/OR.
    conditions?: DependOnCondition[]
    logic?:      'and' | 'or'
}

export interface ScreenSection {
    title:           string
    components:      string[]
    collapsed?:      boolean
    alwaysExpanded?: boolean
    dependOn?:       DependOnRule
}

export interface ScreenTab {
    title:      string
    components: string[]
    dependOn?:  DependOnRule
}

export interface FlashProps {
    success: string | null
    error:   string | null
    warning: string | null
    info:    string | null
}

// ─── Pagination ───────────────────────────────────────────────────────────────
export interface PaginatedData<T = Record<string, unknown>> {
    data:         T[]
    current_page: number
    last_page:    number
    per_page:     number
    total:        number
    from:         number
    to:           number
    links:        PaginationLink[]
}

export interface PaginationLink {
    url:    string | null
    label:  string
    active: boolean
}
