<?php

namespace Kreatif\Translum\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TranslationsSaving
{
    use Dispatchable;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
