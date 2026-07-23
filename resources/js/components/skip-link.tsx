import { cn } from "@/lib/utils";

export function SkipLink() {
    // not-sr-only stomps padding
    // to get around that, we'll add an inner span and padd that, dagnabbit!
    return (
        <a
            href="#main-content"
            className={cn(
                "sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50",
                "bg-background text-foreground font-medium shadow-md border rounded-xl",
                "focus:outline-none focus:ring-2 focus:ring-ring"                
            )}
        >
            <span className="px-6 py-4">Skip to main content</span>
        </a>
    );
}