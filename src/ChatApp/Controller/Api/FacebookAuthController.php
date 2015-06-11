<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use ChatApp\Model\Entity\User;
use ChatApp\Util\Inflector;
use ChatApp\OAuth\FacebookService;

class FacebookAuthController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->post('/login', 'ChatApp\Controller\Api\FacebookAuthController::loginAction');
        $index->post('/register', 'ChatApp\Controller\Api\FacebookAuthController::registerAction');

        return $index;
    }

    /**
     * Facebook login.
     *
     * @param string $uid          The User facebook_uid
     * @param string $access_token Facebook access_token
     *
     * @Route("/api/faceBook/auth/login")
     * @method("POST")
     */
    public function loginAction(Application $app, Request $request)
    {
        $facebookUid = $request->get('uid');
        $accessToken = $request->get('access_token');

        if (!$facebookUid || !$accessToken) {
            throw new AccessDeniedException('The Facebook user id and access token can not be empty.');
        }

        // check facebook user data
        if (!FacebookService::getUser($app, array('token' => $facebookUid, 'uid' => $accessToken))) {
            throw new BadCredentialsException('Invalid access token');
        }

        // set returns data
        $data = array();

        if ($user = $app['user.repository']->findUserByFacebookUid($facebookUid)) {
            $data['token'] = $user->getToken();
        } else {
            $data['registration_required'] = true;
        }

        return $app->json(array(
            'success' => true,
            'data' => $data,
        ));
    }

    /**
     * Facebook register.
     *
     * @param string $uid          The User facebook_uid
     * @param string $access_token Facebook access_token
     * @param string $promo_code   The User promo code used for tracking
     * @param string $username     The User username
     *
     * @Route("/api/faceBook/auth/register")
     * @method("POST")
     */
    public function registerAction(Application $app, Request $request)
    {
        $facebookUid = $request->get('uid');
        $accessToken = $request->get('access_token');
        $username = $request->get('username');
        $password = $request->get('password');

        if (!$facebookUid || !$accessToken || !$username || !$password) {
            throw new AccessDeniedException('Missing required fields.');
        }

        if ($app['user.repository']->findUserByUsername($username)) {
            throw new \Exception('Username already exists');
        }

        // get facebook user data
        if (!$facebookUser = FacebookService::getUser($app, array('token' => $facebookUid, 'uid' => $accessToken))) {
            throw new BadCredentialsException('Invalid access token');
        }

        // get values
        $location = isset($facebookUser['location']['name']) ? $facebookUser['location']['name'] : null;
        $bio = isset($facebookUser['bio']) ? $facebookUser['bio'] : null;
        $email = $facebookUser['email'];

        if ($app['user.repository']->findUserByEmail($email)) {
            throw new \Exception('Email already registered.');
        }

        // set user object
        $user = new User();
        $user->setFacebookUid($facebookUid);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setName($facebookUser['name']);
        $user->setGender($facebookUser['gender'][0]);
        $user->setRegion($location);
        $user->setAboutme($bio);
        $user->setEnabled(true);

        if ($promoCode = $request->get('promo_code')) {
            $user->setPromoCode($promoCode);
        }

        // store user photo and assign to user
        if (isset($facebookUser['picture']) && !$facebookUser['picture']) {
            $filename = Inflector::getRandomString(32).basename($facebookUser['picture']);

            $data = file_get_contents($facebookUser['picture']);

            if ($data !== false && file_put_contents($app['media_dir'] . $filename, $data) !== false) {
                $user->setPhoto($filename);
            }
        }

        // create user
        $app['user.repository']->create($user);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'token' => $user->getToken(),
            ),
        ));
    }
}
