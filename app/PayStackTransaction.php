<?php

namespace App;

use App\Enums\TransactionStatus;
use Spatie\Enum\Laravel\HasEnums;
use Illuminate\Database\Eloquent\Model;



class PayStackTransaction extends Model
{
    protected $table = 'global_transactions';
    use HasEnums;

    protected $guarded = [];

    protected $enums = [
        'status' => TransactionStatus::class
    ];

    public function scopeComplete($query){
        return $query->orWhere('status','=', 'complete');
    }

}
