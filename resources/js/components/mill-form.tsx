"use client"

import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm, useWatch } from "react-hook-form"
import { toast } from "sonner"
import * as z from "zod"
import { storeMill } from "@/routes";
import { router } from '@inertiajs/react';
// import { FlashData, GlobalEvent } from '@inertiajs/core';

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
} from "@/components/ui/card";
import {
  Field,
//   FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldLegend,
  FieldSet,
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
  ControlledCombobox,
  // type ControlledComboboxProps,
  // type HasValueAndLabel,
} from "@/components/extend/controlled-combobox";


import {
    millFormSchema
} from '@/lib/zod-schemas';
import { Checkbox } from "@/components/ui/checkbox"


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
            // I'd love to extract defaultValues into the zod-schemas file as well
            mill_name: "",      
            physical_address: "",
            physical_city: "",
            state_id: '',
            physical_zip: '',
            mailing_address_same_as_physical: true,
            mailing_address: "",
            mailing_city: "",
            mailing_state_id: '',
            mailing_zip: '',
            telephone: '',
            fax: '',
            email: '',
            web_site: '',
            size: '',
            mill_types: [],
            wood_species: [],
            submitter_email: '',
        },
    });

    const watchMailingSameAsPhysical = useWatch({
        control: form.control,
        name: "mailing_address_same_as_physical",
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
          /**
           * for the love of God, I finally found the type for flash! (ah ah)
           * PageFlashData defined(-ish) in inertiajs/core
           * @param flash PageFlashData
           */
            onFlash: (flash) => {
              console.log('flash: ', flash);
              if (flash.message) {
                toast("The server responded: ", {
                  closeButton: true,
                  position: "top-center",
                  description: (
                    <pre className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
                    <code>{JSON.stringify(flash, null, 2)}</code>
                    </pre>
                  ),
                });
              }
            },
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
                    id={field.name}
                    aria-invalid={fieldState.invalid}
                    placeholder=""
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />

            {/** Physical Address */}
            <FieldSet id="physical_address_wrap">
              <FieldLegend>Physical Address</FieldLegend>
              <Controller
                name="physical_address"
                control={form.control}
                render={({ field, fieldState }) => (
                  <Field data-invalid={fieldState.invalid}>
                    <FieldLabel htmlFor={field.name}>
                      Street Address
                    </FieldLabel>
                    <Input
                      {...field}                    
                      id={field.name}
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

              <div className="grid grid-cols-3 gap-4 mt-3">

                  <Controller
                  name="physical_city"
                  control={form.control}
                  render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                      <FieldLabel htmlFor={field.name}>
                          City
                      </FieldLabel>
                      <Input
                          {...field}                    
                          id={field.name}
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
                              <FieldLabel htmlFor={field.name}>
                                  State
                              </FieldLabel>
                              <Select
                                  name={field.name}
                                  value={field.value}                                
                                  onValueChange={field.onChange}
                              >
                                  <SelectTrigger
                                      id={field.name}
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
                          <FieldLabel htmlFor={field.name}>
                              ZIP
                          </FieldLabel>
                          <Input
                              {...field}                    
                              id={field.name}
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
            </FieldSet>
            {/** end Physical Address */}

            <Controller
              name="mailing_address_same_as_physical"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field orientation="horizontal">
                  <Checkbox 
                    id={field.name}  
                    name={field.name}                    
                    checked={field.value}
                    onCheckedChange={field.onChange}
                    aria-controls="mailing_address_wrap"
                  />
                  <FieldLabel htmlFor="mailing_address_same_as_physical">Is the mailing address the same as the physical address?</FieldLabel>
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />

            {/** Mailing Address */}
            <FieldSet id="mailing_address_wrap" className={!watchMailingSameAsPhysical ? '' : 'hidden'}>
              <FieldLegend>Mailing Address</FieldLegend>
              <Controller
                name="mailing_address"
                control={form.control}
                render={({ field, fieldState }) => (
                  <Field data-invalid={fieldState.invalid}>
                    <FieldLabel htmlFor={field.name}>
                      Street Address
                    </FieldLabel>
                    <Input
                      {...field}                    
                      id={field.name}
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

              <div className="grid grid-cols-3 gap-4 mt-3">

                  <Controller
                  name="mailing_city"
                  control={form.control}
                  render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                      <FieldLabel htmlFor={field.name}>
                          City
                      </FieldLabel>
                      <Input
                          {...field}                    
                          id={field.name}
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
                      name="mailing_state_id"
                      control={form.control}
                      render={({field, fieldState}) => (
                          <Field
                              orientation="responsive"
                              data-invalid={fieldState.invalid}
                          >
                              <FieldLabel htmlFor={field.name}>
                                State
                              </FieldLabel>
                              <Select
                                  name={field.name}
                                  value={field.value}                                
                                  onValueChange={field.onChange}
                              >
                                  <SelectTrigger
                                      id={field.name}
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
                      name="mailing_zip"
                      control={form.control}
                      render={({ field, fieldState }) => (
                          <Field data-invalid={fieldState.invalid}>
                          <FieldLabel htmlFor={field.name}>
                              ZIP
                          </FieldLabel>
                          <Input
                              {...field}                    
                              id={field.name}
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
            </FieldSet>{/** end mailing Address */}

            <Controller
              name="telephone"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor={field.name}>
                    Telephone
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
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
                  <FieldLabel htmlFor={field.name}>
                    Fax
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
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
                  <FieldLabel htmlFor={field.name}>
                    Email
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
                    aria-invalid={fieldState.invalid}
                    placeholder="mill.email@example.com"
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
                  <FieldLabel htmlFor={field.name}>
                    Website URL
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
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
                  <FieldLabel htmlFor={field.name}>
                    Mill Size
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
                    aria-invalid={fieldState.invalid}
                    placeholder="Small? Medium? Large? 2?"
                    autoComplete="off"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />

            {/** we need to use combobox to allow multiple selections */}
            <ControlledCombobox
              control={form.control}
              name="mill_types"
              label="Mill Type"
              items={props.millTypes}
              itemToValue={(millType) => String(millType.id)}
              itemToLabel={(millType) => millType.label || millType.name}
              multiple
              placeholder="Select mill types"
            />

            <ControlledCombobox
              control={form.control}
              name="wood_species"
              label="Wood Species"
              items={props.woodSpecies}
              itemToValue={(woodSpecies) => String(woodSpecies.id)}
              itemToLabel={(woodSpecies) => woodSpecies.label || woodSpecies.name}
              multiple              
            />

            <Controller
              name="submitter_email"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel htmlFor={field.name}>
                    Your Email
                  </FieldLabel>
                  <Input
                    {...field}                    
                    id={field.name}
                    aria-invalid={fieldState.invalid}
                    placeholder="your.email@example.com"
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
