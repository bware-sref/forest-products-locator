/**
 * dialog-drawer.tsx
 * Renders a drawer/sheet on mobile and a dialog on desktop.
 */
"use client"

import { 
    type ComponentProps, 
    ReactNode, 
    useState,
} from "react";
import { cn } from "@/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@/components/ui/drawer";
import {
    DialogProps
} from "vaul";
import {
    Button,
} from "@/components/ui/button";
import {
    SlidersHorizontalIcon,
} from "lucide-react";

// export interface IDrawerProps extends DialogProps {

// }

export interface IDialogDrawerProps extends ComponentProps<'div'> {
    title?: string;
    description?: string;
    // classNames should probably be distinct, or at least able to be distinct
    // maybe these should just be props for each child element/component?
    // e.g., headerProps or even drawerHeaderProps
    // headerClassName?: string;
    // titleClassName?: string;
    // descriptionClassName?: string;
    // contentClassName?: string;
    // do we rule out a footer?
    // assumed to probably be a button
    children?: ReactNode;
    trigger?: ReactNode;
    drawerProps?: DialogProps;
    dialogProps?: DialogProps;
    triggerAsChild?: boolean;
    // drawer props?
    // - direction
    // - container
    // - onAnimationEnd
    // - dismissible
    // - handleOnly
    // - repositionInputs
    // dialog props?
    // shared props?
    // - modal
    // - defaultOpen
    // - open
    // - onOpenChange
    // autoFocus?
    // triggerProps? asChild
    drawerContentProps?: {};
    drawerDescriptionProps?: {};
    drawerHeaderProps?: {};
    drawerTitleProps?: {};
    dialogContentProps?: {};
    dialogDescriptionProps?: {};
    dialogHeaderProps?: {};
    dialogTitleProps?: {};
};

export function DialogDrawer({
    title = '',
    description = '',
    children,
    trigger,
    triggerAsChild = true,
    drawerContentProps,
    drawerDescriptionProps,
    drawerHeaderProps,
    drawerTitleProps,
    dialogContentProps,
    dialogDescriptionProps,
    dialogHeaderProps,
    dialogTitleProps,
    ...props
}: IDialogDrawerProps) {    
    const isMobile = useIsMobile();
    // these may conflict with the drawerProps
    // let's omit them for now and see what happens
    // const [open, setOpen] = useState<boolean>(props.drawerProps?.defaultOpen || false);

    // if props.drawerProps.open is not defined
    // if (props.drawerProps && typeof props.drawerProps?.open === undefined) {
    //     props.drawerProps.open = open;
    // }

    if (isMobile) {
        return (
            <Drawer 
                {...props.drawerProps}
            >
                <DrawerTrigger asChild={triggerAsChild}>
                    {trigger}
                </DrawerTrigger>
                <DrawerContent {...drawerContentProps}>
                    <DrawerHeader {...drawerHeaderProps}>
                        <DrawerTitle {...drawerTitleProps}>{title}</DrawerTitle>
                        <DrawerDescription {...drawerDescriptionProps}>{description}</DrawerDescription>
                    </DrawerHeader>
                    {children}
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Dialog 
            {...props.dialogProps}
        >
            <DialogTrigger asChild={triggerAsChild}>
                {trigger}                
            </DialogTrigger>
            <DialogContent {...dialogContentProps}>
                <DialogHeader {...dialogHeaderProps}>
                    <DialogTitle {...dialogTitleProps}>{title}</DialogTitle>
                    <DialogDescription {...dialogDescriptionProps}>{description}</DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
        
    );
}