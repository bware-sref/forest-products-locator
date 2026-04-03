"use client"

import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm, useWatch } from "react-hook-form"
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
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox"
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
  ControlledCombobox,
} from "@/components/extend/controlled-combobox";
import { ControlledInput } from "@/components/extend/controlled-input";
import { ControlledSelect } from "@/components/extend/controlled-select";
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

            <ControlledInput
              control={form.control}
              name="mill_name"
              label="Mill Name"
              placeholder=""
            />

            {/** Physical Address */}
            <FieldSet id="physical_address_wrap">
              <FieldLegend>Physical Address</FieldLegend>
                <ControlledInput
                  control={form.control}
                  name="physical_address"
                  label="Street Address"
                  placeholder=""
                />

              <div className="grid grid-cols-3 gap-4 mt-3">

                <ControlledInput
                  control={form.control}
                  name="physical_city"
                  label="City"
                  placeholder=""
                />

                <ControlledSelect
                  control={form.control}
                  name="state_id"
                  label="State"
                  items={states}
                  placeholder=""
                />

                <ControlledInput
                    control={form.control}
                    name="physical_zip"
                    label="ZIP"
                    placeholder=""
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

              <ControlledInput
                control={form.control}
                name="mailing_address"
                label="Street Address"
                placeholder=""
              />

              <div className="grid grid-cols-3 gap-4 mt-3">

                <ControlledInput
                  control={form.control}
                  name="mailing_city"
                  label="City"
                  placeholder=""
                />

                <ControlledSelect
                  control={form.control}
                  name="mailing_state_id"
                  label="State"
                  items={states}
                  placeholder=""
                />
                  
                <ControlledInput
                    control={form.control}
                    name="mailing_zip"
                    label="ZIP"
                    placeholder=""
                />

              </div>
            </FieldSet>{/** end mailing Address */}

            <ControlledInput 
              control={form.control}
              name="telephone"
              label="Telephone"
              placeholder=""
            />

            <ControlledInput
              control={form.control}
              name="fax"
              label="Fax"
              placeholder=""
            />

            <ControlledInput
              control={form.control}
              name="email"
              label="Email"
              placeholder=""
            />

            <ControlledInput
              control={form.control}
              name="web_site"
              label="Mill Website"
              placeholder="Please include the full URL, including http:// or https://"
            />

            <ControlledInput
              control={form.control}
              name="size"
              label="Mill Size"
              placeholder=""
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

            <ControlledInput
              control={form.control}
              name="submitter_email"
              label="Your Email"
              placeholder=""
            />

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
