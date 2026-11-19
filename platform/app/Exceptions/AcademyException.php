<?php

namespace App\Exceptions;

use RuntimeException;

/** خطأ منطقي يعرض للمستخدم كما هو (رصيد غير كاف، قسيمة مستخدمة ...) */
class AcademyException extends RuntimeException
{
    public function __construct(string $message, public string $errorCode = 'error', public int $status = 422)
    {
        parent::__construct($message);
    }
}
