<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    /**
     * Demo data for a small mixed site. Every date is relative to today
     * so the seed never goes stale — run `php artisan migrate:fresh --seed`
     * any day and the dashboard tells the same story.
     */
    public function run(): void
    {
        $today = Record::today();

        // Expired, badly — forgotten for over a year.
        $eicr = $this->record('Electrical EICR', 'Reception block', [
            'issue_date' => $today->subDays(400)->subYears(5),
            'expiry_date' => $today->subDays(400),
            'issuer' => 'Calder Electrical Services',
            'reference_number' => 'EICR-2019-0481',
            'responsible_person' => 'Karen Doyle',
            'notes' => 'Found in the old filing cabinet during the office move.',
        ]);

        // Expired recently.
        $this->record('Fire alarm service', 'Whole site', [
            'issue_date' => $today->subDays(10)->subMonths(6),
            'expiry_date' => $today->subDays(10),
            'issuer' => 'Pennine Fire & Security',
            'reference_number' => 'FA-8812',
            'responsible_person' => 'Karen Doyle',
        ]);

        // Expiring inside its type threshold (Gas Safety warns at 30 days).
        $gas = $this->record('Gas Safety (CP12)', 'Unit 14 boiler', [
            'issue_date' => $today->addDays(12)->subYear(),
            'expiry_date' => $today->addDays(12),
            'issuer' => 'B. Hartley Heating',
            'reference_number' => 'CP12-44721',
            'responsible_person' => 'Mike Sutton',
        ]);

        // Expiring exactly on its type threshold day (PAT warns at 30 days).
        $this->record('PAT testing', 'Bar area', [
            'issue_date' => $today->addDays(30)->subYear(),
            'expiry_date' => $today->addDays(30),
            'issuer' => 'SafeTest North',
            'responsible_person' => 'Mike Sutton',
        ]);

        // In the 60-day dashboard bucket but outside its own 30-day threshold.
        $this->record('Fire extinguisher service', 'Main kitchen', [
            'issue_date' => $today->addDays(45)->subYear(),
            'expiry_date' => $today->addDays(45),
            'issuer' => 'Pennine Fire & Security',
            'reference_number' => 'EXT-3310',
            'responsible_person' => 'Karen Doyle',
        ]);

        // Comfortably valid.
        $this->record('Legionella risk assessment', 'Pool plant room', [
            'issue_date' => $today->subMonths(14),
            'expiry_date' => $today->addMonths(10),
            'issuer' => 'AquaSafe Compliance',
            'reference_number' => 'LRA-2025-118',
            'responsible_person' => 'Mike Sutton',
        ]);

        $this->record('Public liability insurance', 'Whole site', [
            'issue_date' => $today->subMonths(5),
            'expiry_date' => $today->addMonths(7),
            'issuer' => 'Harbour Mutual',
            'reference_number' => 'PL-99120764',
            'responsible_person' => 'Karen Doyle',
        ]);

        $this->record('F-Gas', 'Main kitchen', [
            'issue_date' => $today->subMonths(7),
            'expiry_date' => $today->addMonths(5),
            'issuer' => 'ColdChain Services',
            'responsible_person' => 'Mike Sutton',
        ]);

        $this->record('First aid at work', 'Dave Mitchell', [
            'issue_date' => $today->subMonths(22),
            'expiry_date' => $today->addMonths(14),
            'issuer' => 'St John Ambulance',
            'reference_number' => 'FAW-230871',
            'responsible_person' => 'Karen Doyle',
        ], entityKind: 'person');

        $this->record('Forklift licence', 'Dave Mitchell', [
            'issue_date' => $today->subMonths(20),
            'expiry_date' => $today->addMonths(16),
            'issuer' => 'RTITB',
            'reference_number' => 'FLT-77012',
            'responsible_person' => 'Mike Sutton',
        ], entityKind: 'person');

        $this->record('Calibration', 'Weighbridge', [
            'issue_date' => $today->subMonths(8),
            'expiry_date' => $today->addMonths(4),
            'issuer' => 'Precision Weighing Ltd',
            'reference_number' => 'CAL-5518',
            'responsible_person' => 'Mike Sutton',
        ]);

        // Genuinely never expires.
        $this->record('Premises licence', 'Whole site', [
            'issue_date' => $today->subYears(6),
            'expiry_date' => null,
            'issuer' => 'Local authority licensing',
            'reference_number' => 'PL-0042',
            'responsible_person' => 'Karen Doyle',
            'notes' => 'Annual fee is paid separately — the licence itself has no expiry date.',
        ]);

        // A renewal chain: last year's policy, replaced by this year's.
        $oldPolicy = $this->record("Employers' liability insurance", 'Whole site', [
            'issue_date' => $today->subMonths(13),
            'expiry_date' => $today->subMonths(1),
            'issuer' => 'Harbour Mutual',
            'reference_number' => 'EL-88234501',
            'responsible_person' => 'Karen Doyle',
        ]);

        $currentPolicy = $this->record("Employers' liability insurance", 'Whole site', [
            'issue_date' => $today->subMonths(1),
            'expiry_date' => $today->addMonths(11),
            'issuer' => 'Harbour Mutual',
            'reference_number' => 'EL-91077443',
            'responsible_person' => 'Karen Doyle',
            'supersedes_id' => $oldPolicy->id,
        ]);

        // Placeholder documents on a few records.
        $this->attachDocument($gas, 'gas-safety-cp12.pdf');
        $this->attachDocument($currentPolicy, 'employers-liability-schedule.pdf');
        $this->attachDocument($eicr, 'eicr-report-2019.pdf');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function record(string $typeName, string $entityName, array $attributes, ?string $entityKind = null): Record
    {
        $type = RecordType::resolveByName($typeName);
        $entity = Entity::resolveByName($entityName);

        if ($entityKind && ! $entity->kind) {
            $entity->update(['kind' => $entityKind]);
        }

        return Record::create($attributes + [
            'record_type_id' => $type->id,
            'entity_id' => $entity->id,
        ]);
    }

    private function attachDocument(Record $record, string $filename): void
    {
        $path = 'documents/seed-'.$filename;
        Storage::put($path, $this->placeholderPdf(pathinfo($filename, PATHINFO_FILENAME)));

        $record->documents()->create([
            'path' => $path,
            'original_name' => $filename,
            'mime' => 'application/pdf',
            'size' => Storage::size($path),
        ]);
    }

    /**
     * A small but structurally valid single-page PDF with the given text,
     * built with correct cross-reference offsets so any viewer opens it.
     */
    private function placeholderPdf(string $text): string
    {
        $stream = "BT /F1 18 Tf 60 770 Td (Placeholder: {$text}) Tj ET";

        $objects = [
            1 => "<</Type/Catalog/Pages 2 0 R>>",
            2 => "<</Type/Pages/Kids[3 0 R]/Count 1>>",
            3 => "<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>",
            4 => "<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>",
            5 => "<</Length ".strlen($stream).">>\nstream\n{$stream}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<</Size ".(count($objects) + 1)."/Root 1 0 R>>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
