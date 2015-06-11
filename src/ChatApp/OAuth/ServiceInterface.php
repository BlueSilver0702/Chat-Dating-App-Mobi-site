<?php

namespace ChatApp\OAuth;

use Silex\Application;

interface ServiceInterface
{
    /**
     * Gets user information.
     *
     * @param Application $app     Silex application
     * @param string      $options The options
     */
    public static function getUser(Application $app, array $options);
}
