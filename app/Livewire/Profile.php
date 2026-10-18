<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Profil - BRÛLÉ')]
class Profile extends Component
{
    public function render()
    {
        return view('livewire.profile');
    }
}
