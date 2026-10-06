<?php

namespace Tests\Feature;

use App\Models\Timesheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_timesheet_entries_use_the_current_database_columns(): void
    {
        $timesheet = Timesheet::query()->create([
            'company_id' => 55,
            'week_start_date' => '2026-10-05',
            'status' => 'draft',
        ]);

        $entry = $timesheet->entries()->create([
            'entry_date' => '2026-10-06',
            'activity_description' => 'Prepare month-end schedules',
            'hours' => 2.5,
            'is_billable' => true,
            'hourly_rate' => 10000,
            'line_total' => 25000,
        ]);

        $this->assertSame('Prepare month-end schedules', $entry->activity_description);
        $this->assertSame('2.50', $entry->hours);
        $this->assertTrue($entry->is_billable);
        $this->assertSame('25000.00', $entry->line_total);
    }
}
