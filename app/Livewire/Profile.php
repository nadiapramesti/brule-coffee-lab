<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Profil - KROMATIK')]
class Profile extends Component
{
    public function render()
    {
        return view('livewire.profile');
    }
}
