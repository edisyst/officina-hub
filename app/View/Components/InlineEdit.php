<?php

namespace App\View\Components;

use Illuminate\View\Component;

class InlineEdit extends Component
{
    public function __construct(
        public int $recordId,
        public string $field,
        public mixed $value,
        public string $saveMethod = 'salvaInlineEdit',
        public string $type = 'text',
        public ?string $step = null,
        public ?string $min = null,
        public ?string $placeholder = null,
        public string $displayClass = '',
    ) {}

    public function render()
    {
        return view('components.inline-edit');
    }
}
