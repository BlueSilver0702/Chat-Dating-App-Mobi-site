<?php

namespace ChatApp\ServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class RepositoryServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        foreach ($app['repository.repositories'] as $label => $class) {
            $app[$label] = $app->share(function($app) use ($class) {
                return new $class($app['orm.em']);
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function register(Application $app) {}
}
