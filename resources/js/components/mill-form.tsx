"use client"

import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { Controller, useForm } from "react-hook-form"
import { toast } from "sonner"
import * as z from "zod"

import {
    type State,
    type County,
    type MillType,
    type WoodSpecies,
} from '@/types';

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
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import {
  InputGroup,
  InputGroupAddon,
  InputGroupText,
  InputGroupTextarea,
} from "@/components/ui/input-group"
import {
    Select,
    SelectTrigger,
} from '@/components/ui/select';

/**
 * It would be wonderful if formSchema translated immediately to MillFormParams
 */
const formSchema = z.object({
  mill_name: z
    .string()
    .min(2, "Mill Name must be at least 2 characters.")
    .max(255, "Mill Name must be at most 255 characters."),
  physical_address: z
    .string()
    .min(5, 'Address must be at least 5 characters.')
    .max(255, 'Address must be at most 255 characters.')
    // .nullish()
    .optional()
    // .or(z.literal(''))
    // .default('')
    ,
  physical_city: z
    .string()
    .min(3, 'City must be at least 3 characters.')
    .max(255, 'City must be at most 255 characters.')
    .optional()
    // .nullish()
    ,
  state_id: z
    .int(),
  physical_zip: z
    .string()
    .min(5, 'ZIP Code must be at least 5 characters.')
    .max(10, 'ZIP Code must be at most 10 characters.')
    .optional()
    // .nullish()
    ,
  // do we want to add counties yet?
  // I ask because county_id select would need to update when state changes
  // which is fine, but seems a tad advanced for the first pass
  /**
   * more fields!
   * we should be able to copy and paste the mailing_* fields once we build the physical versions
   * mailing_address
   * mailing_city
   * mailing_state_id
   * mailing_zip
   * mailing_county_id
   */
  // telephone
  // fax
  // email
  // web_site
  // size
  // year
  telephone: z
    .string()
    .min(10, 'Telephone must be at least 10 characters.')
    .max(17, 'Telephone must be at most 17 characters.')
    .optional(),
  fax: z
    .string()
    .min(10, 'Fax must be at least 10 characters.')
    .max(17, 'Fax must be at most 17 characters.')
    .optional(),
  email: z
    .email()
    .optional(),
  web_site: z.url({
        protocol: /^https?$/,
        hostname: z.regexes.domain
    })
    .optional(),
  size: z
    .string()
    .max(255, 'Size must be at most 255 characters.')
    .optional(),
  description: z
    .string()
    .min(5, "Description must be at least 20 characters.")
    .max(100, "Description must be at most 100 characters.")
    .optional(),
})

/**
 * 
 * Need to add props so we can pass states, counties, mill types, and wood species into this mess.
 * On top of that, we also need to be able to pass values for each field.
 */
export interface MillFormProps {
    states: State[];
    counties?: County[];
    millTypes: MillType[];
    woodSpecies: WoodSpecies[];
    formData?: object;
}

export function MillForm({...props}: MillFormProps) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      mill_name: "",      
      physical_address: "",
      physical_city: "",
      state_id: 0,
      physical_zip: '',
      telephone: '',
      fax: '',
      email: '',
      web_site: '',
      size: '',
      description: "",
    },
  })

  function onSubmit(data: z.infer<typeof formSchema>) {
    toast("You submitted the following values:", {
      description: (
        <pre className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
          <code>{JSON.stringify(data, null, 2)}</code>
        </pre>
      ),
      position: "bottom-right",
      classNames: {
        content: "flex flex-col gap-2",
      },
      style: {
        "--border-radius": "calc(var(--radius)  + 4px)",
      } as React.CSSProperties,
    })
  }

  return (
    <Card className="w-full sm:max-w-md mx-auto">
      <CardHeader>
        <CardTitle>Add Your Business</CardTitle>
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
                  <FieldLabel htmlFor="mill_name">
                    Mill Name
                  </FieldLabel>
                  <Input
                    {...field}
                    id="mill_name"
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
                <div>placeholder for state select</div>
                        
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
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Field orientation="horizontal">
          <Button type="button" variant="outline" onClick={() => form.reset()}>
            Reset
          </Button>
          <Button type="submit" form="form-rhf-demo">
            Submit
          </Button>
        </Field>
      </CardFooter>
    </Card>
  )
}
