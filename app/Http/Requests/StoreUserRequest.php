<?php

namespace App\Http\Requests;

use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest;
use Illuminate\Support\Str;
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

        $rules['checked_role_names'] = [
            'string',
            'nullable',
        ];

        /**
         * Add our custom rule for state_id sometimes
         */
        $rules['state_id'] = [
            Rule::excludeUnless(! empty($this->input('checked_role_names')) && Str::contains($this->input('checked_role_names'), 'State Agent')),
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
            //
        ];
    }
}
