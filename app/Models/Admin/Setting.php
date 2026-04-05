<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group_type'
    ];

    const FIRST_WINNER_LAST_WINNER_GROUP = 'first_winner_last_winner';
    const AUTO_UPDATE_TIME_GROUP = 'auto_update_time';
    const GUEST_LINK_GROUP = 'guest_link_expiry';
}
