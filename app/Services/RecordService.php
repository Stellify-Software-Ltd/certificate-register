<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;

class RecordService
{
    /**
     * Create or update a record from form input. Unknown type and entity
     * names are created on the fly with default settings.
     *
     * @param array{
     *     type_name: string,
     *     entity_name: string,
     *     issue_date: string,
     *     expiry_date?: ?string,
     *     issuer?: ?string,
     *     reference_number?: ?string,
     *     notes?: ?string,
     *     responsible_person?: ?string,
     *     supersedes_id?: ?int,
     * } $input
     */
    public function save(array $input, ?Record $record = null): Record
    {
        $type = RecordType::resolveByName($input['type_name']);
        $entity = Entity::resolveByName($input['entity_name']);

        $attributes = [
            'record_type_id' => $type->id,
            'entity_id' => $entity->id,
            'issue_date' => $input['issue_date'],
            'expiry_date' => $input['expiry_date'] ?? null,
            'issuer' => $input['issuer'] ?? null,
            'reference_number' => $input['reference_number'] ?? null,
            'notes' => $input['notes'] ?? null,
            'responsible_person' => $input['responsible_person'] ?? null,
        ];

        if ($record === null) {
            $attributes['supersedes_id'] = $input['supersedes_id'] ?? null;

            return Record::create($attributes);
        }

        $record->update($attributes);

        return $record;
    }
}
