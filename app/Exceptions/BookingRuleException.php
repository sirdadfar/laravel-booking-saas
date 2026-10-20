<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown for business-rule violations that should be shown to the customer as
 * a 422 response (outside working hours, wrong staff, past date, ...).
 */
class BookingRuleException extends RuntimeException {}
