/**
 * mill-form.tsx
 */
"use client"

import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm, useWatch } from "react-hook-form"
import { toast } from "sonner"
import * as z from "zod"
import {
  store as storeMill,
  update as updateMill,
} from "@/routes/mills";
import { router } from '@inertiajs/react';

import {
    type State,
    type County,
    type Mill,
    type MillType,
    type WoodSpecies,
} from '@/types';
import {
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
import {
  ControlledCombobox,
} from "@/components/extend/controlled-combobox";
import { ControlledInput } from "@/components/extend/controlled-input";
import { ControlledSelect } from "@/components/extend/controlled-select";
import {
    millFormSchema,
    type MillFormData,
    doesZodRequire,
} from '@/lib/zod-schemas';


/**
 * 
 * Need to add props so we can pass states, counties, mill types, and wood species into this mess.
 * On top of that, we also need to be able to pass values for each field.
 */
export interface MillFormProps {
    headline?: string;
    description?: string;
    states: State[];
    counties?: County[];
    millTypes: MillType[];
    woodSpecies: WoodSpecies[];
    // don't know if we even need form data
    // how are validation errors pushed back to the view?
    // Inertia sends errors in page.errors
    formData?: object;
    mill?: Mill;
    initialData?: MillFormData;
}

export function MillForm({
  headline = '',
  description = 'Help us improve by submitting mills that are not in our system.',
  mill,
  initialData,
  ...props
}: MillFormProps) {
    // is we have a Mill, we're editing and thus we need to post to updateMill
    const isEditing = !!mill; // initialData;

    // don't extract states from props so we can use the name here
    const states = React.useMemo(() => normalizeStates(props.states), [props.states]);

    const form = useForm<MillFormData>({
        resolver: zodResolver(millFormSchema),
        mode: 'onBlur',
        defaultValues: {
            // I'd love to extract defaultValues into the zod-schemas file as well
            mill_name: mill?.mill_name || '',      
            physical_address: mill?.physical_address || '',
            physical_city: mill?.physical_city || '',
            state_id: String((typeof mill?.state_id === 'object' ? mill?.state_id?.id : mill?.state_id) ?? ''),
            physical_zip: mill?.physical_zip || '',
            // I need to figure out how to tack this on
            // maybe an "appends" attribute?
            mailing_address_same_as_physical: mill?.mailing_address_same_as_physical || true,
            mailing_address: mill?.mailing_address || "",
            mailing_city: mill?.mailing_city || "",
            mailing_state_id: String((typeof mill?.mailing_state_id === 'object' ? mill?.mailing_state_id?.id : mill?.mailing_state_id) ?? ''),
            mailing_zip: mill?.mailing_zip || '',
            contact_name: mill?.contact_name || '',
            contact_title: mill?.contact_title ||'',            
            telephone: mill?.telephone || '',
            telephone_2: mill?.telephone_2 || '',
            fax: mill?.fax || '',
            email: mill?.email || '',
            email_2: mill?.email_2 || '',
            web_site: mill?.web_site || '',
            size: mill?.size || '',
            year: mill?.year || '',
            mill_types: mill?.mill_types?.map((millType) => String(millType.id)) || [],
            wood_species: mill?.wood_species?.map((woodSpecies) => String(woodSpecies.id)) || [],
            submitter_email: mill?.submitter_email || '',
        },
    });

    const watchMailingSameAsPhysical = useWatch({
        control: form.control,
        name: "mailing_address_same_as_physical",
    });

    function onSubmit(data: MillFormData) {
        /**
         * toast will silently fail if the page does not also include a Toaster component somewhere.
         * To wit, I have added Toaster to the bottom of app-layout.
         */

        /**
         * Umm...we need to be able to use either storeMill or patchMill, depending on the current page.
         */
        router.post(isEditing ? updateMill(mill.match_id) : storeMill(), data, {
          /**
           * for the love of God, I finally found the type for flash! (ah ah)
           * PageFlashData defined(-ish) in inertiajs/core
           * @param flash PageFlashData
           */
            onFlash: (flash) => {
              // console.log('flash: ', flash);
              if (flash.message) {
                toast.success("Thank you for contributing.", {
                  closeButton: true,
                  position: "top-center",
                  description: (
                    <p className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
                      {String(flash.message)}
                    </p>
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

  // use useEffect to reset the form after successful submission
  React.useEffect(() => {
      if (form.formState.isSubmitSuccessful) {
          form.reset();
      }
  }, [form]);


  return (
    <Card className="w-full sm:max-w-md mx-auto">
      <CardHeader>
        <CardTitle>{headline}</CardTitle>
        <CardDescription>
          {description !== '' && (
            <p>{description}</p>
          )}          
          <p className="my-3">Required fields are marked with an asterisk (<span className="text-destructive">*</span>).</p>
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
              required={doesZodRequire(millFormSchema, 'mill_name')}
            />

            {/** Physical Address */}
            <FieldSet id="physical_address_wrap">
              <FieldLegend>Physical Address</FieldLegend>
              <ControlledInput
                control={form.control}
                name="physical_address"
                label="Street Address"
                placeholder=""
                required={doesZodRequire(millFormSchema, 'physical_address')}
              />

              <div className="grid grid-cols-3 gap-4 mt-3">

                <ControlledInput
                  control={form.control}
                  name="physical_city"
                  label="City"
                  placeholder=""
                  required={doesZodRequire(millFormSchema, 'physical_city')}
                />

                <ControlledSelect
                  control={form.control}
                  name="state_id"
                  label="State"
                  items={states}
                  placeholder=""
                  required={doesZodRequire(millFormSchema, 'state_id')}
                />

                <ControlledInput
                    control={form.control}
                    name="physical_zip"
                    label="ZIP"
                    placeholder=""
                    required={doesZodRequire(millFormSchema, 'physical_zip')}
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
              name="contact_name"
              label="Contact Name"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'contact_name')}
            />
            <ControlledInput 
              control={form.control}
              name="contact_title"
              label="Contact Title"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'contact_title')}
            />


            <ControlledInput 
              control={form.control}
              name="telephone"
              label="Telephone"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'telephone')}
            />

            <ControlledInput 
              control={form.control}
              name="telephone_2"
              label="Telephone 2"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'telephone_2')}
            />

            <ControlledInput
              control={form.control}
              name="fax"
              label="Fax"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'fax')}
            />

            <ControlledInput
              control={form.control}
              name="email"
              label="Email"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'email')}
            />

            <ControlledInput
              control={form.control}
              name="email_2"
              label="Email 2"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'email_2')}
            />

            <ControlledInput
              control={form.control}
              name="web_site"
              label="Mill Website"
              placeholder="Please include the full URL, including http:// or https://"
              required={doesZodRequire(millFormSchema, 'web_site')}
            />

            <ControlledInput
              control={form.control}
              name="size"
              label="Mill Size"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'size')}
            />

            <ControlledInput
              control={form.control}
              name="year"
              label="Year Established"
              placeholder=""
              required={doesZodRequire(millFormSchema, 'year')}
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
              required={doesZodRequire(millFormSchema, 'submitter_email')}
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
