<?php

namespace App\Models\Admin;

use App\Abstracts\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Result extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'pigeon_number', 'pigeon_time', 'total_time',
        'player_id', 'tournament_id', 'pigeon_total', 'time_in_seconds',
        'date', 'start_time'
    ];
    public function resultOfPlayer()
    {
        return $this->belongsTo(Player::class);
    }
    public function resultOfFlyingDay()
    {
        return $this->belongsTo(TournamentFlyingDay::class);
    }
    public function resultOfTournament()
    {
        return $this->belongsTo(Tournament::class);
    }
    public static function setLogsTableName(): string
    {
        return 'website_activity_logs';
    }
}
