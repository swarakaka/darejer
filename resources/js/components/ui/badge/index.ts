import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center gap-1 rounded-[2px] px-1.5 py-[1px] text-[10px] font-semibold uppercase tracking-[0.04em] border transition-colors",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-brand-500 text-white",
        secondary:
          "border-paper-200 bg-paper-100 text-ink-700",
        destructive:
          "border-transparent bg-danger-500 text-white",
        outline:
          "border-ink-500 text-ink-700 bg-transparent",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
