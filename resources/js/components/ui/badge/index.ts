import type { VariantProps } from 'class-variance-authority'
import { cva } from 'class-variance-authority'

export { default as Badge } from './Badge.vue'

export const badgeVariants = cva(
  'inline-flex items-center gap-1 rounded-[2px] border px-1.5 py-[1px] text-[10px] font-semibold tracking-[0.04em] uppercase transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-brand-500 text-white',
        secondary: 'border-paper-200 bg-paper-100 text-ink-700',
        destructive: 'border-transparent bg-danger-500 text-white',
        outline: 'border-ink-500 bg-transparent text-ink-700',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
