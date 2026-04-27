import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide border transition-colors",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-brand-600 text-white",
        secondary:
          "border-paper-200 bg-paper-100 text-ink-600",
        destructive:
          "border-transparent bg-danger-600 text-white",
        outline:
          "border-paper-300 text-ink-700 bg-transparent",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
