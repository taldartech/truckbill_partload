<?php

namespace App\Enums;

enum SubscriptionAccessMode: string
{
    case Full = 'full';
    case GraceReadOnly = 'grace';
    case Locked = 'locked';
}
