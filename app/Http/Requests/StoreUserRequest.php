<?php

namespace App\Http\Requests;

use App\Enums\UserRoles;
use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends UserStoreCrudRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules['is_agent'] = [
            'string',
            'nullable',
        ];

        /**
         * Add our custom rule for state_id sometimes
         */
        $rules['state_id'] = [
            Rule::excludeUnless(
                'true' === $this->input('is_agent')
            ),
            // 'sometimes',
            'required',
            'numeric',
            'exists:states,id'
        ];
        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'state_id.required' => 'State is required for users having the "'.UserRoles::AGENT->value.'" role.',
        ];
    }
}
