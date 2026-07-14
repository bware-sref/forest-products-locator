<?php

namespace App\Http\Resources;

use App\Http\Resources\CountyResource;
use App\Http\Resources\MillTypeResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\WoodSpeciesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * try to grab state info before building our array
         */        
        $state = $this->whenLoaded('state');
        $county = $this->whenLoaded('county');
        // county comes in as a model object
        // dd($county);
        $millType = $this->whenLoaded('millTypes');
        $woodSpecies = $this->whenLoaded('woodSpecies');

        // dd($millType);

        return [
            // mill model properties
            /**
             * Excel export currently wants a list of numeric mill ids so I'm reverting id to be the db id
             */
            // 'id' => $this->match_id,
            'id' => $this->id,
            'match_id' => $this->match_id,
            'mill_name' => $this->mill_name,
            'physical_address' => $this->whenNotNull($this->physical_address),
            'physical_city' => $this->whenNotNull($this->physical_city),
            'physical_state' => $this->whenNotNull($this->physical_state),
            'physical_zip' => $this->whenNotNull($this->physical_zip),
            'physical_address_two' => $this->whenNotNull($this->physical_address_two),
            'mailing_address' => $this->whenNotNull($this->mailing_address),
            'mailing_city' => $this->whenNotNull($this->mailing_city),
            'mailing_state' => $this->whenNotNull($this->mailing_state),
            'mailing_zip' => $this->whenNotNull($this->mailing_zip),
            'telephone' => $this->whenNotNull($this->telephone),
            'fax' => $this->whenNotNull($this->fax),
            'email' => $this->whenNotNull($this->email),
            'web_site' => $this->whenNotNull($this->web_site),
            'latitude' => $this->whenNotNull((float) $this->latitude, 0),
            'longitude' => $this->whenNotNull((float) $this->longitude, 0),
            /**
             * We may want to flatten these into strings
             * Except State probably needs to have name and abbreviation
             * in that case we could simply use two separate properties
             */
            // $this->mergeWhen($this->whenLoaded('millTypes'), []),
            'mill_types' => MillTypeResource::collection($this->whenLoaded('millTypes')),
            'wood_species' => WoodSpeciesResource::collection($this->whenLoaded('woodSpecies')),
            // 'state' => new StateResource($this->whenLoaded('state')),
            $this->mergeWhen($this->whenLoaded('state'), [
                // prevent exception when state not populated
                'state' => $state->abbreviation ?? '',
                'state_name' => $state->name ?? '',
            ]),
            // I forgot why I wanted to make this more complex...
            $this->mergeWhen($this->whenLoaded('county'), [
                // 'county' => !empty($county) && property_exists($county, 'name') ? $county->name : '',
                // because $county is a Model, it doesn't have an actual property named "name",
                // therefore property_exists() returns false.
                'county' => $county->name ?? '',
            ]),
            // 'county' => new CountyResource($this->whenLoaded('county')),
        ];
    }
}
