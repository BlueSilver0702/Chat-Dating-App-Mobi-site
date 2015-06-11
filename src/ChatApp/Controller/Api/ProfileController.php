<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class ProfileController implements ControllerProviderInterface
{
    /**
     * List of properties allowed to get/set.
     *
     * @var array
     */
    protected $allowedProperties = array(
        'username',
        'email',
        'phoneNumber',
        'password',
        'name',
        'gender',
        'birthday',
        'ethnicity',
        'region',
        'interest',
        'aboutme',
        'greeting',
        'latitude',
        'longitude',
        'endroidGcmId',
        'iosDeviceId',
        'photo',
        'background',
    );

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->post('/delete', 'ChatApp\Controller\Api\ProfileController::deleteAction')->before($app['apiSecurityCheck']);
        $index->post('/report', 'ChatApp\Controller\Api\ProfileController::reportAction')->before($app['apiSecurityCheck']);
        $index->get('/get_info/{username}', 'ChatApp\Controller\Api\ProfileController::getInfoAction')->before($app['apiSecurityCheck']);
        $index->match('/get_infos', 'ChatApp\Controller\Api\ProfileController::getInfosAction')->before($app['apiSecurityCheck']);
        $index->match('/get_property', 'ChatApp\Controller\Api\ProfileController::getPropertyAction')->before($app['apiSecurityCheck']);
        $index->match('/set_property', 'ChatApp\Controller\Api\ProfileController::setPropertyAction')->before($app['apiSecurityCheck']);
        $index->match('/update_password', 'ChatApp\Controller\Api\ProfileController::updatePasswordAction')->before($app['apiSecurityCheck']);
        $index->match('/upload_photo', 'ChatApp\Controller\Api\ProfileController::uploadPhotoAction')->before($app['apiSecurityCheck']);
        $index->match('/reset_photo', 'ChatApp\Controller\Api\ProfileController::resetPhotoAction')->before($app['apiSecurityCheck']);

        return $index;
    }

    /**
     * Deletes user and all related data.
     *
     * @param string $username The User username, phone number, or email
     *
     * @Route("/api/profile/delete")
     * @method("POST")
     */
    public function deleteAction(Application $app, Request $request)
    {
        $user = $app['user'];

        // allow admin to delete any profile
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            if ($username = $request->get('username')) {
                if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
                    throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
                }
            }
        }

        // unlink all images
        foreach ($user->getMoments() as $moment) {
            foreach ((array) $moment->getImages() as $filename) {
                $filename = $app['media_dir'].$filename;

                if ($filename && is_file($filename) && file_exists($filename)) {
                    unlink($filename);
                }
            }
        }

        // unlink profile photo
        if ($user->getPhoto() && file_exists($app['media_dir'].$user->getPhoto())) {
            unlink($app['media_dir'].$user->getPhoto());
        }

        // unlink profile background photo
        if ($user->getBackground() && file_exists($app['media_dir'].$user->getBackground())) {
            unlink($app['media_dir'].$user->getBackground());
        }

        $app['user.repository']->delete($user);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Reports user.
     *
     * @param string $username The User username, phone number, or email
     *
     * @Route("/api/profile/report/{username}")
     * @method("POST")
     */
    public function reportAction(Application $app, Request $request)
    {
        if (!$username = $request->get('username')) {
            throw new \Exception('Invalid username.');
        }
        if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        $user->setReported(true);

        $app['user.repository']->update($user);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Get infos
     *
     * @param array usernames List of usernames to retrieve profile info
     *
     * @Route("/api/profile/get_infos")
     * @method("POST")
     */
    public function getInfosAction(Application $app, Request $request)
    {
        $usernames = (array) $request->get('usernames');

        $properties = $app['user.repository']->findUsersByUsername($usernames);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'properties' => $properties,
            ),
        ));
    }

    /**
     * Gets all user property and related data.
     *
     * @param string $username The User username, phone number, or email
     *
     * @return array
     *
     * @Route("/api/profile/get_info/{username}")
     * @method({"GET", "POST"})
     */
    public function getInfoAction(Application $app, Request $request)
    {
        $username = $request->get('username', $app['user']->getUsername());

        if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        // get all properties
        $properties = array();
        foreach ($this->allowedProperties as $property) {
            $properties[$property] = $user->__get($property);
        }

        // get all contacts
        $request = new Request(array(
            'type' => 'friends',
            'page' => -1,
            'limit' => -1
        ));
        $contacts = $app['api.contacts.controller']->searchAction($app, $request);
        $contacts = json_decode($contacts->getContent(), true);
        $contacts = $contacts['data']['contacts']['result'];

        // get all moments
        $request = new Request(array(
            'username' => $user->getUsername(),
            'page' => -1,
            'limit' => -1
        ));
        $moments = $app['api.moments.controller']->searchAction($app, $request);
        $moments = json_decode($moments->getContent(), true);
        $moments = $moments['data']['moments']['result'];

        // get all open messages
        $chats = $app['chat_participant.repository']->findOpenChats($user);

        // chat history
        foreach ($chats['result'] as $key => $value) {
            if (!$chat = $app['chat.repository']->find($value['chat_id'])) {
                continue;
            }

            // get recent messages
            $request = new Request(array(
                'chat_id' => $value['chat_id'],
            ));
            $messages = $app['api.messages.controller']->historyAction($app, $request);
            $messages = json_decode($messages->getContent(), true);
            $messages = $messages['data']['messages']['result'];

            $chats['result'][$key]['history'] = $messages;
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'properties' => $properties,
                'contacts' => $contacts,
                'moments' => $moments,
                'chats' => $chats,
            ),
        ));
    }

    /**
     * Gets user property value.
     *
     * @param string $property The property name
     *
     * @return mix Property value
     *
     * @Route("/api/profile/get_property")
     * @method({"GET", "POST"})
     */
    public function getPropertyAction(Application $app, Request $request)
    {
        $property = $request->get('property');

        if (!in_array($property, $this->allowedProperties)) {
            throw new \Exception('Invalid property.');
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'property' => $app['user']->__get($property),
            ),
        ));
    }

    /**
     * Sets the user property with a value.
     *
     * @param string $property The property name
     * @param string $value    The new value
     *
     * @return mix Property value
     *
     * @Route("/api/profile/set_property")
     * @method({"GET", "POST"})
     */
    public function setPropertyAction(Application $app, Request $request)
    {
        $property = $request->get('property');
        $value = $request->get('value');

        if (!in_array($property, $this->allowedProperties)) {
            throw new \Exception('Invalid property.');
        }

        switch ($property) {
            case 'birthday':
                $birthday = sprintf('%04d-%02d-%02d', $property['year'], $property['month'], $property['day']);
                if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $birthday)) {
                    $birthday = new \DateTime($birthday);
                    if ($birthday->diff(new \DateTime())->format('%y') < 13) {
                        throw new \Exception('User must be older than 13 year old.');
                    }
                } else {
                    throw new \Exception(sprintf('Birthday "%s" is invalid.', $birthday));
                }
                break;
        }


        $app['user']->__set($property, $value);
        $app['user.repository']->update($app['user']);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'property' => $app['user']->__get($property),
            ),
        ));
    }

    /**
     * Updated user password.
     *
     * @param string $curent The current password
     * @param string $new    The new password
     *
     * @Route("/api/profile/update_password")
     * @method({"GET", "POST"})
     */
    public function updatePasswordAction(Application $app, Request $request)
    {
        $current = $request->get('current');
        $new = $request->get('new');

        if ($app['user'] != $current) {
            throw new \Exception('Invalid Password.');
        }

        $app['user']->setPassword($new);
        $app['user.repository']->update($app['user']);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Uploads a user photo.
     *
     * @param string  $photo         The photo filename
     * @param Boolean $is_background Whether or not to set background photo
     *
     * @return string The saved photo filename
     *
     * @Route("/api/profile/upload_photo")
     * @method({"GET", "POST"})
     */
    public function uploadPhotoAction(Application $app, Request $request)
    {
        $filename = $request->get('photo');
        $isBackground = $request->get('is_background');

        if (!$filename || !file_exists($app['media_dir'].$filename)) {
            throw new \Exception('Missing file.');
        }

        if ($isBackground) {
            $app['user']->setBackground($filename);
        } else {
            $app['user']->setPhoto($filename);
        }
        $app['user.repository']->update($app['user']);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'image' => $filename,
            ),
        ));
    }

    /**
     * Resets the user photo.
     *
     * @param string  $username      The User username, phone number, or email
     * @param Boolean $is_background Whether or not to set background photo
     *
     * @Route("/api/profile/reset_photo")
     * @method("POST")
     */
    public function resetPhotoAction(Application $app, Request $request)
    {
        $user = $app['user'];

        // allow admin to delete any profile
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            if ($username = $request->get('username')) {
                if (!$user = $app['user.repository']->findUserByUsernameOrPhoneNumberOrEmail($username)) {
                    throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
                }
            }
        }

        $isBackground = $request->get('is_background');

        $filename = $isBackground
            ? $app['media_dir'].$user->getBackground()
            : $app['media_dir'].$user->getPhoto()
        ;

        if ($filename && is_file($filename) && file_exists($filename)) {
            unlink($filename);
        }

        if ($isBackground) {
            $user->setBackground(null);
        } else {
            $user->setPhoto(null);
        }
        $app['user.repository']->update($user);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'image' => $filename,
            ),
        ));
    }
}
