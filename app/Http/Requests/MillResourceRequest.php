<?php

namespace App\Http\Requests;

use App\Exceptions\MillResourceRequestValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class MillResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return false;
        /**
         * The endpoint is public...
         * We might need to restrict access to the application itself.
         * If so, later.
         */
        return true;
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
             * s - text search on mill name?
             * millType - 0 or more valid MillType.name values
             * woodSpecies - 0 or more WoodSpecies.name values
             * state - should we allow full names or just abbreviations?
             * county - most useful when combined with state
             */
            'q' => [
                'sometimes',
                'nullable',
                'string',
            ],
            /**
             * Okay.
             * At this time, millType (singular) doesn't need to be an array because we currently don't allow multiselect.
             */
            'millType' => [
                'sometimes',
                'nullable',
                // 'string',
                // 'max:24'
                'integer',
                Rule::exists('mill_types', 'id'),
            ],
            /**
             * Okay.
             * At this time, woodSpecies does not need to be an array because there are only 2 so allowing multiselect is a
             * long way to handle not doing anything.
             * Also, need to update woodSpecies to be an integer
             */
            'woodSpecies' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('wood_species', 'id'),
                // 'string',
                // 'max:12',
            ],
            'state' => [
                'sometimes',
                'nullable',
                // update to use id instead of abbreviation
                // Rule::exists('states','abbreviation'),
                Rule::exists('states','id'),
            ],
            'county' => [
                'sometimes',
                'nullable',
                // update to use id instead of name?
                // 'exists:counties,name',
                Rule::exists('counties', 'id'),
            ],
            /**
             * coordinates for user position
             * y is latitude
             * x is longitude
             */
            'lat' => [
                'exclude_if:lng,null',
                'sometimes',
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'lng' => [
                'exclude_if:lat,null',
                'sometimes',
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'radius' => [
                Rule::excludeIf(fn () => is_null($this->lng) || is_null($this->lat)),
                'sometimes',
                'nullable',
                'numeric',
            ]
        ];
    }

    /**
     * Supposedly we can override failedValidation() and/or response() methods to do something different.
     */
    // protected function failedValidation(Validator $validator)
    // {
    //     Log::alert('MillResourceRequest failed validation because...', $validator->errors()->all());
    //     Log::alert('these ones failed:', $validator->failed());
    //     /**
    //      * what happens if we don't throw an exception?
    //      * looks like someone else does...
    //      */
    //     throw new MillResourceRequestValidationException($validator, $this->response($validator));
    //     // return parent::failedValidation($validator);
    // }


    /**
     * This is sort of worthless.
     */
    // public function response(Validator $validator)
    // {
    //     if (!empty($validator->errors())) {
    //         dd($validator->failed());
    //         return new JsonResponse($validator->errors(), 200);
    //     }

    //     return parent::response($validator);
    // }


}
