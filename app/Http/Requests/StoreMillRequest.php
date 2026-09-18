<?php

namespace App\Http\Requests;

use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use function Symfony\Component\Clock\now;

class StoreMillRequest extends AbstractMillRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // anyone is allowed to do this
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return parent::baseRules();
    }

    protected function prepareForValidation(): void
    {
        $this->mergeSubmitterIp();
        $this->mergeStatus();
        $this->mergeMatchId();
        /**
         * When we start inserting into mill_edits instead of mills, this issue goes away.
         */
        $this->handleMailingAddress();
    }

    protected function mergeMatchId(): void
    {
        $this->merge([
            /**
             * If we insert into mill_edits instead of mills, this issue goes away.
             * Oh yeah.
             */
            // if and when approved, we can consider changing the match_id to a cleaner slug, but for now, let's just ensure uniqueness by appending a suffix based on the number of existing mills with the same base slug.
            'match_id' => Str::slug($this->input('mill_name')) . '-' . Carbon::now()->timestamp,
        ]);
    }
}
