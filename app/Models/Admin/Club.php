<?php

namespace App\Models\Admin;

use App\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'owner', 'phone', 'city', 'poster', 'status', 'sort',
    ];

    public function clubTournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public static function setLogsTableName(): string
    {
        return 'website_activity_logs';
    }
}
