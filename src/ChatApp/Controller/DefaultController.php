<?php

namespace ChatApp\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\HttpFoundation\Request;

class DefaultController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->match('/', 'ChatApp\Controller\DefaultController::indexAction')->bind('homepage');

        return $index;
    }

    /**
     * Homepage.
     *
     * @Route("/")
     * @method({"GET", "POST"})
     */
    public function indexAction(Application $app, Request $request)
    {
        // redirect if already logged-in
        if ($app['security']->isGranted('ROLE_USER')) {
            return $app->redirect($app['url_generator']->generate('home'));
        }

        $regions = Intl::getRegionBundle()->getCountryNames();

        return $app['twig']->render('index.html.twig', array(
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'regions' => $regions
        ));

    }
}
