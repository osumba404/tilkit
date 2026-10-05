<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class FieldReferenceController extends Controller
{
    public function __invoke(): View
    {
        return view('reference.index');
    }
}
