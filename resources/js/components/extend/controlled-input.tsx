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
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { HTMLInputAutoCompleteAttribute } from "react";


export function ControlledInput<FormValues extends FieldValues>({
    control,
    name,
    label,
    placeholder,
    autoComplete = "off",
    ...props
}: {
    control: Control<FormValues>;
    name: Path<FormValues>;
    label: string;
    placeholder?: string;
    autoComplete?: HTMLInputAutoCompleteAttribute;    
    [key: string]: unknown;
}) {
    const fieldClassName = props.fieldClassName ?? "";
    const labelClassName = props.labelClassName ?? "";
    const inputClassName = props.inputClassName ?? "";

    return (
        <Controller            
            name={name}
            control={control}
            render={({ field, fieldState }) => (
                <Field 
                    data-invalid={fieldState.invalid}
                    className={cn(fieldClassName)}
                >
                    <FieldLabel
                        htmlFor={field.name}
                        className={cn(labelClassName)}
                    >
                        {label}
                    </FieldLabel>
                    <Input
                        {...field}
                        id={field.name}
                        aria-invalid={fieldState.invalid}
                        placeholder={placeholder}
                        autoComplete={autoComplete as HTMLInputAutoCompleteAttribute}
                        className={cn(inputClassName)}
                    />
                    {fieldState.invalid && (
                        <FieldError errors={[fieldState.error]} />
                    )}
                </Field>
            )}
        />
    );
}