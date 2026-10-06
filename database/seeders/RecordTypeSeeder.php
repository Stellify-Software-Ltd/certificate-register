<?php

namespace Database\Seeders;

use App\Models\RecordType;
use Illuminate\Database\Seeder;

class RecordTypeSeeder extends Seeder
{
    /**
     * Common record types, seeded as suggestions only. warn_days drives
     * the per-type "expiring soon" chip; suggested_interval_months only
     * ever pre-fills the expiry date on the renew form. Both are fully
     * editable on the Types screen — nothing here is a statement of what
     * any business is required to do.
     */
    public function run(): void
    {
        $types = [
            // [name, warn_days, suggested_interval_months]
            ['Gas Safety (CP12)', 30, 12],
            ['Electrical EICR', 60, 60],
            ['PAT testing', 30, 12],
            ['Fire alarm service', 30, 6],
            ['Fire extinguisher service', 30, 12],
            ['Legionella risk assessment', 60, 24],
            ['Lift inspection (LOLER)', 30, 6],
            ["Employers' liability insurance", 30, 12],
            ['Public liability insurance', 30, 12],
            ['F-Gas', 30, 12],
            ['Calibration', 30, 12],
            ['Premises licence', 30, null],
            ['First aid at work', 60, 36],
            ['Food hygiene', 60, 36],
            ['Forklift licence', 60, 36],
            ['SSSTS', 60, 60],
        ];

        foreach ($types as [$name, $warnDays, $intervalMonths]) {
            RecordType::firstOrCreate(
                ['name' => $name],
                ['warn_days' => $warnDays, 'suggested_interval_months' => $intervalMonths],
            );
        }
    }
}
