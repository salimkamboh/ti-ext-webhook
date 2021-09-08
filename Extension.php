<?php

namespace IgniterLabs\Webhook;

use IgniterLabs\Webhook\Classes\WebhookManager;
use IgniterLabs\Webhook\Models\WebhookLog;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;
use System\Classes\BaseExtension;

/**
 * Webhook Extension Information File
 */
class Extension extends BaseExtension
{
    /**
     * Register method, called when the extension is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/webhook-server.php', 'webhook-server');
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'outgoing_webhook' => 'IgniterLabs\Webhook\Models\Outgoing',
        ]);

        $this->bootWebhookServer();

        if (WebhookManager::isConfigured())
            WebhookManager::bindWebhookEvents();
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Webhooks Settings',
                'description' => 'Configure authentication, signature key settings for the Webhooks extension.',
                'icon' => 'fa fa-cog',
                'model' => 'Igniterlabs\Webhook\Models\Settings',
                'permissions' => ['IgniterLabs.Webhook.ManageSetting'],
            ],
        ];
    }

    /**
     * Registers any admin permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'IgniterLabs.Webhook.ManageSetting' => [
                'description' => 'Manage Webhook settings',
                'group' => 'module',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'tools' => [
                'child' => [
                    'webhooks' => [
                        'priority' => 50,
                        'class' => 'webhooks',
                        'href' => admin_url('igniterlabs/webhook/outgoing'),
                        'title' => lang('igniterlabs.webhook::default.text_title'),
                        'permission' => 'IgniterLabs.Webhooks.*',
                    ],
                ],
            ],
        ];
    }

    public function registerWebhookEvents()
    {
        return [
            'category' => \IgniterLabs\Webhook\WebhookEvents\Category::class,
            'customer' => \IgniterLabs\Webhook\WebhookEvents\Customer::class,
            'menu' => \IgniterLabs\Webhook\WebhookEvents\Menu::class,
            'order' => \IgniterLabs\Webhook\WebhookEvents\Order::class,
            'reservation' => \IgniterLabs\Webhook\WebhookEvents\Reservation::class,
            'table' => \IgniterLabs\Webhook\WebhookEvents\Table::class,
        ];
    }

    public function registerApiResources()
    {
        return [
            'webhooks' => [
                'controller' => \IgniterLabs\Webhook\ApiResources\Webhooks::class,
                'name' => 'Webhooks',
                'description' => 'An API resource for webhooks',
                'actions' => [
                    'store:admin', 'update:admin', 'destroy:admin',
                ],
            ],
        ];
    }

    protected function bootWebhookServer()
    {
        Event::listen([
            WebhookCallSucceededEvent::class,
            WebhookCallFailedEvent::class,
        ], function ($event) {
            WebhookLog::createLog($event);
        });
    }
}
