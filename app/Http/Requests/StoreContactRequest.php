<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'name' => [
                'string',
                'max:255',
                'nullable',
            ],
            'email' => [            
                'required',
                Rule::email()
                    // validate against RFCs
                    // strict causes failure on warnings
                    // warnings are caused by questionable formatting
                    // e.g., trailing periods, multiple consecutive periods
                    ->rfcCompliant($strict = true)
                    // verify the email domain has an MX record                    
                    ->validateMxRecord()
                    // prevent deceptive homographs (look-alike characters)
                    ->preventSpoofing()
                    // validate with filter_var(), allowing some Unicode characters
                    ->withNativeValidation($allowUnicode = true)
            ],
            'subject' => [
                'string',
                'required',
                'max:255',
            ],
            'message' => [
                'string',
                'required',
                'max:1024',
            ],
            'ip_address' => [
                'required',
                'ip',
            ],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        // attach submitter's IP address
        $this->merge([
            'ip_address' => $this->ip(),
        ]);
    }
}
