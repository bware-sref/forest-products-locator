import {
    cn
} from "@/lib/utils"
import {
    ExternalToast,
} from "sonner";

// sonner doesn't export its Position type so let's just copy the values here
export type ToastPosition = 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'top-center' | 'bottom-center';
// sonner also includes, but doesn't export, a ToasterOptions type.
// Ours is more similar to its ToasterProps interface, which it does export but which has many additional properties,
// some of which we don't want to have to care about.
// It also exports a ToastT type, but again, more than we need at this time.
// We can always fallback to using ToasterProps or ToastT (or more likely a combination of subsets of the two) 
// if end up needing more options.
export interface ToastOptions {
    msg: string;
    className?: string;
    closeButton?: boolean;
    position?: ToastPosition;
}

// Funny thing:
// sonner toast actually calls the parameter built by this method "data", but types it as ExternalToast.
export function makeToastOptions({
    msg,
    className = '',
    closeButton = true,
    position = 'top-center',
} : ToastOptions) : ExternalToast {
    const defaultCn = "mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground";
    return {
        closeButton: closeButton,
        position: position,
        description: (
            <p className={cn(defaultCn, className)}>
                {msg}
            </p>
        ),
    };
}