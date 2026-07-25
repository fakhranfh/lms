<?php

namespace App\Livewire;

use Livewire\Component;

class MySchools extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function render()
    {
        return view('livewire.my-schools', [
            'schools' => auth()->user()->schools,
        ])
            ->extends('master', ['body_class' => 'bg-background text-on-background min-h-screen flex flex-col font-body-md'])
            ->section('content');
    }
}
