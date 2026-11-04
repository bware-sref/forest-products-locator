@props(['kpis'])
{{-- A row of stat tiles from [label, value, detail?, link?] arrays. --}}
<div class="row row-cards mb-3">
    @foreach ($kpis as $kpi)
        <div class="col-6 col-md-4 col-xl-2">
            <x-stats.tile :label="$kpi['label']" :value="$kpi['value']" :detail="$kpi['detail'] ?? null" :link="$kpi['link'] ?? null" />
        </div>
    @endforeach
</div>
