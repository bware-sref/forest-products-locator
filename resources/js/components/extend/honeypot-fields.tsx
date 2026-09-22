import {
    Control,
} from "react-hook-form";
import {
    ControlledInput
} from "@/components/extend/controlled-input";
import {
    type IHoneypot
} from "@/types";

export interface HoneypotFieldsProps {
    honeypot : IHoneypot;
    control : Control;
}

export function HoneypotFields({
    honeypot,
    control
} : HoneypotFieldsProps) {
    return (
        <>
        {honeypot.enabled && (
            <div className="hidden" aria-hidden="true">
                <ControlledInput 
                    control={control}
                    name={honeypot.nameFieldName}
                    label=""
                    placeholder=""
                    required={false}
                    autocomplete="off"
                />
                <ControlledInput 
                    control={control}
                    name={honeypot.validFromFieldName}
                    label=""
                    placeholder=""
                    required={false}
                    autocomplete="off"
                />
            </div>
        )}
        </>
    );
}