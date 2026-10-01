import { cva } from "class-variance-authority"

export const navigationMenuTriggerStyle = cva(
  "group inline-flex h-9 w-max items-center justify-center px-4 py-2 text-sm font-medium hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground disabled:pointer-events-none disabled:opacity-50 ring-ring/10 dark:ring-ring/20 dark:outline-ring/40 outline-ring/50 transition-[color,box-shadow] focus-visible:ring-4 focus-visible:outline-1"
)