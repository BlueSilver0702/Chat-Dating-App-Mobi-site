<?php

namespace ChatApp\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use ChatApp\Util\Inflector;

class AppController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->get('/home', 'ChatApp\Controller\AppController::homeAction')->bind('home');
        $index->get('/dashboard', 'ChatApp\Controller\AppController::dashboardAction')->bind('dashboard');
        $index->match('/radar', 'ChatApp\Controller\AppController::radarAction')->bind('radar');
        $index->match('/settings', 'ChatApp\Controller\AppController::settingsAction')->bind('settings');
        $index->match('/following', 'ChatApp\Controller\AppController::followingAction')->bind('following');
        $index->match('/map', 'ChatApp\Controller\AppController::mapAction')->bind('map');
        $index->match('/msg', 'ChatApp\Controller\AppController::msgAction')->bind('msg');
        $index->get('/chat', 'ChatApp\Controller\AppController::chatAction')->bind('chat');
        $index->get('/profile/{username}', 'ChatApp\Controller\AppController::profileAction')->bind('profile');

        return $index;
    }

    /**
     * Home (after login homepage).
     *
     * @Route("/home")
     * @method("GET")
     */
    public function homeAction(Application $app, Request $request)
    {
        $request = new Request(array(
                    'limit'=>4
                ));

        $moments = $app['api.moments.controller']->poMomentsAction($app, $request);

        $request = new Request(array(
                    'limit'=>4
                ));

        $members = $app['api.moments.controller']->poMembersAction($app, $request);

        return $app['twig']->render('app/home.html.twig', array(
            'login' => true,
            'po_moments' => $moments,
            'po_members' => $members
        ));
    }

     /**
     * Dashboard (view moments).
     *
     * @Route("/dashboard")
     * @method("GET")
     */
    public function dashboardAction(Application $app, Request $request)
    {
        return $app['twig']->render('app/dashboard.html.twig');
    }

    /**
     * Radar.
     *
     * @Route("/settings")
     * @method("GET")
     */
    public function radarAction(Application $app, Request $request)
    {
        return $app['twig']->render('app/radar.html.twig');
    }

    /**
     * Profile settings.
     *
     * @Route("/settings")
     * @method("GET")
     */
    public function settingsAction(Application $app, Request $request)
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $formData = $request->request->get('profile');

            // validate email
            if (!$formData['email'] || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Email address is invalid.';
            }
            else {
                $check = $app['user.repository']->findUserByEmail($formData['email']);

                // check that email not used by someone else
                if ($check && $check->getId() != $app['user']->getId()) {
                    $error = 'Email already exists.';
                }
                // valid
                else {
                    $app['user']->setEmail($formData['email']);
                }
            }

            // validate passwords
            if (!$error && $formData['password_old']) {
                // check if old password match
                if ($formData['password_old'] != $app['user']->getPassword()) {
                    $error = 'Your old password was entered incorrectly. Please enter it again.';
                }
                // confirm new password
                elseif ($formData['password_new'] != $formData['password_confirm']) {
                    $error = 'New passwords do not match.';
                }
            }

            // upload photo
            $profilePhoto = $request->files->get('profile_photo');
            if (!$error && $profilePhoto) {

                if (!$profilePhoto instanceof UploadedFile || !$profilePhoto->isValid()) {
                    $error = 'Missing file.';
                } else {
                    $fileConst = new \Symfony\Component\Validator\Constraints\File(array(
                        'maxSize'   => '5120k',
                        'mimeTypes' => array(
                            'image/bmp', 'image/gif', 'image/jpeg', 'image/png',
                            'audio/mpeg', 'audio/3gpp', 'audio/3gpp2', 'audio/mp4',
                            'video/mpeg', 'video/3gpp', 'video/3gpp2', 'video/mp4',
                        ),
                    ));

                    $errors = $app['validator']->validateValue($profilePhoto, $fileConst);
                    if (count($errors) > 0) {
                        $error = 'Invalid file.';
                    } else {
                        $filename = Inflector::getRandomString(32).'.'.$profilePhoto->guessExtension();
                        $profilePhoto->move($app['media_dir'], $filename);

                        $app['user']->setPhoto($filename);
                    }
                }
            }

            // validate bithday
            if (!$error && $formData['birthday']) {
                $birthday = sprintf('%04d-%02d-%02d', $formData['birthday']['year'], $formData['birthday']['month'], $formData['birthday']['day']);
                if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $birthday)) {
                    $birthday = new \DateTime($birthday);
                    if ($birthday->diff(new \DateTime())->format('%y') < 13) {
                        throw new \Exception('User must be older than 13 year old.');
                    }

                    $app['user']->setBirthday($birthday);
                } else {
                    $error = 'Birthday is invalid.';
                }
            }

            // set prefer kilometers or miles
            $response = new Response();
            $response->headers->setCookie(new Cookie('prefer', $formData['prefer'], time() + 86400));
            $response->send();

            if (!$error) {
                $app['user']->setName($formData['name']);
                $app['user']->setGender($formData['gender']);
                $app['user']->setEthnicity($formData['ethnicity']);
                $app['user']->setRegion($formData['region']);
                $app['user']->setInterest($formData['interest']);
                $app['user']->setAboutme($formData['aboutme']);
                $app['user']->setGreeting($formData['greeting']);

                $app['user.repository']->update($app['user']);
            }

            return $app['twig']->render('app/settings.html.twig', array(
                'error' => $error,
                'prefer' => $formData['prefer']
            ));
        }

        return $app['twig']->render('app/settings.html.twig', array(
            'error' => $error,
            'prefer' => ''
        ));
    }

    /**
     * My contacts.
     *
     * @Route("/following")
     * @method("GET")
     */
    public function followingAction(Application $app, Request $request)
    {
        return $app['twig']->render('app/following.html.twig');
    }

    /**
     * Profile.
     *
     * @Route("/profile/{username}")
     * @method("GET")
     */
    public function profileAction(Application $app, Request $request)
    {
        if (!$username = $request->get('username')) {
            throw new \Exception('Invalid username.');
        }
        if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $app['twig']->render('app/profile.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * Map.
     *
     * @Route("/map")
     * @method("GET")
     */
    public function mapAction(Application $app, Request $request)
    {
        // update user info
        if ($request->get('lat') != '') {
            $app['user']->setLatitude($request->get('lat'));
            $app['user']->setLongitude($request->get('lng'));
        }

        $contacts = array();

        $request = new Request(array(
            'type' => 'all',
            'query' => $request->get('query'),
            'distance' => $request->get('distance'),
            'gender' => $request->get('gender'),
            'lat' => $request->get('lat'),
            'lng' => $request->get('lng'),
            'only_photo' => $request->get('only_photo', true),
        ));

        $contacts = $app['api.contacts.controller']->searchAction($app, $request);
        $contacts = json_decode($contacts->getContent(), true);
        $contacts = $contacts['data']['contacts']['result'];

        return $app['twig']->render('app/map.html.twig', array(
            'contacts' => $contacts
        ));
    }

    /**
     * Message.
     *
     * @Route("/msg")
     * @method("GET")
     */
    public function msgAction(Application $app, Request $request)
    {
        $request = new Request(array(
                            'limit' => 10,
                            'site'=> true
                        ));

        $msgs = $app['api.messages.controller']->openChatsAction($app, $request);

        return $app['twig']->render('app/msg.html.twig', array(
            'msgs' => $msgs['result']
        ));
    }

    /**
     * Chat room.
     *
     * @Route("/chat")
     * @method("GET")
     */
    public function chatAction(Application $app, Request $request)
    {
        if ($request->get('user')) {
            return $app['twig']->render('app/chat.html.twig', array(
                'chats' => array(),
            ));
        } else {
            $chats = $app['api.messages.controller']->readAction($app, new Request(array(
                                                                             'chat_id' => $request->get('chat_id'),
                                                                           )));
            $chats = $app['api.messages.controller']->historyAction($app, new Request(array(
                                                                              'limit' => 10,
                                                                              'site' => true,
                                                                              'chat_id' => $request->get('chat_id'),
                                                                          )));

            return $app['twig']->render('app/chat.html.twig', array(
                'chats' => $chats['result'],
            ));
        }
    }
}
