/**
 * contact-form.tsx
 */
"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import {
    Control,
    useForm,
} from "react-hook-form"
import { 
    toast,
} from "sonner"
import {
    makeToastOptions
} from "@/lib/tsx-utils";
import * as z from "zod"
import { store as storeContact } from "@/routes/contacts";
import { router } from '@inertiajs/react';
import { RequestPayload } from '@inertiajs/core';
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Field,
  FieldGroup,
} from "@/components/ui/field"
import { ControlledInput } from "@/components/extend/controlled-input";
import { ControlledTextarea } from "@/components/extend/controlled-textarea";
import {
    HoneypotFields,
} from "@/components/extend/honeypot-fields"
import {
    contactFormSchema,
    type ContactFormData,
    doesZodRequire
} from "@/lib/zod-schemas";
import {
    type IHoneypot,
} from "@/types";

export interface ContactFormProps {
    headline?: string;
    description?: string;
    honeypot: IHoneypot;
}

export function ContactForm({
    headline = 'Fill in this form to contact site administrators.',
    description = 'Provides a directory of primary and secondary forest products companies that produce products using raw forest material such as trees, logs, bark, etc.',
    honeypot
}:ContactFormProps) {
    
    const {
        formState,
        reset,
        ...form
    } = useForm<ContactFormData>({
        resolver: zodResolver(contactFormSchema),
        mode: "onBlur",
        defaultValues: {
            name: '',
            email: '',
            subject: '',
            message: '',
            [honeypot.nameFieldName]: '', // test spam-blocking with a non-empty value
            [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
        }
    });

    function onSubmit(data: ContactFormData) {
        // do stuff
        router.post(storeContact(), data as unknown as RequestPayload, {
          /**
           * for the love of God, I finally found the type for flash! (ah ah)
           * PageFlashData defined(-ish) in inertiajs/core
           * @param flash PageFlashData
           */
            onFlash: (flash) => {
            //   console.log('flash: ', flash);
              if (flash.message) {

                if (flash.type && flash.type === 'error') {
                    toast.error(
                        "An error occurred...",
                        makeToastOptions({
                            msg: String(flash.message),
                            className: 'text-red-700',
                        })
                    );
                    return;
                }

                toast.success(
                    "Contact request sent.",
                    makeToastOptions({
                        msg: String(flash.message),
                    })
                );
              }
            },
            onError: (errors) => {
                // Manually map Inertia server side errors bak to React Hook Form
                Object.keys(errors).forEach((key) => {
                    form.setError(key as keyof z.infer<typeof contactFormSchema>, {
                        type: 'server',
                        message: errors[key],
                    });
                });
            },
            // use onSuccess() to trigger form reset.
            onSuccess: () => {
                reset();
            }
        });
    }

    return (
        <Card className="w-full sm:max-w-md mx-auto">
            <form id="form-contact" onSubmit={form.handleSubmit(onSubmit)}>
                <CardHeader>
                    <CardTitle>{headline}</CardTitle>
                    <CardDescription>
                        {description !== '' && (
                            <p>{description}</p>
                        )}
                        <p className="my-3">
                            Required fields are marked with an asterisk (<span className="text-destructive">*</span>).
                        </p>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <FieldGroup>
                        <HoneypotFields 
                            honeypot={honeypot}
                            control={form.control as unknown as Control}
                        />
                        {/* {honeypot.enabled && (
                            <div className="hidden" aria-hidden="true">
                                <ControlledInput 
                                    control={form.control}
                                    name={honeypot.nameFieldName}
                                    label=""
                                    placeholder=""
                                    required={false}
                                    autocomplete="off"
                                />
                                <ControlledInput 
                                    control={form.control}
                                    name={honeypot.validFromFieldName}
                                    label=""
                                    placeholder=""
                                    required={false}
                                    autocomplete="off"
                                />
                            </div>
                        )} */}
                        <ControlledInput
                            control={form.control}
                            name="name"
                            label="Name"
                            placeholder=""
                            required={doesZodRequire(contactFormSchema, 'name')}
                        />

                        <ControlledInput
                            control={form.control}
                            name="email"
                            label="Email"
                            placeholder=""
                            required={doesZodRequire(contactFormSchema, 'email')}
                        />
                        <ControlledInput
                            control={form.control}
                            name="subject"
                            label="Subject"
                            placeholder=""
                            required={doesZodRequire(contactFormSchema, 'subject')}
                        />
                        <ControlledTextarea
                            control={form.control}
                            name="message"
                            label="Message"
                            placeholder=""
                            required={doesZodRequire(contactFormSchema, 'message')}
                        />

                    </FieldGroup>
                </CardContent>
                <CardFooter className="mt-6">
                    <Field orientation="horizontal">
                    <Button type="button" variant="outline" onClick={() => reset()}>
                        Reset
                    </Button>
                    <Button type="submit" form="form-contact">
                        Submit
                    </Button>
                    </Field>
                </CardFooter>
            </form>
        </Card>
    );
}