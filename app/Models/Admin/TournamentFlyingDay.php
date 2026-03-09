<?php

namespace App\Models\Admin;

use App\Abstracts\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentFlyingDay extends BaseModel
{
    use HasFactory;
    protected $fillable = ['tournament_id', 'date'];
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public static function setLogsTableName(): string
    {
        return 'website_activity_logs';
    }
}
