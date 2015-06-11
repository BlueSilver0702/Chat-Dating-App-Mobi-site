<?php

namespace ChatApp\ServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ImagineServiceProvider implements ServiceProviderInterface
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
        if(!isset($app['imagine.factory'])) {
            $app['imagine.factory'] = 'Gd';
        }

        $app['imagine'] = $app->share(function ($app) {
            $class = sprintf('\Imagine\%s\Imagine', $app['imagine.factory']);
            return new $class();
        });
    }
}
