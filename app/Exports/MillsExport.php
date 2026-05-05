<?php

namespace App\Exports;

use App\Models\Mill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MillsExport implements FromCollection, WithMapping, WithHeadings
{

    /**
     * Ooh la la, modern PHP allows member declarations in the constructor signature!
     */
    public function __construct(protected Collection $mills)
    {
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        /**
         * Laravel choked on calling all() here.
         * The exception claimed that we called all() on an Array.
         * However, var_dump() clearly indicated that $this->mills was a Collection, so shrug.
         */
        return $this->mills; //->all();
    }

    public function headings(): array
    {
        return [
            'id',
            'match_id',
            'mill_name',
            'latitude',
            'longitude',
            'year',
            'physical_address',
            'physical_city',
            'physical_county',
            'physical_state',
            'physical_zip',
            'mailing_address',
            'mailing_city',
            'mailing_county',
            'mailing_state',
            'mailing_zip',
            'telephone',
            'fax',
            'email',
            'web_site',
            'size',
            'mill_type',
            'wood_species',
            'updated_at',
        ];
    }

    public function map($mill): array
    {
        return [
            $mill->id,
            $mill->match_id,
            $mill->mill_name,
            $mill->latitude,
            $mill->longitude,
            $mill->year,
            $mill->physical_address,
            $mill->physical_city,
            // need null coalescence in case the relationships are not defined
            $mill->county->name ?? '',
            $mill->state->name ?? '',
            $mill->physical_zip,
            $mill->mailing_address,
            $mill->mailing_city,
            $mill->mailing_county->name ?? '',
            $mill->mailing_state->name ?? '',
            $mill->mailing_zip,
            $mill->telephone,
            $mill->fax,
            $mill->email,
            $mill->web_site,
            $mill->size,
            /**
             * may need fallback for these as well
             */
            implode(', ', $mill->millTypes->pluck('name')->toArray()),
            implode(', ', $mill->woodSpecies->pluck('name')->toArray()),
            $mill->updated_at,
        ];
    }
}
