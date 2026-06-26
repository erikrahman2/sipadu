<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsGlobalSettings extends Model
{
    protected $fillable = [
        'key',
        'footer_address',
        'footer_phone',
        'footer_email',
        'footer_social_link',
        'footer_copyright',
    ];

    public static function getFooter()
    {
        $row = static::firstOrCreate(['key' => 'global'], []);
        return $row;
    }
}
