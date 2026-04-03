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
    ...props
}: {
    control: Control<FormValues>;
    name: Path<FormValues>;
    label: string;
    placeholder?: string;
    [key: string]: unknown;
}) {
    const autoComplete = props.autoComplete || "off";
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                    <FieldLabel htmlFor={field.name}>
                    {label}
                    </FieldLabel>
                    <Input
                        {...field}
                        id={field.name}
                        aria-invalid={fieldState.invalid}
                        placeholder={placeholder}
                        autoComplete={autoComplete as HTMLInputAutoCompleteAttribute}
                    />
                    {fieldState.invalid && (
                        <FieldError errors={[fieldState.error]} />
                    )}
                </Field>
            )}
        />
    );
}