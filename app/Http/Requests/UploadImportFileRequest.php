<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use RedSquirrelStudio\LaravelBackpackImportOperation\Requests\ImportFileRequest;

class UploadImportFileRequest extends ImportFileRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /**
         * We could get the parent rules and remove stuff... or we could just copy what they do and change it.
         * 
         */
        // $rules = parent::rules();
        return [
            'file' => [
                'required',
                'file',
                'mimetypes:'. implode(',', [
                    // XLS, which we don't want.
                    // 'application/vnd.ms-excel',
                    // 'application/msexcel',
                    // 'application/msexcel',
                    // 'application/x-xls',
                    // XLSX
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    // CSV
                    'text/csv',
                    'text/plain',
                    'application/csv',
                ]),
            ],
            /**
             * Add our additional fields
             */
            'state_id'=> [
                'integer',
                'nullable',
            ],
            'delete_from_state' => [
                'boolean',
                'nullable',
            ],
        ];
    }
}
