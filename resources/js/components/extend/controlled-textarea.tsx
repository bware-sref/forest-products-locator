import { cn } from "@/lib/utils";
import {
    Control,
    Controller,
    FieldValues,
    Path,
} from "react-hook-form";

import {
  Field,
  FieldError,
  FieldLabel,
} from "@/components/ui/field";
import { Textarea } from "@/components/ui/textarea";
import { HTMLInputAutoCompleteAttribute } from "react";

export function ControlledTextarea<FormValues extends FieldValues>({
    control,
    name,
    label,
    placeholder,
    // autoComplete = "off",
    fieldClassName = '',
    inputClassName = '',
    labelClassName = '',
    ...props
}: {
    control: Control<FormValues>;
    name: Path<FormValues>;
    label: string;
    placeholder?: string;
    required?: boolean;
    fieldClassName?: string;
    inputClassName?: string;
    labelClassName?: string;
}) {

    // if we make this required, we want to add the required attribute to the input element, but we also want to add a visual indicator to the label and field. We can do this by checking if the required prop is true or if any of the classNames include "required". This way, we can support both explicit and implicit ways of marking a field as required.
    const required = props.required ||fieldClassName.includes("required");

    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => (
                <Field
                    data-invalid={fieldState.invalid}
                    className={cn(fieldClassName + (required ? " required" : ""))}
                >
                    <FieldLabel
                        htmlFor={field.name}
                        className={cn(labelClassName)}
                    >{label}</FieldLabel>
                    <Textarea 
                        {...field}
                        id={field.name}
                        aria-invalid={fieldState.invalid}
                        placeholder={placeholder}
                        className={cn(inputClassName)}
                    />
                    {fieldState.invalid && (
                        <FieldError errors={[fieldState.error]} />
                    )}
                </Field>
            )}
        ></Controller>
    );
}