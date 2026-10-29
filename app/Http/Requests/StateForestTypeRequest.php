<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;
use Illuminate\Foundation\Http\FormRequest;

class StateForestTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'state_id' => 'required|exists:states,id',
            'title' => 'required|max:255',
            /**
             * mimes:svg alone rejects many real SVGs: PHP sniffs the type from the file's contents, and SVGs that
             * open with a comment (Illustrator/Figma exports) sniff as text/plain. So check the extension, plus a
             * content type that's either a proper SVG/PNG or one of the text types SVGs get misread as.
             * ValidUpload also skips file rules on edit when the existing icon wasn't replaced.
             */
            'icon' => ValidUpload::field('nullable')->file([
                'extensions:svg,png',
                'mimetypes:image/svg+xml,image/svg,image/png,text/plain,text/xml,application/xml,text/html',
            ]),
        ];
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
