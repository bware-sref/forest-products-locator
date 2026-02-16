<?php

namespace App\Http\Resources;

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
                    (float) $this->longitude,
                    (float) $this->latitude,
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
                'physical_address' => $this->physical_address ?? '',
                'physical_city' => $this->physical_city ?? '',
                'physical_state' => $this->physical_state ?? '',
                'physical_zip' => $this->physical_zip ?? '',
                'mailing_address' => $this->mailing_address ?? '',
                'mailing_city' => $this->mailing_city ?? '',
                'mailing_state' => $this->mailing_state ?? '',
                'mailing_zip' => $this->mailing_zip ?? '',
                'telephone' => $this->telephone ?? '',
                'fax' => $this->fax ?? '',
                'email' => $this->email ?? '',
                'web_site' => $this->website ?? '',
                /**
                 * add lat & long for debugging purposes
                 */
                'latitude' => (float) $this->latitude ?? 0,
                'longitude' => (float) $this->longitude ?? 0,
            ],
        ];
    }
}
