<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TabletLayout extends Component
{
    public function __construct(
        public string $subtitle = '',
        public string $title = 'Officina Hub',
    ) {}

    public function render(): View
    {
        return view('layouts.tablet');
    }
}
