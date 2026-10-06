@php
    $value = function (string $field, $default = null) use ($record, $prefill) {
        return old($field, $record?->{$field} ?? $prefill[$field] ?? $default);
    };
    $intervalMonths = $renewing?->type->suggested_interval_months;
@endphp

<form method="post" action="{{ $record ? route('records.update', $record) : route('records.store') }}">
    @csrf
    @if ($record)
        @method('PUT')
    @endif
    @if ($renewing)
        <input type="hidden" name="supersedes_id" value="{{ $renewing->id }}">
    @endif

    <div class="grid2">
        <div>
            <label class="lbl" for="type_name">Type</label>
            <input class="inp" type="text" id="type_name" name="type_name" list="type-suggestions" required
                   value="{{ old('type_name', $record?->type->name ?? $prefill['type_name'] ?? '') }}">
            <datalist id="type-suggestions">
                @foreach ($types as $type)
                    <option value="{{ $type->name }}"></option>
                @endforeach
            </datalist>
            <p class="hint">Pick one or type a new name — new types are created automatically.</p>
        </div>
        <div>
            <label class="lbl" for="entity_name">Covers</label>
            <input class="inp" type="text" id="entity_name" name="entity_name" list="entity-suggestions" required
                   value="{{ old('entity_name', $record?->entity->name ?? $prefill['entity_name'] ?? '') }}"
                   placeholder="e.g. Unit 14 boiler, Reception block, whole site">
            <datalist id="entity-suggestions">
                @foreach ($entities as $entity)
                    <option value="{{ $entity->name }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>

    <div class="grid2">
        <div>
            <label class="lbl" for="issue_date">Issue date</label>
            <input class="inp" type="date" id="issue_date" name="issue_date" required
                   value="{{ $value('issue_date') ? \Illuminate\Support\Carbon::parse($value('issue_date'))->toDateString() : '' }}"
                   @if ($intervalMonths) data-interval-months="{{ $intervalMonths }}" @endif>
        </div>
        <div>
            <label class="lbl" for="expiry_date">Expiry date</label>
            <input class="inp" type="date" id="expiry_date" name="expiry_date"
                   value="{{ $value('expiry_date') ? \Illuminate\Support\Carbon::parse($value('expiry_date'))->toDateString() : '' }}">
            <p class="hint">
                Leave empty if this record doesn't expire.
                @if ($renewing && $intervalMonths)
                    Pre-filled from this type's suggested renewal interval ({{ $intervalMonths }} {{ Str::plural('month', $intervalMonths) }}) — change it to whatever the new certificate says.
                @endif
            </p>
        </div>
    </div>

    <div class="grid2">
        <div>
            <label class="lbl" for="issuer">Issuer</label>
            <input class="inp" type="text" id="issuer" name="issuer" list="issuer-suggestions"
                   value="{{ $value('issuer') }}" placeholder="Who issued or carried it out">
            <datalist id="issuer-suggestions">
                @foreach ($issuers as $issuer)
                    <option value="{{ $issuer }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="lbl" for="reference_number">Reference number</label>
            <input class="inp" type="text" id="reference_number" name="reference_number" value="{{ $value('reference_number') }}">
        </div>
    </div>

    <label class="lbl" for="responsible_person">Responsible person</label>
    <input class="inp" type="text" id="responsible_person" name="responsible_person" value="{{ $value('responsible_person') }}"
           placeholder="Who keeps an eye on this">

    <label class="lbl" for="notes">Notes</label>
    <textarea class="inp" id="notes" name="notes" rows="3">{{ $value('notes') }}</textarea>

    <button class="btn" type="submit" style="margin-top:14px">{{ $record ? 'Save changes' : ($renewing ? 'Save renewal' : 'Add record') }}</button>
    <a class="btn ghost" href="{{ $record ? route('records.show', $record) : ($renewing ? route('records.show', $renewing) : route('records.index')) }}" style="margin-left:6px">Cancel</a>
</form>

@if ($intervalMonths)
<script>
// When renewing: keep the pre-filled expiry in step with the issue date,
// using the type's suggested renewal interval. Purely a convenience —
// the field stays editable and nothing enforces it.
(function () {
    var issue = document.getElementById('issue_date');
    var expiry = document.getElementById('expiry_date');
    var months = parseInt(issue.dataset.intervalMonths, 10);
    issue.addEventListener('change', function () {
        if (!issue.value || !months) return;
        var d = new Date(issue.value + 'T00:00:00');
        d.setMonth(d.getMonth() + months);
        expiry.value = d.toISOString().slice(0, 10);
    });
})();
</script>
@endif
