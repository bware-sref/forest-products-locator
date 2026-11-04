@props(['states', 'selected' => null, 'action', 'allLabel' => null, 'param' => 'state'])
{{--
  State picker. With allLabel, the first option means "all states" and the choice is sent as ?state=;
  without it, the state is required (By State reads ?state= when the URL has no state).
  A plain GET form with a button. It doesn't submit on change: that would move keyboard users off the page
  while they arrow through the options (WCAG 3.2.2).
--}}
@php $id = 'stats-state-'.\Illuminate\Support\Str::random(6); @endphp
<form method="GET" action="{{ $action }}" class="d-flex align-items-center gap-2 d-print-none">
    <label for="{{ $id }}" class="form-label mb-0 text-nowrap">State</label>
    <select id="{{ $id }}" name="{{ $param }}" class="form-select form-select-sm" style="min-width: 12rem">
        @if ($allLabel)
            <option value="">{{ $allLabel }}</option>
        @endif
        @foreach ($states as $state)
            <option value="{{ $state->abbreviation }}" @selected($selected?->id === $state->id)>{{ $state->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-sm btn-secondary">Show</button>
</form>
