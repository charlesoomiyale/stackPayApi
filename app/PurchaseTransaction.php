<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;

class PurchaseTransaction extends Model
{

    use GeneratesUuid;

    protected $table = 'purchase_transactions';
    protected $guarded = [];

    public function uuidColumn()
    {
        return 'transaction_reference';
    }
}
