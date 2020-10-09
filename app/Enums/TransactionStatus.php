<?php

namespace App\Enums;


use Spatie\Enum\Enum;

/**
 * @method static self pending()
 * @method static self in_progress()
 * @method static self complete()
 * @method static self failed()
 */

class TransactionStatus extends Enum
{
}
