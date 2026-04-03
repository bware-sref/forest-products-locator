<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // anyone is allowed to do this
        return true; // false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * mill_names are not currently unique...
             * there are 7 named "Westrock" plus 13 more whose name includes "westrock"
             * we could handle this several ways:
             *  - update existing mills to ensure unique mill_names
             *  - we could still use unique here ensure that no new mills are submitted with duplicate names
             */
            'mill_name'=> 'required|string|unique:mills,mill_name|max:255',
            /**
             * match_id can be created with slugify
             * still needs to be unique
             */
            'physical_address' => 'string|nullable|max:255',
            'physical_city' => 'string|nullable|max:255',
            // not allowing null effectively makes state_id required
            // so let's make it required
            'state_id' => 'required|numeric|exists:states,id',
            // 12345-6789
            'physical_zip' => 'string|nullable|max:10',
            /**
             * latitude and longitude can be filled via reverse geolookup
             */

            // if present and true, use the same values for mailing address as physical address
            'mailing_address_same_as_physical' => 'boolean|nullable',

            /**
             * county_id can be filled in based on state and reverse geolookup
             * we could also allow it here...
             */
            'mailing_address' => 'string|nullable|max:255',
            'mailing_city' => 'string|nullable|max:255',
            /**
             * need to make:
             * X a migration to add mailing_state_id
             * - command to backfill mailing_state_ids
             */
            'mailing_state_id' => 'numeric|nullable|exists:states,id',
            'mailing_zip' => 'string|nullable|max:10',
            // +1 (123) 456-7890 <- 17 characters
            'telephone' => 'string|nullable|max:17',
            'fax' => 'string|nullable|max:17',
            'email' => [
                'nullable',
                Rule::email()
                    ->rfcCompliant(strict: true) // check for strict RFC c
                    // preventSpoofing() requires PHP intl extension, which we may not have
                    ->preventSpoofing() // prevent sneaky, lookalike Unicode characters
                    // validateMxRecord() requires PHP intl extension
                    // we may only want to do this for capturing submitter's email
            ],
            /**
             * only allow URLs with http or https protocol
             * should we allow URLs without a protocol?
             * probably, then we'll have to do our validation separately and maybe mutate the data
             */
            'web_site' => 'nullable|url:http,https|max:255',
            /**
             * MillType and WoodSpecies need to be arrays of numeric ids.
             * I need to look up how to handle that.
             */
            'size' => 'string|nullable|max:255',
            /**
             * is year the year it was established or what?
             * doesn't matter
             * the DB contains both 4-digit years and 10-digit dates mm/dd/yyyy
             */
            'year' => 'numeric|nullable|max:4',

            /**
             * @tada: add millTypes array
             * @tada: add woodSpecies array
             */
            'mill_types' => 'array|nullable',
            'mill_types.*' => 'numeric|exists:mill_types,id',
            'wood_species' => 'array|nullable',
            'wood_species.*' => 'numeric|exists:wood_species,id',

            /**
             * @tada: add submitter_email
             */
            'submitter_email' => [
                Rule::email()
                    ->rfcCompliant(strict: true) // check for strict RFC compliance
                    ->preventSpoofing() // prevent sneaky, lookalike Unicode characters
            ],

        ];
    }
}
