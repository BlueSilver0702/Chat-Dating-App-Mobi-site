<?php

use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use ChatApp\ServiceProvider\DoctrineOrmServiceProvider;
use ChatApp\ServiceProvider\EndroidGcmServiceProvider;
use ChatApp\ServiceProvider\ImagineServiceProvider;
use ChatApp\ServiceProvider\RepositoryServiceProvider;
use Gigablah\Silex\OAuth\OAuthServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use ChatApp\Security\UserProvider;

$app = new Application();

/** Doctrine */
$app->register(new DoctrineServiceProvider());

$app->register(new DoctrineOrmServiceProvider(), array(
    'orm.em.options' => array(
        'mappings' => array(
            array(
                'type' => 'annotation',
                'namespace' => 'ChatApp\\Model\\Entity',
                'path' => __DIR__.'/ChatApp/Model/Entity',
                'use_simple_annotation_reader' => false,
            ),
        ),
    ),
));

/** Repository */
$app->register(new RepositoryServiceProvider(), array('repository.repositories' => array(
    'user.repository' => 'ChatApp\\Model\\Repository\\UserRepository',
    'friend.repository' => 'ChatApp\\Model\\Repository\\FriendRepository',
    'moment.repository' => 'ChatApp\\Model\\Repository\\MomentRepository',
    'moment_comment.repository' => 'ChatApp\\Model\\Repository\\MomentCommentRepository',
    'chat.repository' => 'ChatApp\\Model\\Repository\\ChatRepository',
    'chat_message.repository' => 'ChatApp\\Model\\Repository\\ChatMessageRepository',
    'chat_participant.repository' => 'ChatApp\\Model\\Repository\\ChatParticipantRepository',
)));

/** Security */
$app->register(new OAuthServiceProvider());

$app->register(new SecurityServiceProvider(), array(
    'security.encoder.digest' => $app->share(function ($app) {
        // use plain password
        return new PlaintextPasswordEncoder();
    }),
    'security.firewalls' => array(
        'main' => array(
            'pattern' => '.*',
            'users' => $app->share(function($app) {
                return new UserProvider($app['user.repository']);
            }),
            'oauth' => array(
                //'login_path' => '/auth/{service}',
                //'callback_path' => '/auth/{service}/callback',
                //'check_path' => '/auth/{service}/check',
                'failure_path' => '/login',
            ),
            'form' => array(
                'login_path' => '/login',
                'check_path' => '/login_check',
            ),
            'logout' => true,
            'anonymous' => true,
        ),
    ),
    'security.access_rules' => array(

        // Register & login pages needs to be access without credential
        array('^/register', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/logout', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/login-check', 'IS_AUTHENTICATED_ANONYMOUSLY'),

        // Secured part of the site
        // This config requires being logged for the whole site and having the user role for the panel part.
        // Change these rules to adapt them to your needs
        array('^/app', 'ROLE_USER'),
    ),
    'security.role_hierarchy' => array(
        'ROLE_ADMIN' => array('ROLE_USER'),
        'ROLE_SUPER_ADMIN' => array('ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'),
    ),
));

/** Twig */
$app->register(new TwigServiceProvider(), array(
    'twig.path' => array(__DIR__.'/../templates'),
    'twig.options' => array('cache' => __DIR__.'/../var/cache/twig'),
));

/** Imagine */
$app->register(new ImagineServiceProvider(), array(
    'imagine.factory' => 'Gd',
    'imagine.base_path' => __DIR__.'/vendor/imagine',
));

/** Other services */
$app->register(new SessionServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new SwiftmailerServiceProvider());
$app->register(new EndroidGcmServiceProvider());

/**
 * Middlewares
 */
$app->before(function (Request $request) use ($app) {
    $token = $app['security']->getToken();

    $app['user'] = null;

    if ($token && !$app['security.trust_resolver']->isAnonymous($token)) {
        $app['user'] = $token->getUser();
    }
});

$app['apiSecurityCheck'] = $app->protect(function (Request $request) use ($app) {
    if (!$token = $request->get('token')) {
        throw new TokenNotFoundException('No token found.');
    }

    if (!$user = $app['user.repository']->findUserByToken($token)) {
        throw new BadCredentialsException(sprintf('Token "%s" does not exist.', $token));
    }

    if (!$user->getEnabled()) {
        throw new AccessDeniedException('Access denied.');
    }

    $app['user'] = $user;
});

/**
  * This method manages the place errors will not
  * Managed and controlled by us as a general catch.
  */
$app->error(function (\Exception $e, $code) use ($app) {
    // force JSON format for all API calls
    if (strpos($app['request']->get('_route'), '_api_') !== false) {
        return $app->json(array(
            'error' => true,
            'data' => array(
                'message' => $e->getMessage(),
            ),
        ), $code);
    }
    // show full exception
    elseif ($app['debug']) {
        echo '<pre>';
        var_dump($e->__toString());
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        //'errors/'.$code.'.html.twig',
        //'errors/'.substr($code, 0, 2).'x.html.twig',
        //'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('errorCode' => $code)), $code);
});

return $app;
