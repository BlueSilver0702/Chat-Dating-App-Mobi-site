<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

/** Default */
$app->mount('/', new ChatApp\Controller\DefaultController());
$app->mount('/', new ChatApp\Controller\SecurityController());
$app->mount('/', new ChatApp\Controller\RegistrationController());

/** App */
$app->mount('/app', new ChatApp\Controller\AppController());

/** Auth */
$app->mount('/api/auth', new ChatApp\Controller\Api\AuthController());
$app->mount('/api/faceBook/auth', new ChatApp\Controller\Api\FacebookAuthController());

/** Analytics */
$app->mount('/api/analytics', new ChatApp\Controller\Api\AnalyticsController());

/** Other */
$app->mount('/api', new ChatApp\Controller\Api\FileController());
$app->mount('/api/profile', new ChatApp\Controller\Api\ProfileController());
$app->mount('/api/contacts', new ChatApp\Controller\Api\ContactsController());
$app->mount('/api/moments', new ChatApp\Controller\Api\MomentsController());
$app->mount('/api/messages', new ChatApp\Controller\Api\MessagesController());

/** Shared controllers */
$app['api.profile.controller'] = $app->share(function() use ($app) {
    return new ChatApp\Controller\Api\ProfileController();
});
$app['api.contacts.controller'] = $app->share(function() use ($app) {
    return new ChatApp\Controller\Api\ContactsController();
});
$app['api.moments.controller'] = $app->share(function() use ($app) {
    return new ChatApp\Controller\Api\MomentsController();
});
$app['api.messages.controller'] = $app->share(function() use ($app) {
    return new ChatApp\Controller\Api\MessagesController();
});
