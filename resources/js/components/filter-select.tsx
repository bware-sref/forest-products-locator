// import { cn } from "@/lib/utils";
import {
    Field,
    // FieldDescription,
    // FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    // SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

/**
 * Generic interface that should apply to all SelectOptions
 */
interface HasNameAndId {
    id: string | number;
    name: string;
    [key: string]: unknown; // allow for additional props
}

export interface FilterSelectProps<T extends HasNameAndId> {
    id: string;
    labelText: string;
    placeholder?: string;
    isDisabled?: boolean;
    defaultValue?: string | undefined;
    options?: T[];
    optionKeyKey?: string;
    optionValueKey?: string;
    optionTextKey?: string;
    callback?: (value: string) => void;
    [key: string]: unknown; // allow for additional props
}

export function FilterSelect<T extends HasNameAndId>({
    id,
    labelText,
    placeholder,
    isDisabled = false,
    defaultValue = '',
    options = [],
    optionKeyKey = 'id',
    optionValueKey = 'name',
    optionTextKey = 'name',
    callback,
    ...props
}: FilterSelectProps<T>) {
    return (
        <Field>
            <FieldLabel 
                htmlFor={id}
                className="text-white"
                >{labelText}</FieldLabel>
            <Select 
                defaultValue={defaultValue}
                onValueChange={callback}
                disabled={isDisabled}
            >
                <SelectTrigger id={id}
                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet [&_svg:not([class*='text-'])]:text-velvet! [&_svg]:opacity-100 focus-visible:ring-coupe"
                >
                    <SelectValue 
                        placeholder={placeholder}
                        className="bg-beluga! text-velvet"
                    />
                </SelectTrigger>
                <SelectContent className="bg-beluga text-velvet">
                    <SelectGroup className="bg-beluga text-velvet">
                        {/**
                         * I want to make the keys used for key, value, and the text variable to account for things like
                         * using state abbreviations as state values.
                         * However, the damn thing choked on using a key that might be an unknown type to specify option properties.
                         * E.g., option[optionKeyKey]
                         */}
                        {options && options.length > 0 && options.map(option => 
                            <SelectItem 
                                key={option.id}
                                value={option.id.toString()}
                            >{option.name}</SelectItem>
                        )}
                    </SelectGroup>
                </SelectContent>
            </Select>
        </Field>        
    );
}