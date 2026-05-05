import type { VariantProps } from 'class-variance-authority'
import { cva } from 'class-variance-authority'

export { default as Alert } from './Alert.vue'
export { default as AlertDescription } from './AlertDescription.vue'
export { default as AlertTitle } from './AlertTitle.vue'

export const alertVariants = cva(
  'relative w-full rounded-[2px] border-y border-s-[3px] border-e p-3 text-[13px] [&>svg]:absolute [&>svg]:start-3 [&>svg]:top-3.5 [&>svg]:size-4 [&>svg+div]:translate-y-[-2px] [&>svg~*]:ps-7',
  {
    variants: {
      variant: {
        default:
          'border-y-brand-100 border-s-brand-500 border-e-brand-100 bg-brand-50 text-ink-800 [&>svg]:text-brand-600',
        destructive:
          'border-y-danger-100 border-s-danger-500 border-e-danger-100 bg-danger-50 text-ink-800 [&>svg]:text-danger-600',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>
