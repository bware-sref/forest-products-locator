import * as React from "react"
import { cn } from "cn"
import { ChevronDownIcon } from "lucide-react"
import { Accordion as AccordionPrimitive } from "radix-ui"

// Extension of Shadcn Accordion (the one based on Radix UI's Accordion)
// Hail Eris!
// All hail Discordia!
// This component exists because Shadcn Accordion fails to provide access to several things.
// Notably, it does not provide a means to override the icon or the icon's styles, preventing resize or coloring.
// Also, the way it bundles Header, Trigger, and Icon together prevents overriding the default usage of <h3> for the heading.

function Discordion({
  ...props
}: React.ComponentProps<typeof AccordionPrimitive.Root>) {
  return <AccordionPrimitive.Root data-slot="accordion" {...props} />
}

function DiscordionItem({
  className,
  ...props
}: React.ComponentProps<typeof AccordionPrimitive.Item>) {
  return (
    <AccordionPrimitive.Item
      data-slot="accordion-item"
      className={cn("border-b last:border-b-0", className)}
      {...props}
    />
  )
}

// function DiscordionHeader({
//   className,
//   children,
//   ...props
// }: React.ComponentProps<typeof AccordionPrimitive.Header>) {
//   return (
//     <AccordionPrimitive.Header
//       data-slot="accordion-header"
//       className={cn("flex", className)}
//       {...props}
//     >
//       {children}
//     </AccordionPrimitive.Header>
//   );
// }

// function DiscordionTrigger({
//   className,
//   children,
//   ...props
// }: React.ComponentProps<typeof AccordionPrimitive.Trigger>) {
//   return (
//     <AccordionPrimitive.Trigger
//       data-slot="accordion-trigger"
//       className={cn(
//         "flex flex-1 items-start justify-between gap-4 rounded-md py-4 text-left text-sm font-medium transition-all outline-none hover:underline focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 [&[data-state=open]>svg]:rotate-180",
//         className
//       )}
//       {...props}
//     >
//       {children}
//     </AccordionPrimitive.Trigger>
//   );
// }

// make an interface that extends AccordionPrimitive.Trigger props
interface DiscordionTriggerProps extends React.ComponentProps<typeof AccordionPrimitive.Trigger> {
  // heading element to render instead of the default of h3
  asHeading?: "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
  icon?: React.ReactNode;
}

// named so because the original hobo bundled them all together
// we don't want that so we made our own
function DiscordionTrigger({
  className,
  children,
  asHeading: Heading = "h3",
  icon,
  ...props
}: DiscordionTriggerProps) {
  return (
    // use asChild on Header to cause it to render whatever tag Heading is instead of the default <h3>.
    <AccordionPrimitive.Header asChild className="flex">
      <Heading>
        <AccordionPrimitive.Trigger
          data-slot="accordion-trigger"
          className={cn(
            "flex flex-1 items-start justify-between gap-4 rounded-md py-4 text-left text-sm font-medium transition-all outline-none hover:underline focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 [&[data-state=open]>svg]:rotate-180",
            className
          )}
          {...props}
        >
          {children}
          {icon ? (
            icon
          ) : (
            <ChevronDownIcon className="pointer-events-none size-4 shrink-0 translate-y-0.5 text-muted-foreground transition-transform duration-200" />
          )}
        </AccordionPrimitive.Trigger>
      </Heading>
    </AccordionPrimitive.Header>
  )
}

function DiscordionContent({
  className,
  children,
  ...props
}: React.ComponentProps<typeof AccordionPrimitive.Content>) {
  return (
    <AccordionPrimitive.Content
      data-slot="accordion-content"
      className="overflow-hidden text-sm data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down"
      {...props}
    >
      <div className={cn("pt-0 pb-4", className)}>{children}</div>
    </AccordionPrimitive.Content>
  )
}

export {
  Discordion,
  DiscordionItem,
  // DiscordionHeader,
  DiscordionTrigger,
  // DiscordionHeaderTriggerIcon,
  DiscordionContent
}
