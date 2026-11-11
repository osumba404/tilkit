<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $metaDescription = null,
        public ?string $canonical = null,
        public string $robots = 'noindex, nofollow',
    ) {}

    public function render(): View
    {
        return view('layouts.guest', [
            'pageTitle' => $this->title ?? config('app.name', 'Tilkit'),
            'metaDescription' => $this->metaDescription,
            'canonical' => $this->canonical ?? url()->current(),
            'robots' => $this->robots,
        ]);
    }
}
