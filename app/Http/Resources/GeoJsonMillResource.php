<?php

namespace App\Http\Resources;

use App\Http\Resources\CountyResource;
use App\Http\Resources\MillTypeResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\WoodSpeciesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeoJsonMillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    /**
                     * NOTE: positions are ordered as x, y, z.
                     * So instead of "normal" [latitude, longitude] (a.k.a., [y, x]), 
                     * TL;DR, we need to reverse them to put x (longitude) first
                     * 
                     */
                    $this->whenNotNull((float) $this->longitude, 0),
                    $this->whenNotNull((float) $this->latitude, 0),
                ],
            ],
            /**
             * Per spec, each Feature can (and should) have an id property.
             * Weird that no examples I've seen actually use it.
             */
            'id' => $this->match_id,
            'properties' => [
                // mill model properties
                'match_id' => $this->match_id,
                'name' => $this->mill_name,
                'physical_address' => $this->whenNotNull($this->physical_address),
                'physical_city' => $this->whenNotNull($this->physical_city),
                'physical_state' => $this->whenNotNull($this->physical_state),
                'physical_zip' => $this->whenNotNull($this->physical_zip),
                'mailing_address' => $this->whenNotNull($this->mailing_address),
                'mailing_city' => $this->whenNotNull($this->mailing_city),
                'mailing_state' => $this->whenNotNull($this->mailing_state),
                'mailing_zip' => $this->whenNotNull($this->mailing_zip),
                'telephone' => $this->whenNotNull($this->telephone),
                'fax' => $this->whenNotNull($this->fax),
                'email' => $this->whenNotNull($this->email),
                'web_site' => $this->whenNotNull($this->web_site),
                /**
                 * add lat & long for debugging purposes
                 */
                'latitude' => $this->whenNotNull((float) $this->latitude, 0),
                'longitude' => $this->whenNotNull((float) $this->longitude, 0),
                'mill_types' => MillTypeResource::collection($this->whenLoaded('millTypes')),
                'wood_species' => WoodSpeciesResource::collection($this->whenLoaded('woodSpecies')),
                'state' => new StateResource($this->whenLoaded('state')),
                'county' => new CountyResource($this->whenLoaded('county')),
            ],
        ];
    }
}
