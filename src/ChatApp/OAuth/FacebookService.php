<?php

namespace ChatApp\OAuth;

use Silex\Application;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\Common\Consumer\Credentials;
use OAuth\ServiceFactory;

class FacebookService implements ServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getUser(Application $app, array $options)
    {
        // create a new instance of the URI class with the current
        // URI, stripping the query string
        $uriFactory = new UriFactory();
        $currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
        $currentUri->setQuery('');

        // setup the credentials for the requests
        $credentials = new Credentials(
            $app['facebook']['key'],
            $app['facebook']['secret'],
            $currentUri->getAbsoluteUri()
        );

        /** @var $serviceFactory ServiceFactory An OAuth service factory. */
        $serviceFactory = new ServiceFactory();

        // instantiate the Facebook service using the credentials
        /** @var $facebookService \OAuth\OAuth2\Service\Facebook */
        $facebookService = $serviceFactory->createService('facebook', $credentials);

        // this was a callback request from facebook, get the token
        $token = $facebookService->requestAccessToken($options['token']);

        // send a request with it
        $me = json_decode($facebookService->request('/me'), true);

        // get user photo
        $picture = $facebookService->request(sprintf('/%s/picture', $me['id']), array(
            'redirect' => false,
            'width' => 256,
            'height' => 256
        ));

        if (is_array($picture) && !$picture['data']['is_silhouette']) {
            $me['picture'] = $profilePicture['data']['url'];
        }

        return $me['id'] == $options['uid'] ? $me : false;
    }
}
