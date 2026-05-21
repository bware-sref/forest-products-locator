/**
 * dialog-drawer.tsx
 * Renders a drawer/sheet on mobile and a dialog on desktop.
 */
"use client"

import { 
    type ComponentProps, 
    ReactNode, 
    // useState,
} from "react";
// import { cn } from "@/lib/utils";
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
    // DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@/components/ui/drawer";
import {
    DialogProps
} from "vaul";
// import {
//     Button,
// } from "@/components/ui/button";
import {
    // SlidersHorizontalIcon,
    X,
} from "lucide-react";


export interface IDialogDrawerProps extends ComponentProps<'div'> {
    title?: string;
    description?: string;
    children?: ReactNode;
    trigger?: ReactNode;
    drawerProps?: DialogProps;
    dialogProps?: DialogProps;
    triggerAsChild?: boolean;
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
                    {/**
                     * drawer wrapper
                     */}
                    <div data-thing="drawer-content-inner" className="w-screen p-0 overflow-y-auto md:max-h-[60vh] bg-nature">
                        <DrawerClose
                            className="text-beluga w-full flex flex-row items-end justify-end p-2 -mb-2"
                        >
                            <X data-icon="inline-end" size="32" className="" />
                        </DrawerClose>
                        {children}
                    </div>
                </DrawerContent>
            </Drawer>
        );
    }
X
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
                    <div className="overflow-y-auto">
                        {children}
                    </div>
            </DialogContent>
        </Dialog>
        
    );
}