import type { Component } from 'vue'
import AppLayout from './AppLayout.vue'
import MinimalLayout from './MinimalLayout.vue'

// Registry of layouts that Screen.vue can pick at runtime based on the
// `layout` prop emitted by Screen::layout('...') on the PHP side. Hosts
// that need additional layouts should fork the bootstrap call and merge
// their own components into this map.
export const layouts: Record<string, Component> = {
  app: AppLayout,
  minimal: MinimalLayout,
}

export type LayoutName = keyof typeof layouts
