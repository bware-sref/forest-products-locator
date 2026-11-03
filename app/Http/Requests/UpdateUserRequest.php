<?php

namespace App\Http\Requests;

use Backpack\PermissionManager\app\Http\Requests\UserUpdateCrudRequest;
use App\Http\Requests\Concerns\ValidatesUserRoles;

class UpdateUserRequest extends UserUpdateCrudRequest
{
    use ValidatesUserRoles;

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

        $rules['roles'] = [...$this->rolesRules(), $this->keepsLastSuperadminRule()];
        $rules['state_id'] = $this->stateIdRules();
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
            ...$this->roleMessages(),
            //
        ];
    }
}
