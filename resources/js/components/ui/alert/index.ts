import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Alert } from "./Alert.vue"
export { default as AlertDescription } from "./AlertDescription.vue"
export { default as AlertTitle } from "./AlertTitle.vue"

export const alertVariants = cva(
  "relative w-full rounded-[2px] border-l-[3px] border-y border-r p-3 text-[13px] [&>svg~*]:ps-7 [&>svg+div]:translate-y-[-2px] [&>svg]:absolute [&>svg]:start-3 [&>svg]:top-3.5 [&>svg]:size-4",
  {
    variants: {
      variant: {
        default:
          "bg-brand-50 border-y-brand-100 border-r-brand-100 border-l-brand-500 text-ink-800 [&>svg]:text-brand-600",
        destructive:
          "bg-danger-50 border-y-danger-100 border-r-danger-100 border-l-danger-500 text-ink-800 [&>svg]:text-danger-600",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>
