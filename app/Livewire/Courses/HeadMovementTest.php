<?php

namespace App\Livewire\Courses;

use Livewire\Component;

class HeadMovementTest extends Component
{
    public function mount(): void
    {
        abort_unless(app()->environment('local'), 404);
    }

    public function render()
    {
        return view('livewire.courses.head-movement-test')
            ->extends('layouts.app', ['skipTopbar' => true, 'skipSidebar' => true])
            ->section('app-content');
    }
}
