<?php

namespace App\Exceptions;

use RuntimeException;

class TenantNotResolvedException extends RuntimeException
{
    public function __construct(string $message = 'Tenant context is not set. Wrap the operation in TenantContext::runFor().')
    {
        parent::__construct($message);
    }
}
