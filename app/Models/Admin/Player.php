<?php

namespace App\Models\Admin;

use App\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'name', 'phone', 'city', 'province', 'club_id',
    ];

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class)->withTimestamps();
    }

    public function playerTournamentResult()
    {
        return $this->hasMany(Result::class);
    }
    public static function setLogsTableName(): string
    {
        return 'website_activity_logs';
    }
}
