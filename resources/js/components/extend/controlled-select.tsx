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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';


interface SelectItem {
    value: string;
    label: string;
    [key: string]: unknown;
}

export interface ControlledSelectProps<FormValues extends FieldValues, Item extends SelectItem> {
  control: Control<FormValues>;
  name: Path<FormValues>;
  label: string;
  items: Item[];
  multiple?: boolean;
  placeholder?: string;
  orientation?: "vertical" | "horizontal" | "responsive" | null;
  [key: string]: unknown;
}

export function ControlledSelect<FormValues extends FieldValues, Item extends SelectItem>({
    control,
    name,
    label,
    items,
    multiple = false,
    placeholder,
    orientation = "responsive",
    ...props
}: ControlledSelectProps<FormValues, Item>) {
    
    const fieldClassName = props.fieldClassName ?? "";
    const labelClassName = props.labelClassName ?? "";
    const selectTriggerClassName = props.selectTriggerClassName ?? "min-w-25";

    return (
        <Controller
            name={name}
            control={control}
            render={({field, fieldState}) => (
                <Field
                    orientation={orientation}
                    data-invalid={fieldState.invalid}
                    className={cn(fieldClassName)}
                >
                    <FieldLabel
                        htmlFor={field.name}
                        className={cn(labelClassName)}>
                        {label}
                    </FieldLabel>
                    <Select
                        name={field.name}
                        value={field.value}                                
                        onValueChange={field.onChange}
                    >
                        <SelectTrigger
                            id={field.name}
                            aria-invalid={fieldState.invalid}
                            className={cn(selectTriggerClassName)}
                        >
                            <SelectValue placeholder={placeholder} />
                        </SelectTrigger>
                        <SelectContent>
                            {items.map((item) => (
                                <SelectItem key={item.value} value={String(item.value)}>
                                    {item.label}
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
    );
}