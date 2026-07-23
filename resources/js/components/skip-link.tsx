import { cn } from "@/lib/utils";

export function SkipLink() {
    return (
        <a
            href="#main-content"
            className={cn(
                "sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50",
                "bg-background text-foreground px-4 py-2 font-medium shadow-md border rounded-md",
                "focus:outline-none focus:ring-2 focus:ring-ring"                
            )}
        >Skip to main content</a>
    );
}