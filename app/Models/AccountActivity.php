<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_id',
        'consent_id_card',
        'date_consent_id_card',
        'consent_capture_country',
        'presented_languaje',
        'consent_id_email',
        'date_consent_id_email',
        'consent_id_phone',
        'date_consent_id_phone'
    ];

    public $timestamps = false;

    static function generateId($key){
        $catalog = [
            'consent_id1' => 'consent_id_card',
            'consent_id2' => 'consent_id_email',
            'consent_id3' => 'consent_id_phone'
        ];
        do {
            $id = Str::random(30);
            $flag = AccountActivity::where($catalog[$key], $id)->first() != null ? false : true;
            
        } while ($flag == false);
        return $id;
    }
}
