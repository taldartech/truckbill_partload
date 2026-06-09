<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesTenant;

abstract class Controller
{
    use ResolvesTenant;
}
