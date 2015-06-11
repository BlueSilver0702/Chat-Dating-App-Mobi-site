<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Intl\Intl;
use ChatApp\Model\Entity\User;
use ChatApp\Util\Inflector;
use ChatApp\Util\Mailer;

class AuthController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->post('/login', 'ChatApp\Controller\Api\AuthController::loginAction');
        $index->post('/register', 'ChatApp\Controller\Api\AuthController::registerAction');
        $index->post('/resend-code', 'ChatApp\Controller\Api\AuthController::resendCodeAction');
        $index->post('/verify', 'ChatApp\Controller\Api\AuthController::verifyAction');
        $index->post('/recover', 'ChatApp\Controller\Api\AuthController::recoverAction');

        return $index;
    }

    /**
     * Login.
     *
     * @param string $username The User username, phone number, or email
     * @param string $password The User password
     *
     * @return string The user token
     *
     * @Route("/api/auth/login")
     * @method("POST")
     */
    public function loginAction(Application $app, Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');

        if (!$username || !$password) {
            throw new AccessDeniedException('The username and password can not be empty.');
        }

        if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        if ($user->getPassword() != $password) {
            throw new BadCredentialsException('Bad credentials.');
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'token' => $user->getToken(),
            ),
        ));
    }

    /**
     * Register via email or phone number.
     *
     * @param string $name         The User name
     * @param string $username     The User username
     * @param string $password     The User password
     * @param string $email        The User email
     * @param string $phone_number The User phone number
     * @param string $gender       The User gender
     * @param array  $birthday     The User birthday
     * @param string $promo_code   The User promotion code
     *
     * @Route("/api/auth/register")
     * @method("POST")
     */
    public function registerAction(Application $app, Request $request)
    {
        $user = new User();
        $user->setName($request->get('name'));
        $user->setGender($request->get('gender'));
        $user->setRegion($request->get('region'));
        $user->setEthnicity($request->get('ethnicity'));

        if (!$password = $request->get('password')) {
            $password = Inflector::getRandomString(6);
        }
        $user->setPassword($password);

        if ($promoCode = $request->get('promo_code')) {
            $user->setPromoCode($promoCode);
        }

        // set username
        if ($username = $request->get('username')) {
            if (count($app['validator']->validateValue($username, new Assert\Regex(array('pattern' => '/^\w+/')))) > 0) {
                throw new \Exception('Username may only contain alphanumeric characters.');
            }
            if (count($app['validator']->validateValue($username, new Assert\Length(array('max' => 15)))) > 0) {
                throw new \Exception('Username is too long (maximum is 15 characters) and may only contain alphanumeric characters.');
            }
            if ($app['user.repository']->findUserByUsername($username)) {
                throw new \Exception(sprintf('Username %s already taken', $username));
            }

            $user->setUsername($username);
        } else {
            throw new \Exception('Missing username.');
        }

        // set email
        if ($email = $request->get('email')) {
            if (count($app['validator']->validateValue($user->getEmail(), new Assert\Email())) > 0) {
                throw new \Exception('Invalid email.');
            }
            if ($app['user.repository']->findUserByEmail($email)) {
                throw new \Exception(sprintf('Email "%s" already exist.', $email));
            }

            $user->setEmail($email);
        } else {
            throw new \Exception('Missing email.');
        }

        // set phone
        if ($phone_number = $request->get('phone_number')) {
            if (count($app['validator']->validateValue($user->getPhoneNumber(), new Assert\Regex(array('pattern' => '/\d/')))) > 0) {
                throw new \Exception('Phone number may only contain numeric characters.');
            }
            if ($app['user.repository']->findUserByPhoneNumber($phone_number)) {
                throw new \Exception(sprintf('Phone number "%s" already exist.', $phone_number));
            }

            $user->setPhoneNumber($phone_number);
        }

        // set birthday
        if ($birthday = $request->get('birthday')) {
            $birthday = sprintf('%04d-%02d-%02d', $birthday['year'], $birthday['month'], $birthday['day']);
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $birthday)) {
                $birthday = new \DateTime($birthday);
                if ($birthday->diff(new \DateTime())->format('%y') < 13) {
                    throw new \Exception('User must be older than 13 year old.');
                }

                $user->setBirthday($birthday);
            } else {
                throw new \Exception(sprintf('Birthday "%s" is invalid.', $birthday));
            }
        }

        $user->setEnabled(true);

        $app['user.repository']->create($user);

        // send welcome message
        if ($email) {
            $message = sprintf("
Hi %s!

Thanks for registering to use ChatApp!

Your username is: %s
Your password is: %s

With ChatApp you can chat with your friends and find new friends. Share special pictures and status updates with the Moment updates! Find new friends to talk with by accessing the Radar feature.

ChatApp is the new way to chat!

Enjoy!

Your ChatApp Team
http://chatapp.mobi

            ", $user->getName() ?: $user->getUsername(), $user->getUsername(), $user->getPassword());

            Mailer::send($email, 'ChatApp Welcome', $message);
        }
        if ($phone_number) {
            // TODO: send SMS
        }

        if (isset($user) && $user instanceof User) {
            return $app->json(array(
                'success' => true,
                'data' => array(
                    'token' => $user->getToken(),
                ),
            ));
        }

        throw new AccessDeniedException('You must provide ether an email address or a phone number to register.');
    }

    /**
     * Resend registration code.
     *
     * @param string $email        The User email
     * @param string $phone_number The User phone number
     *
     * @Route("/api/auth/resend-code")
     * @method("POST")
     */
    public function resendCodeAction(Application $app, Request $request)
    {
        // using email
        if ($email = $request->get('email')) {
            if (!$user = $app['user.repository']->findUserByEmail($email)) {
                throw new \Exception(sprintf('Email "%s" does not exist.', $email));
            }

            $user->setCaptcha($app['user.repository']->generateCaptcha());

            // only effective for 1 day
            $now = new \DateTime();
            $now->add(new \DateInterval('P1D'));
            $user->setCaptchaExpireAt($now);
            $user->setCaptchaExpired(false);

            $app['user.repository']->update($user);

            // send email
            $message = sprintf("
Hi '%s'!

Thanks for registering to use ChatApp! Before you have access to all our awesome features, please confirm your registration code so you can start chatting away: %s.

With ChatApp you can chat with your friends and find new friends. Share special pictures and status updates with the Moment updates! Find new friends to talk with by accessing the Radar feature.

ChatApp is the new way to chat!

Enjoy!

Your ChatApp Team
http://chatapp.mobi

            ", $user->getUsername(), $user->getCaptcha());

            Mailer::send($email, 'ChatApp Registration Code', $message);
        }
        // using phone number
        else if ($phone_number = $request->get('phone_number')) {
            if (!$user = $app['user.repository']->findUserByPhoneNumber($phone_number)) {
                throw new \Exception(sprintf('Phone number "%s" does not exist.', $phone_number));
            }

            $user->setCaptcha($app['user.repository']->generateCaptcha());

            // only effective for 1 day
            $now = new \DateTime();
            $now->add(new \DateInterval('P1D'));
            $user->setCaptchaExpireAt($now);
            $user->setCaptchaExpired(false);

            $app['user.repository']->update($user);

            // TODO: send SMS
        }

        if (isset($user) && $user instanceof User) {
            return $app->json(array(
                'success' => true,
            ));
        }

        throw new AccessDeniedException('You must provide ether an email address or a phone number to resend registration code.');
    }

    /**
     * Verify the registration captcha code.
     *
     * @param string $captcha A captcha string
     *
     * @return string The user token
     *
     * @Route("/api/auth/verify")
     * @method("POST")
     */
    public function verifyAction(Application $app, Request $request)
    {
        if (!$captcha = $request->get('captcha')) {
            throw new TokenNotFoundException('No captcha found.');
        }

        if (!$user = $app['user.repository']->findUserByCaptcha($captcha)) {
            throw new UsernameNotFoundException(sprintf('Captcha "%s" does not exist.', $captcha));
        }

        if (!$user->isCaptchaNonExpired()) {
            // expire the user captcha
            $user->setCaptchaExpired(true);

            $app['user.repository']->update($user);

            throw new CredentialsExpiredException(sprintf('Captcha "%s" has expired.', $captcha));
        }

        // reset user captcha
        $user->setCaptcha(null);
        $user->setCaptchaExpireAt(null);
        // activate user
        $user->setEnabled(true);

        $app['user.repository']->update($user);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'token' => $user->getToken(),
            ),
        ));
    }

    /**
     * Recover password via email or phone number.
     *
     * @param string $username     The User username (optional)
     * @param string $email        The User email (optional)
     * @param string $phone_number The User phone number (optional)
     *
     * @Route("/api/auth/recover")
     * @method("POST")
     */
    public function recoverAction(Application $app, Request $request)
    {
        // using username
        if ($username = $request->get('username')) {
            if (!$user = $app['user.repository']->findUserByUsername($username)) {
                throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
            }
        }
        // using email
        elseif ($email = $request->get('email')) {
            if (!$user = $app['user.repository']->findUserByEmail($email)) {
                throw new UsernameNotFoundException(sprintf('Email "%s" does not exist.', $email));
            }
        }
        // using phone number
        else if ($phone_number = $request->get('phone_number')) {
            if (!$user = $app['user.repository']->findUserByPhoneNumber($phone_number)) {
                throw new UsernameNotFoundException(sprintf('Phone "%s" does not exist.', $phone_number));
            }
        }
        // throw error
        else {
            throw new Exception('Can only recover from username, email or phone number.');
        }

        if ($user->getEmail()) {
            // send email
            $message = sprintf("
'%s',

Your password is: %s.

Thanks,

ChatApp Team
http://chatapp.mobi

            ", $user->getUsername(), $user->getPassword());

            Mailer::send($user->getEmail(), 'ChatApp Password', $message);
        }

        if ($user->getPhoneNumber()) {
            // TODO: send SMS
        }

        return $app->json(array(
            'success' => true,
        ));
    }
}
