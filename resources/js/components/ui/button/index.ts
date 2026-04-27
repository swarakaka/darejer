import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-sm text-sm font-medium transition-colors duration-100 cursor-pointer disabled:pointer-events-none disabled:opacity-50 focus:outline-none focus-visible:ring-1 focus-visible:ring-brand-500 focus-visible:ring-offset-1 [&_svg]:pointer-events-none [&_svg]:size-3.5 [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        default:
          "bg-brand-600 text-white hover:bg-brand-700 border border-transparent",
        destructive:
          "bg-danger-600 text-white hover:bg-danger-700 border border-transparent",
        outline:
          "border border-paper-300 bg-white text-ink-700 hover:bg-paper-100",
        secondary:
          "bg-paper-100 text-ink-700 hover:bg-paper-200 border border-transparent",
        ghost:
          "bg-transparent text-ink-600 hover:bg-paper-100 border border-transparent",
        link:
          "text-brand-600 underline-offset-4 hover:underline border border-transparent",
      },
      size: {
        "default": "h-8 px-3",
        "sm":      "h-7 px-2.5",
        "lg":      "h-9 px-4",
        "icon":    "h-8 w-8",
        "icon-sm": "h-7 w-7",
        "icon-lg": "h-9 w-9",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
