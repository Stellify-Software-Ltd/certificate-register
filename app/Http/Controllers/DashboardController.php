<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The dashboard buckets expiring records at fixed 30/60/90 day
     * boundaries. Status chips on each row still use the per-type
     * warn_days threshold — the two are deliberately independent.
     */
    public function index(): View
    {
        $records = Record::query()
            ->current()
            ->whereNotNull('expiry_date')
            ->with(['type', 'entity'])
            ->orderBy('expiry_date')
            ->get();

        $buckets = [
            'expired' => collect(),
            'within30' => collect(),
            'within60' => collect(),
            'within90' => collect(),
        ];

        foreach ($records as $record) {
            $days = $record->daysUntilExpiry();

            if ($days < 0) {
                $buckets['expired']->push($record);
            } elseif ($days <= 30) {
                $buckets['within30']->push($record);
            } elseif ($days <= 60) {
                $buckets['within60']->push($record);
            } elseif ($days <= 90) {
                $buckets['within90']->push($record);
            }
        }

        return view('dashboard', ['buckets' => $buckets]);
    }
}
