import { cn } from "@/lib/utils";
import {
    Control,
    Controller,
    FieldValues,
    Path,
} from "react-hook-form";

import {
  Combobox,
  ComboboxChip,
  ComboboxChips,
  ComboboxContent,
//   ComboboxEmpty,
  ComboboxItem,
//   ComboboxInput,
  ComboboxList,
  ComboboxValue,
  ComboboxChipsInput,
} from "@/components/ui/combobox";
import {
  Field,
  FieldError,
//   FieldGroup,
  FieldLabel,
} from "@/components/ui/field"

export interface HasValueAndLabel {
    value: string;
    label: string;
    [key: string]: unknown;
}

export interface ControlledComboboxProps<FormValues extends FieldValues, Item> {
    control: Control<FormValues>;
    name: Path<FormValues>;
    label: string;
    items: Item[];
    itemToValue: (item: Item) => string;
    itemToLabel?: (item: Item) => string;
    multiple?: boolean;
    placeholder?: string;
    [key: string]: unknown;
}

export function ControlledCombobox<FormValues extends FieldValues, Item>({
    control,
    name,
    label,
    items,
    itemToValue,
    itemToLabel,
    multiple = false,
    placeholder,
    ...props
}: ControlledComboboxProps<FormValues, Item>) {

    // hacky way to deal with props that avoids es-lint errors about unused variables and also allows us to pass through any additional props to the Combobox component
    const fieldClassName = props.fieldClassName ?? "";
    const labelClassName = props.labelClassName ?? "";
    

    const getItemLabel = (item: Item) => {
        console.log("Getting label for item:", item);
        if (itemToLabel) {
            console.log("Using itemToLabel for item:", item, itemToLabel(item));
            return itemToLabel(item);
        }
        
        const maybeLabel = (item as unknown as HasValueAndLabel).label;

        if (typeof maybeLabel === "string") {
            return maybeLabel;
        }

        return itemToValue(item);
    };

    const itemsKeyedByValue: Record<string, string> = {};
    items.forEach((item: Item) => {
        itemsKeyedByValue[String(itemToValue(item))] = getItemLabel(item);
    });

    return (
        <Controller
            control={control}
            name={name}
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

                    <Combobox
                        value={field.value as unknown as string | string[]}
                        onValueChange={(value) => field.onChange(value)}
                        multiple={multiple}
                        aria-label={label}
                        autoHighlight
                        {...(props as React.ComponentPropsWithoutRef<typeof Combobox>)}
                    >
                        <ComboboxChips>
                            <ComboboxValue>
                                {field.value.map((item: string) => (
                                    
                                    <ComboboxChip 
                                        key={item}
                                    >
                                        {itemsKeyedByValue[item] || item}
                                    </ComboboxChip>
                                ))}
                            </ComboboxValue>
                            <ComboboxChipsInput
                                id={field.name}
                                // placeholder={placeholder}
                            />
                        </ComboboxChips>
                        <ComboboxContent>
                            {/* <ComboboxEmpty>No results found.</ComboboxEmpty> */}
                            <ComboboxList>
                                {items.map((item) => (
                                    <ComboboxItem
                                        key={itemToValue(item)}
                                        value={itemToValue(item)}
                                    >
                                        {getItemLabel(item)}
                                    </ComboboxItem>
                                ))}
                            </ComboboxList>
                            
                        </ComboboxContent>
                    </Combobox>


                    {fieldState.error && <FieldError>{fieldState.error.message}</FieldError>}
                </Field>
            )}
        />
    );

}