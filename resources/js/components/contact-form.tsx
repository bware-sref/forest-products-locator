// import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { toast } from "sonner"
import * as z from "zod"
import { storeContact } from "@/routes";
import { router } from '@inertiajs/react';

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
//   FieldDescription,
//   FieldError,
  FieldGroup,
//   FieldLabel,
//   FieldLegend,
//   FieldSet,
} from "@/components/ui/field"
import { ControlledInput } from "@/components/extend/controlled-input";
import { ControlledTextarea } from "@/components/extend/controlled-textarea";
import {
    contactFormSchema,
    type ContactFormData,
    doesZodRequire
} from "@/lib/zod-schemas";

export function ContactForm() {

    const headline = "Contact form";

    const form = useForm<ContactFormData>({
        resolver: zodResolver(contactFormSchema),
        mode: "onBlur",
        defaultValues: {
            name: '',
            email: '',
            subject: '',
            message: '',
        }
    });

    function onSubmit(data: ContactFormData) {
        // do stuff
        router.post(storeContact(), data, {
          /**
           * for the love of God, I finally found the type for flash! (ah ah)
           * PageFlashData defined(-ish) in inertiajs/core
           * @param flash PageFlashData
           */
            onFlash: (flash) => {
              console.log('flash: ', flash);
              if (flash.message) {
                toast.success("Email sent.", {
                  closeButton: true,
                  position: "top-center",
                  description: (
                    <p className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
                        { String(flash.message) }
                    </p>
                  ),
                });
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
            }
        });
    }

    return (
        <Card className="w-full sm:max-w-md mx-auto">
            <form id="form-contact" onSubmit={form.handleSubmit(onSubmit)}>
                <CardHeader>
                    <CardTitle>{headline}</CardTitle>
                    <CardDescription>
                    Provides a directory of primary and secondary forest products companies that produce products using raw forest material such as trees, logs, bark, etc.
                    <br />
                    Required fields are marked with an asterisk (<span className="text-destructive">*</span>).
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <FieldGroup>
        
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
                    <Button type="button" variant="outline" onClick={() => form.reset()}>
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