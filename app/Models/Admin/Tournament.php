<?php

namespace App\Models\Admin;

use App\Abstracts\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'name', 'days', 'status', 'show', 'pigeons',
        'start_date', 'start_time', 'supporter', 'club_id', 'poster', 'sort', 'type', 'public_hide',
        'allow_double_stamp', 'allow_auto_update'
    ];


    public function players()
    {
        return $this->belongsToMany(Player::class)->withTimestamps();
    }

    public function flyingDays()
    {
        return $this->hasMany(TournamentFlyingDay::class);
    }
    public function tournamentResult()
    {
        return $this->hasMany(Result::class);
    }

    public function tournamentOwner()
    {
        return $this->belongsTo(Club::class);
    }
    public function tournamentPrize()
    {
        return $this->hasMany(TournamentPrize::class);
    }
    public static function setLogsTableName(): string
    {
        return 'website_activity_logs';
    }
}
