<?php

namespace App\Http\Requests;

use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use function Symfony\Component\Clock\now;

class ImportMillRequest extends FormRequest
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
             *  - if we reject duplicate names, we prevent multiple submissions for the same mill, which is good, but we also prevent submissions for mills that share a name with an existing mill, which is bad.
             */
            // 'mill_name'=> 'required|string|unique:mills,mill_name|max:255',
            'mill_name'=> 'required|string|min:2|max:255',
            /**
             * match_id can be created with slugify
             * still needs to be unique
             */
            'match_id' => 'required|string|max:255|unique:mills,match_id',

            'mill_id' => 'string|nullable|max:255',

            'physical_address' => 'string|nullable|max:255',
            'physical_city' => 'string|nullable|max:255',

            // should this be the database column or the request parameter?
            // the latter, I think
            // 'county_name' => 'string|nullable|max:255',
            'county' => 'string|nullable|max:255',

            // we need physical_state instead of state_id
            'physical_state' => 'required|string|max:50',

            // not allowing null effectively makes state_id required
            // so let's make it required
            // 'state_id' => 'required|numeric|exists:states,id',

            // regex for zip? 12345-6789
            'physical_zip' => 'string|nullable|max:10|regex:/^\d{5}(-\d{4})?$/',            

            /**
             * latitude and longitude can be filled via reverse geolookup
             */
            'latitude' => 'string|nullable',

            'longitude' => 'string|nullable',

            // if present and true, use the same values for mailing address as physical address
            // 'mailing_address_same_as_physical' => 'boolean|nullable',

            /**
             * county_id can be filled in based on state and reverse geolookup
             * we could also allow it here...
             */
            'mailing_address' => 'string|nullable|max:255',
            'mailing_city' => 'string|nullable|max:255',

            /**
             * mailing_state instead of mailing_state_id
             */
            'mailing_state' => 'string|nullable|max:50',
            /**
             * need to make:
             * X a migration to add mailing_state_id
             * - command to backfill mailing_state_ids
             */
            // 'mailing_state_id' => 'numeric|nullable|exists:states,id',

            // regex for zip?
            'mailing_zip' => 'string|nullable|max:10|regex:/^\d{5}(-\d{4})?$/',

            // +1 (123) 456-7890 <- 17 characters            
            'telephone' => 'string|nullable|max:17', //|regex:/^\+?1?(\s*[\-\.]\s*|\s+)?\(?[2-9][0-9]{2}\)?(\s*[\-\.]\s*|\s+)?[0-9]{3}(\s*[\-\.]\s*|\s+)?[0-9]{4}$/',

            'fax' => 'string|nullable|max:17', // |regex:/^\+?1?(\s*[\-\.]\s*|\s+)?\(?[2-9][0-9]{2}\)?(\s*[\-\.]\s*|\s+)?[0-9]{3}(\s*[\-\.]\s*|\s+)?[0-9]{4}$/',

            // 'email' => 'email:rfc|nullable',
            'email' => 'string|nullable',

            /**
             * only allow URLs with http or https protocol
             * should we allow URLs without a protocol?
             * probably, then we'll have to do our validation separately and maybe mutate the data
             * when URLs don't have a protocol, inserting the row fails.
             */
            // 'web_site' => 'nullable|url:http,https|max:255',
            'web_site' => 'nullable|string|max:255',

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
            'year' => 'numeric|nullable|min:1800|max:'.date('Y'),

            /**
             * @tada: add millTypes array
             * @tada: add woodSpecies array
             */
            // 'mill_types' => 'array|nullable',
            // 'mill_types.*' => 'numeric|exists:mill_types,id',
            // 'wood_species' => 'array|nullable',
            // 'wood_species.*' => 'numeric|exists:wood_species,id',

            'type' => 'string|nullable',

            'species' => 'string|nullable',

            'modification_date' => 'string|nullable',

            /**
             * @tada: add submitter_email
             */
            // 'submitter_email' => [
            //     Rule::email()
            //         ->rfcCompliant(strict: true) // check for strict RFC compliance
            //         ->preventSpoofing() // prevent sneaky, lookalike Unicode characters
            // ],

        ];
    }

    // protected function prepareForValidation(): void
    // {
    //     Log::debug('Running prepareForValidation() with data: ', $this->all());
    //     $this->merge([
    //         // 'mailing_address_same_as_physical' => $this->boolean('mailing_address_same_as_physical'),
    //         'submitter_ip' => $this->ip(),
    //         'status' => PublicationStatus::Pending->value,
    //         // if and when approved, we can consider changing the match_id to a cleaner slug, but for now, let's just ensure uniqueness by appending a suffix based on the number of existing mills with the same base slug.
    //         'match_id' => Str::slug($this->mill_name) . '-' . Carbon::now(),
    //     ]);

    //     // if mailing and physical addresses are the same, copy physical address fields to mailing address fields
    //     if ($this->input('mailing_address_same_as_physical') && 
    //         (!empty($this->input('physical_address')) || 
    //             !empty($this->input('physical_city')) || 
    //             // state_id is required, so we can assume it's not empty if the validation passed, but we'll check it anyway
    //             !empty($this->input('state_id')) || 
    //             !empty($this->input('physical_zip'))
    //         )) {
    //         Log::debug('mailing_address_same_as_physical is true, copying physical address fields to mailing address fields');
    //         $this->merge([
    //             'mailing_address' => $this->input('physical_address'),
    //             'mailing_city' => $this->input('physical_city'),
    //             'mailing_state_id' => $this->input('state_id'),
    //             'mailing_zip' => $this->input('physical_zip'),
    //         ]);
    //     }

    //     // also, we need to add approve and reject hashes to the data so that we can include them in the email to the admin for approving or rejecting the mill submission without having to log in to the admin panel and find the mill record.

    //     Log::debug('Data after merging additional fields in prepareForValidation(): ', $this->all());
    // }


    /**
     * after validation, we need to "flesh out" the data by:
     *  - adding submitter's IP address
     *  - set status to "pending"
     *  - generate a unique slug for match_id
     *  - what is our strategy for handling duplicate match_id slugs?
     *      query for the slug with possible suffixes, e.g., 'slug%'
     *      sort the results by match_id in descending order
     *     if the base slug exists, add a suffix of -2, -3, etc. based on the number of existing slugs
     *  - also, we need to check to see if mailing and physical addresses are the same and if so, copy the physical address fields to the mailing address fields
     *  - do we want to normalize phone numbers here too?
     *  - preg replace all non-digit characters, ltrim leading 1 if present, and then reformat as 123.456.7890
     * passedValidation() doesn't seem to do anything, so I'm doing this in prepareForValidation() instead, which runs before validation and allows us to modify the data before it is validated.
     */
    // protected function passedValidation(): void
    // {
        // Log::debug('Running passedValidation() to flesh out data', $this->all());
        // $this->replace([
        //     'submitter_ip' => $this->ip(),
        //     'status' => PublicationStatus::Pending->value,
        //     'match_id' => Str::slug($this->mill_name),
        // ]);

        // if mailing and physical addresses are the same, copy physical address fields to mailing address fields
        // if ($this->input('mailing_address_same_as_physical')) {
        //     $this->replace([
        //         'mailing_address' => $this->input('physical_address'),
        //         'mailing_city' => $this->input('physical_city'),
        //         'mailing_state_id' => $this->input('state_id'),
        //         'mailing_zip' => $this->input('physical_zip'),
        //     ]);
        // }
    // }
}
