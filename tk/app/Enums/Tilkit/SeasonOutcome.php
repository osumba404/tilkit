<?php

namespace App\Enums\Tilkit;

enum SeasonOutcome: string
{
    case Hit = 'hit';
    case Miss = 'miss';
    case FalseStart = 'false_start';
}
