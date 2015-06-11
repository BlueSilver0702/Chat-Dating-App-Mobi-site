<?php

namespace ChatApp\ServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class EndroidGcmServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function boot(Application $app) {}

    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        if(!isset($app['endroid.gcm.api_url'])) {
            $app['endroid.gcm.api_url'] = 'https://android.googleapis.com/gcm/send';
        }

        $app['endroid.gcm'] = $app->share(function ($app) {
            return new \Endroid\Gcm\Gcm($app['endroid.gcm.api_key'], $app['endroid.gcm.api_url']);
        });
    }
}
