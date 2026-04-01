"use client"

import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm } from "react-hook-form"
import { toast } from "sonner"
import * as z from "zod"
import { storeMill } from "@/routes";
import { router } from '@inertiajs/react';

import {
    type State,
    type County,
    type MillType,
    type WoodSpecies,
} from '@/types';
import {
    // type CountiesByState,
    // buildCountiesByState,
    normalizeStates,
} from '@/hooks/use-mills';
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
//   FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
// import {
//   InputGroup,
//   InputGroupAddon,
//   InputGroupText,
//   InputGroupTextarea,
// } from "@/components/ui/input-group"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import {
    millFormSchema
} from '@/lib/zod-schemas';


type MillFormData = z.infer<typeof millFormSchema>;

/**
 * 
 * Need to add props so we can pass states, counties, mill types, and wood species into this mess.
 * On top of that, we also need to be able to pass values for each field.
 */
export interface MillFormProps {
    headline?: string;
    states: State[];
    counties?: County[];
    millTypes: MillType[];
    woodSpecies: WoodSpecies[];
    // don't know if we even need form data
    // how are validation errors pushed back to the view?
    // Inertia sends errors in page.errors
    formData?: object;
}

export function MillForm({...props}: MillFormProps) {
    const headline = props.headline || 'Add Your Business';
    const states = React.useMemo(() => normalizeStates(props.states), [props.states]);
    // const countiesByState = React.useMemo(() => buildCountiesByState(props.states), [props.states]);

    // const form = useForm<z.infer<typeof millFormSchema>>({
    const form = useForm<MillFormData>({
        resolver: zodResolver(millFormSchema),
        mode: 'onBlur',
        defaultValues: {
            // I want to extract these into the zod-schemas file as well
            mill_name: "",      
            physical_address: "",
            physical_city: "",
            state_id: '',
            physical_zip: '',
            telephone: '',
            fax: '',
            email: '',
            web_site: '',
            size: '',
        },
    });

    function onSubmit(data: z.infer<typeof millFormSchema>) {
        /**
         * toast will silently fail if the page does not also include a Toaster component somewhere.
         * To wit, I have added Toaster to the bottom of app-layout.
         */
        // toast("You submitted the following values:", {
        //     closeButton: true,
        //     duration: Infinity,
        //     description: (
        //         <pre className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
        //         <code>{JSON.stringify(data, null, 2)}</code>
        //         </pre>
        //     ),
        //     position: "bottom-right",
        //     classNames: {
        //         content: "flex flex-col gap-2",
        //     },
        //     style: {
        //         "--border-radius": "calc(var(--radius)  + 4px)",
        //     } as React.CSSProperties,
        // })

        router.post(storeMill(), data, {
            onError: (errors) => {
                // Manually map Inertia server side errors bak to React Hook Form
                Object.keys(errors).forEach((key) => {
                    form.setError(key as keyof z.infer<typeof millFormSchema>, {
                        type: 'server',
                        message: errors[key],
                    })
                })
            }
        })
    }

  return (
    <Card className="w-full sm:max-w-md mx-auto">
      <CardHeader>
        <CardTitle>{headline}</CardTitle>
        <CardDescription>
          Help us improve by submitting mills that are not in our system.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form id="form-submit-mill" onSubmit={form.handleSubmit(onSubmit)}>
          <FieldGroup>
            <Controller
              name="mill_name"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor={field.name}>
                    Mill Name
                  </FieldLabel>
                  <Input
                    {...field}
                    id={field.name} //"mill_name"
                    aria-invalid={fieldState.invalid}
                    placeholder="Sawyer's Mill"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="physical_address"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="physical_address">
                    Address
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="physical_address"
                    aria-invalid={fieldState.invalid}
                    placeholder="123 Sawyer's Mill Rd"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />

            <div className="grid grid-cols-3 gap-4">
                <Controller
                name="physical_city"
                control={form.control}
                render={({ field, fieldState }) => (
                    <Field data-invalid={fieldState.invalid}>
                    <FieldLabel htmlFor="physical_city">
                        City
                    </FieldLabel>
                    <Input
                        {...field}                    
                        id="physical_city"
                        aria-invalid={fieldState.invalid}
                        placeholder="Milltown"
                        autoComplete="off"
                    />
                    {fieldState.invalid && (
                        <FieldError errors={[fieldState.error]} />
                    )}
                    </Field>
                )}
                />

                <Controller
                    name="state_id"
                    control={form.control}
                    render={({field, fieldState}) => (
                        <Field
                            orientation="responsive"
                            data-invalid={fieldState.invalid}
                        >
                            <FieldLabel htmlFor="state_id">
                                State
                            </FieldLabel>
                            <Select
                                name={field.name}
                                value={field.value}                                
                                onValueChange={field.onChange}
                            >
                                <SelectTrigger
                                    id="state_id"
                                    aria-invalid={fieldState.invalid}
                                    className="min-w-25"
                                >
                                    <SelectValue placeholder="State" />
                                </SelectTrigger>
                                <SelectContent>
                                    {states.map((state) => (
                                        <SelectItem key={state.value} value={String(state.value)}>
                                            {state.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {fieldState.invalid && (
                                <FieldError errors={[fieldState.error]} />
                            )}
                        </Field>
                    )}
                />
                        
                <Controller
                    name="physical_zip"
                    control={form.control}
                    render={({ field, fieldState }) => (
                        <Field data-invalid={fieldState.invalid}>
                        <FieldLabel htmlFor="physical_zip">
                            ZIP
                        </FieldLabel>
                        <Input
                            {...field}                    
                            id="physical_zip"
                            aria-invalid={fieldState.invalid}
                            placeholder="90210"
                            autoComplete="off"
                        />
                        {fieldState.invalid && (
                            <FieldError errors={[fieldState.error]} />
                        )}
                        </Field>
                    )}
                />
            </div>
            <Controller
              name="telephone"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="telephone">
                    Telephone
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="telephone"
                    aria-invalid={fieldState.invalid}
                    placeholder="555.867.5309"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="fax"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="fax">
                    Fax
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="fax"
                    aria-invalid={fieldState.invalid}
                    placeholder="555.867.5309"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="email"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="email">
                    Email
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="email"
                    aria-invalid={fieldState.invalid}
                    placeholder="contact@emill.lol"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="web_site"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="web_site">
                    Website URL
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="web_site"
                    aria-invalid={fieldState.invalid}
                    placeholder="https://primary.forestproductslocator.org"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="size"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="size">
                    Mill Size
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id="size"
                    aria-invalid={fieldState.invalid}
                    placeholder="2?"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
{/**
            <Controller
              name="description"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor="form-rhf-demo-description">
                    Description
                  </FieldLabel>
                  <InputGroup>
                    <InputGroupTextarea
                      {...field}
                      id="form-rhf-demo-description"
                      placeholder="These is a placeholder..."
                      rows={6}
                      className="min-h-24 resize-none"
                      aria-invalid={fieldState.invalid}
                    />
                    <InputGroupAddon align="block-end">
                      <InputGroupText className="tabular-nums">
                        {field.value ? field.value.length : 0}/100 characters
                      </InputGroupText>
                    </InputGroupAddon>
                  </InputGroup>
                  <FieldDescription>
                    FieldDescription placeholder
                  </FieldDescription>
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
 */}            
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Field orientation="horizontal">
          <Button type="button" variant="outline" onClick={() => form.reset()}>
            Reset
          </Button>
          <Button type="submit" form="form-submit-mill">
            Submit
          </Button>
        </Field>
      </CardFooter>
    </Card>
  )
}
