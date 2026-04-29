import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-[2px] text-[13px] font-semibold transition-colors duration-100 cursor-pointer disabled:pointer-events-none disabled:opacity-50 focus:outline-none focus-visible:outline-1 focus-visible:outline-offset-1 focus-visible:outline-brand-500 [&_svg]:pointer-events-none [&_svg]:size-3.5 [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        default:
          "bg-brand-500 text-white hover:bg-brand-600 active:bg-brand-700 border border-transparent",
        destructive:
          "bg-danger-500 text-white hover:bg-danger-600 active:bg-danger-700 border border-transparent",
        outline:
          "border border-ink-700 bg-white text-ink-900 hover:bg-paper-100 active:bg-paper-150",
        secondary:
          "bg-paper-150 text-ink-900 hover:bg-paper-200 active:bg-paper-300 border border-transparent",
        ghost:
          "bg-transparent text-ink-900 hover:bg-paper-150 active:bg-paper-200 border border-transparent",
        link:
          "text-brand-600 underline-offset-2 hover:underline hover:text-brand-700 border border-transparent",
      },
      size: {
        "default": "h-8 px-4",
        "sm":      "h-7 px-3",
        "lg":      "h-9 px-5",
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
