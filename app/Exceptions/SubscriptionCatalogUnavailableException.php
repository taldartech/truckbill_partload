<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionCatalogUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Unable to load subscription pricing. Please try again later.');
    }
}
