<?php

namespace ChatApp\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegistrationController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->match('/register', 'ChatApp\Controller\RegistrationController::registerAction')->bind('register');

        return $index;
    }

    /**
     * Register.
     *
     * @Route("/register")
     * @method({"GET", "POST"})
     */
    public function registerAction(Application $app, Request $request)
    {
        // redirect if already logged-in
        if ($app['security']->isGranted('ROLE_USER')) {
            return $app->redirect($app['url_generator']->generate('home'));
        }

        $regions = Intl::getRegionBundle()->getCountryNames();
        $error = null;

        if ($request->isMethod('POST')) {
            $subRequest = Request::create('/api/auth/register', 'POST', $request->request->all());

            $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, true);
            $response = json_decode($response->getContent(), true);

            if (isset($response['success']) && $response['success']) {
                // get user
                if ($user = $app['user.repository']->findUserByToken($response['data']['token'])) {
                    // log user in
                    if (null !== ($current_token = $app['security']->getToken())) {
                        $providerKey = method_exists($current_token, 'getProviderKey') ? $current_token->getProviderKey() : $current_token->getKey();
                        $token = new UsernamePasswordToken($user, null, $providerKey);
                        $app['security']->setToken($token);
                    }

                    return $app->redirect($app['url_generator']->generate('dashboard'));
                }
            }

            $error = $response['data']['message'] ?: true;
        }

        return $app['twig']->render('registration/register.html.twig', array(
            'error' => $error,
            'regions' => $regions
        ));
    }
}
