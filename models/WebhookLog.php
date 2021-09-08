<?php

namespace IgniterLabs\Webhook\Models;

use GuzzleHttp\Psr7\Response;
use Igniter\Flame\Database\Model;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

/**
 * Webhook Log Model
 */
class WebhookLog extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'igniterlabs_webhook_logs';

    public $timestamps = TRUE;

    /**
     * @var array Guarded fields
     */
    public $guarded = [];

    protected $casts = [
        'webhook_id' => 'integer',
        'is_success' => 'boolean',
        'payload' => 'array',
        'response' => 'array',
    ];

    protected $appends = [
        'status_name', 'created_since',
    ];

    public function webhook()
    {
        return $this->morphTo('webhook');
    }

    //
    //
    //

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @param \Igniter\Flame\Database\Model $webhook
     * @return mixed
     */
    public function scopeApplyWebhook($query, $webhook)
    {
        return $query
            ->where('webhook_type', $webhook->getMorphClass())
            ->where('webhook_id', $webhook->getKey());
    }

    //
    //
    //

    public static function createLog(WebhookCallEvent $webhookEvent)
    {
        $response = [];
        if ($webhookEvent->response instanceof Response)
            $response = $webhookEvent->response->getBody()->getContents();

        $isSuccess = $webhookEvent instanceof WebhookCallSucceededEvent;

        $message = $isSuccess
            ? 'Payload delivered successfully'
            : e($webhookEvent->errorMessage ?? 'No error message available.');

        return self::create(array_merge($webhookEvent->meta, [
            'payload' => $webhookEvent->payload,
            'is_success' => $isSuccess,
            'message' => $message,
            'response' => $response,
        ]));
    }

    public function markAsSuccessful()
    {
        $this->is_success = TRUE;

        $this->save();

        return $this;
    }

    public function markAsFailed()
    {
        $this->is_success = FALSE;

        $this->save();

        return $this;
    }

    //
    //
    //

    public function getStatusNameAttribute($value)
    {
        return lang($this->is_success
            ? 'igniterlabs.webhook::default.text_success'
            : 'igniterlabs.webhook::default.text_failed'
        );
    }

    public function getCreatedSinceAttribute($value)
    {
        return $this->created_at ? day_elapsed($this->created_at) : null;
    }
}
