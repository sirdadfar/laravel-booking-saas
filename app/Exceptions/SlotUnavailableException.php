<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a slot was bookable but has been taken before the request could
 * be committed (for example two customers clicking "book" at the same time).
 */
class SlotUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'این زمان دیگر در دسترس نیست. لطفاً زمان دیگری انتخاب کنید.')
    {
        parent::__construct($message);
    }
}
