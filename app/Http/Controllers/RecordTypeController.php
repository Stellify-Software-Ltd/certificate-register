<?php

namespace App\Http\Controllers;

use App\Models\RecordType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecordTypeController extends Controller
{
    public function index(): View
    {
        return view('types.index', [
            'types' => RecordType::withCount('records')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190|unique:record_types,name',
            'warn_days' => 'required|integer|min:0|max:3650',
            'suggested_interval_months' => 'nullable|integer|min:1|max:600',
        ]);

        RecordType::create($validated);

        return redirect()->route('types.index')->with('status', 'Type added.');
    }

    public function update(Request $request, RecordType $type): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('record_types', 'name')->ignore($type->id)],
            'warn_days' => 'required|integer|min:0|max:3650',
            'suggested_interval_months' => 'nullable|integer|min:1|max:600',
        ]);

        $type->update($validated);

        return redirect()->route('types.index')->with('status', 'Type updated.');
    }
}
