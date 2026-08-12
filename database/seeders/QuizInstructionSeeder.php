<?php

namespace Database\Seeders;

use App\Models\QuizInstruction;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizInstructionSeeder extends Seeder
{
    /**
     * Seed the single global quiz instructions record, if it doesn't exist yet.
     */
    public function run(): void
    {
        if (QuizInstruction::exists()) {
            return;
        }

        QuizInstruction::create([
            'content' => <<<'HTML'
                <p>Please read the following before you begin:</p>
                <ul>
                    <li>Each quiz has a limited number of attempts &mdash; check the overview page before starting.</li>
                    <li>If a time limit applies, it starts as soon as you click <strong>Start Attempt</strong> and cannot be paused.</li>
                    <li>Answer every question to the best of your ability. Unanswered questions receive no points.</li>
                    <li>Once you submit, you cannot change your answers for that attempt.</li>
                    <li>Do not refresh or close this page while a timed attempt is in progress &mdash; you may lose remaining time.</li>
                    <li>This assessment must be completed independently. Do not collaborate with classmates or use outside assistance unless your instructor says otherwise.</li>
                </ul>
                <p>Good luck!</p>
                HTML,
            'updated_by' => User::whereHas('roles', fn ($query) => $query->where('name', 'School Admin'))->first()?->id,
        ]);
    }
}
