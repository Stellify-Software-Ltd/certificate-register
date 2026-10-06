<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;
use App\Services\RecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function __construct(private readonly RecordService $records)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (int) $request->query('type', 0),
            'entity' => (int) $request->query('entity', 0),
            'responsible' => trim((string) $request->query('responsible', '')),
            'status' => (string) $request->query('status', ''),
            'sort' => $request->query('sort') === 'expiry_desc' ? 'expiry_desc' : 'expiry_asc',
            'superseded' => $request->boolean('superseded'),
        ];

        $query = Record::query()->with(['type', 'entity', 'supersededBy']);

        if (! $filters['superseded']) {
            $query->current();
        }

        if ($filters['type'] > 0) {
            $query->where('record_type_id', $filters['type']);
        }

        if ($filters['entity'] > 0) {
            $query->where('entity_id', $filters['entity']);
        }

        if ($filters['responsible'] !== '') {
            $query->where('responsible_person', 'like', '%'.$filters['responsible'].'%');
        }

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($term) {
                $inner->whereHas('type', fn ($t) => $t->where('name', 'like', $term))
                    ->orWhereHas('entity', fn ($e) => $e->where('name', 'like', $term))
                    ->orWhere('issuer', 'like', $term)
                    ->orWhere('reference_number', 'like', $term);
            });
        }

        // Records without an expiry date always sort last.
        $query->orderByRaw('expiry_date is null')
            ->orderBy('expiry_date', $filters['sort'] === 'expiry_desc' ? 'desc' : 'asc')
            ->orderBy('id');

        $records = $query->get();

        // Status is derived per record (per-type threshold), so this
        // filter has to run after the query.
        if ($filters['status'] !== '') {
            $records = $records->filter(
                fn (Record $record) => $record->status()->value === $filters['status']
            )->values();
        }

        return view('records.index', [
            'records' => $records,
            'filters' => $filters,
            'types' => RecordType::orderBy('name')->get(),
            'entities' => Entity::orderBy('name')->get(),
        ]);
    }

    public function show(Record $record): View
    {
        $record->load(['type', 'entity', 'documents', 'supersedes', 'supersededBy']);

        return view('records.show', [
            'record' => $record,
            'chain' => $record->renewalChain(),
        ]);
    }

    public function create(): View
    {
        return view('records.create', [
            'record' => null,
            'renewing' => null,
            'prefill' => ['issue_date' => Record::today()->toDateString()],
        ] + $this->formLists());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRecord($request);

        if (isset($validated['supersedes_id'])) {
            $old = Record::findOrFail($validated['supersedes_id']);

            if ($old->isSuperseded()) {
                return back()->withErrors([
                    'supersedes_id' => 'That record has already been renewed.',
                ])->withInput();
            }
        }

        $record = $this->records->save($validated);

        return redirect()
            ->route('records.show', $record)
            ->with('status', 'Record saved.');
    }

    public function edit(Record $record): View
    {
        $record->load(['type', 'entity']);

        return view('records.edit', ['record' => $record] + $this->formLists());
    }

    public function update(Request $request, Record $record): RedirectResponse
    {
        $validated = $this->validateRecord($request, allowSupersedes: false);

        $this->records->save($validated, $record);

        return redirect()
            ->route('records.show', $record)
            ->with('status', 'Record updated.');
    }

    public function destroy(Record $record): RedirectResponse
    {
        $record->delete();

        return redirect()->route('records.index')->with('status', 'Record deleted.');
    }

    /**
     * Renewal form: prefilled from the old record. The expiry date is
     * only ever a pre-fill from the type's suggested renewal interval —
     * it stays fully editable.
     */
    public function renew(Record $record): View|RedirectResponse
    {
        if ($record->isSuperseded()) {
            return redirect()
                ->route('records.show', $record->supersededBy)
                ->with('status', 'This record has already been renewed — you are looking at its replacement.');
        }

        $record->load(['type', 'entity']);

        $issueDate = Record::today();
        $suggestedExpiry = $record->type->suggested_interval_months
            ? $issueDate->addMonths($record->type->suggested_interval_months)->toDateString()
            : null;

        return view('records.create', [
            'record' => null,
            'renewing' => $record,
            'prefill' => [
                'type_name' => $record->type->name,
                'entity_name' => $record->entity->name,
                'issuer' => $record->issuer,
                'responsible_person' => $record->responsible_person,
                'issue_date' => $issueDate->toDateString(),
                'expiry_date' => $suggestedExpiry,
            ],
        ] + $this->formLists());
    }

    /**
     * @return array{types: \Illuminate\Database\Eloquent\Collection<int, RecordType>, entities: \Illuminate\Database\Eloquent\Collection<int, Entity>, issuers: \Illuminate\Support\Collection<int, string>}
     */
    private function formLists(): array
    {
        return [
            'types' => RecordType::orderBy('name')->get(),
            'entities' => Entity::orderBy('name')->get(),
            'issuers' => Record::query()
                ->whereNotNull('issuer')
                ->distinct()
                ->orderBy('issuer')
                ->pluck('issuer'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRecord(Request $request, bool $allowSupersedes = true): array
    {
        $rules = [
            'type_name' => 'required|string|max:190',
            'entity_name' => 'required|string|max:190',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'issuer' => 'nullable|string|max:190',
            'reference_number' => 'nullable|string|max:190',
            'notes' => 'nullable|string|max:5000',
            'responsible_person' => 'nullable|string|max:190',
        ];

        if ($allowSupersedes) {
            $rules['supersedes_id'] = 'nullable|integer|exists:records,id';
        }

        return $request->validate($rules);
    }
}
