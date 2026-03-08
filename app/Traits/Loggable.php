<?php

namespace App\Traits;

use App\Models\Admin\Result;
use App\Services\WebsiteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait Loggable
{
    public static function bootLoggable()
    {
        static::saved(function ($model) {
            resolve(WebsiteService::class)->flushCache();
        });
        static::created(static function ($model) {
            if (!$model instanceof Result) {
                $data = static::getDataForLogs($model, 'created');
                static::insertLog($model, $data, 'created');
            }
        });
        static::updated(static function ($model) {
            if (!$model instanceof Result) {
                $data = static::getDataForLogs($model, 'updated');
                if (!empty($data['original']) && !empty($data['changes'])) {
                    static::insertLog($model, $data, 'updated');
                }
            }
        });
        static::deleted(static function ($model) {
            if (!$model instanceof Result) {
                $data = static::getDataForLogs($model, 'delete');
                static::insertLog($model, $data, 'delete');
            }
        });
    }

    public static function getDataForLogs($model, $event): array
    {
        $original = [];
        $changes = [];
        if ('created' === $event) {
            $changes = $model->getAttributes();
        } elseif ('updated' === $event) {
            $changes = static::modelWithoutTimeStamps($model->getChanges());
            $original = array_intersect_key(static::modelWithoutTimeStamps($model->getOriginal()), $changes);
            static::discardUnchangedValues($changes, $original);
        }

        return compact('original', 'changes');
    }

    private static function insertLog($model, $data, $event)
    {
        \DB::table(static::setLogsTableName())->insert([
            'loggable_type' => get_class($model),
            'loggable_id' => $model->id,
            'action' => $event,
            'action_by' => \Auth::id(),
            'payload' => json_encode([
                'previous' => $data['original'],
                'new' =>  $data['changes'],
            ]),
            'created_at' => Carbon::now(),
            'updated_at'=> Carbon::now(),
        ]);
        //we can utilize log_payload in Stages remarks
        $model->log_payload = json_encode([
            'previous' => $data['original'],
            'new' =>  $data['changes'],
        ]);
        if ($model::CACHE_KEY !== null) {
            Cache::tags(['models',$model::CACHE_KEY])->forget($model::CACHE_KEY.$model->id);
        }

    }

    /**
     * @param $changes
     * @return array
     */
    protected static function modelWithoutTimeStamps($changes): array
    {
        unset($changes['updated_at']);
        unset($changes['created_at']);

        return $changes;
    }

    private static function discardUnchangedValues(&$changes, &$original)
    {
        foreach ($original as $column => $originalValue) {
            if ($originalValue == $changes[$column]) {
                unset($changes[$column]);
                unset($original[$column]);
            } elseif (self::isSameDecimalNumber($originalValue, $changes[$column])) {
                unset($changes[$column]);
                unset($original[$column]);
            }
        }
    }

    /**
     * @param $originalValue
     * @param $number
     * @return bool
     */
    private static function isSameDecimalNumber($originalValue, $number): bool
    {
        return is_numeric($number) && ((int)$number == (int)($originalValue));
    }
}
