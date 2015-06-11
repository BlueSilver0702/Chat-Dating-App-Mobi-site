<?php

namespace ChatApp\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

class SecurityController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->get('/login', 'ChatApp\Controller\SecurityController::loginAction')->bind('login');

        // The login_check and logout are dummy routes so we can use the names.
        // The security provider should intercept these, so no controller is needed.
        $index->match('/login_check', 'ChatApp\Controller\SecurityController::securityCheckAction')->bind('login-check');
        $index->get('/logout', 'ChatApp\Controller\SecurityController::logoutAction')->bind('logout');

        // oauth
        $index->match('/auth/{service}/callback', 'ChatApp\Controller\SecurityController::authServiceCallbackAction');
        $index->match('/auth/{service}/check', 'ChatApp\Controller\SecurityController::authServiceCheckAction');

        return $index;
    }

    /**
     * Login.
     *
     * @Route("/login")
     * @method("GET")
     */
    public function loginAction(Application $app, Request $request)
    {
        // redirect if already logged-in
        if ($app['security']->isGranted('ROLE_USER')) {
            return $app->redirect($app['url_generator']->generate('home'));
        }

        return $app['twig']->render('security/login.html.twig', array(
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'regions' => Intl::getRegionBundle()->getCountryNames(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function securityCheckAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form in your security firewall configuration.');
    }

    /**
     * {@inheritDoc}
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    /**
     * @param string $service
     */
    public function authServiceCheckAction($service)
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using oauth in your security firewall configuration.');
    }

    /**
     * @param string $service
     */
    public function authServiceCallbackAction($service)
    {
        throw new \RuntimeException('You must activate the callback in your oauth security firewall configuration.');
    }
}
