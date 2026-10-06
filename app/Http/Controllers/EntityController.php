<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\View\View;

class EntityController extends Controller
{
    /**
     * An entity's full history, renewed (superseded) records included —
     * this page is where the paper trail lives.
     */
    public function show(Entity $entity): View
    {
        $records = $entity->records()
            ->with(['type', 'supersededBy'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        return view('entities.show', ['entity' => $entity, 'records' => $records]);
    }
}
