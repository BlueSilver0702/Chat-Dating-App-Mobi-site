<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use ChatApp\Model\Entity\Friend;

class ContactsController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->match('/search', 'ChatApp\Controller\Api\ContactsController::searchAction')->before($app['apiSecurityCheck']);
        $index->match('/add/{username}', 'ChatApp\Controller\Api\ContactsController::addAction')->before($app['apiSecurityCheck']);
        $index->match('/delete/{username}', 'ChatApp\Controller\Api\ContactsController::deleteAction')->before($app['apiSecurityCheck']);
        $index->match('/favorite/{username}', 'ChatApp\Controller\Api\ContactsController::favoriteAction')->before($app['apiSecurityCheck']);
        $index->match('/unfavorite/{username}', 'ChatApp\Controller\Api\ContactsController::unfavoriteAction')->before($app['apiSecurityCheck']);
        $index->match('/block/{username}', 'ChatApp\Controller\Api\ContactsController::blockAction')->before($app['apiSecurityCheck']);
        $index->match('/unblock/{username}', 'ChatApp\Controller\Api\ContactsController::unblockAction')->before($app['apiSecurityCheck']);
        $index->match('/alias/{username}', 'ChatApp\Controller\Api\ContactsController::aliasAction')->before($app['apiSecurityCheck']);

        return $index;
    }

    /**
     * Searches for contacts.
     *
     * @param string  $type          Search type by.. (all, usernames, or friends)
     * @param string  $query         Search query (optional)
     * @param integer $distance      Maximum distance (optional)
     * @param array   $equals        List of user fields with values (optional)
     * @param mix     $include_block Whether or not to include block users (optional)
     *                               true  = Include
     *                               false = Not_include
     *                               -1    = Only blocked users
     * @param Boolean $only_photo    Whether or not to only return profiles with photo (optional)
     *                               true  = with
     *                               false = with or without
     * @param integer $page          Pagination offset (optional)
     * @param integer $limit         Maximum number of results to retrieve (optional)
     *
     * @return array List of contacts (contact: {username, phone, aboutme})
     *
     * @Route("/api/contacts/search")
     * @method({"GET", "POST"})
     */
    public function searchAction(Application $app, Request $request)
    {
        $type = $request->get('type', 'all');
        $query = $request->get('query');
        $distance = $request->get('distance');
        $gender = $request->get('gender');
        $includeBlock = $request->get('include_block', false);
        $onlyPhoto = $request->get('only_photo', false);
        $page = $request->get('page', 1);
        $limit = $request->get('limit', $app['limit']);

        // remove undefined properties
        $equals = (array) $request->get('equals');
        foreach ($equals as $key => $value) {
            if (!property_exists('\ChatApp\Model\Entity\User', $key)) {
                unset($equals[$key]);
            }
        }

        // change type
        if ($includeBlock != -1) $includeBlock = (boolean) $includeBlock;
        $onlyPhoto = (boolean) $onlyPhoto;
        if ($gender == 1) {
            $gender = 'M';
        } else if ($gender == 2) {
            $gender = 'F';
        }
        if ($distance == 1) {
            $distance = 10;
        } else if ($distance == 2) {
            $distance = 100;
        } else if ($distance == 3) {
            $distance = 1000;
        } else if ($distance == 4) {
            $distance = 2500;
        }

        // get all blocked users ids
        $blockedUsers = array();
        if ($includeBlock !== true) {
            foreach ($app['user']->getBlockedUsers() as $user) {
                $blockedUsers[] = $user->getId();
            }
        }

        // always remove logged-in user from results
        $blockedUsers[] = $app['user']->getId();

        $contacts = array();

        switch ($type) {
            case 'friends':
                $contacts = $app['friend.repository']->search(array(
                    'user' => $app['user'],
                    'query' => $query,
                    'username' => $query,
                    'equals' => $equals,
                    'latitude' => $app['user']->getLatitude(),
                    'longitude' => $app['user']->getLongitude(),
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_users' => $blockedUsers,
                    'block_only' => $includeBlock == -1,
                    'only_photo' => $onlyPhoto,
                ), null, $page, $limit);

                break;

            case 'usernames':
                $contacts = $app['user.repository']->search(array(
                    'username' => $query,
                    'equals' => $equals,
                    'latitude' => $app['user']->getLatitude(),
                    'longitude' => $app['user']->getLongitude(),
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_users' => $blockedUsers,
                    'block_only' => $includeBlock == -1,
                    'only_photo' => $onlyPhoto,
                ), 'username', $page, $limit);

                break;

            case 'all':
            default:
                $contacts = $app['user.repository']->search(array(
                    'query' => $query,
                    'equals' => $equals,
                    'latitude' => $app['user']->getLatitude(),
                    'longitude' => $app['user']->getLongitude(),
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_users' => $blockedUsers,
                    'block_only' => $includeBlock == -1,
                    'only_photo' => $onlyPhoto,
                ), $distance ? 'distance' : null, $page, $limit);
        }

        if (isset($contacts['result'])) {
            foreach ($contacts['result'] as $key => $value) {
                // set value user
                $user = is_array($value) ? $value[0] : $value;

                // get friend
                if ($type == 'friends') {
                    $user = $user->getFriend();
                }

                // get images sizes
               list($width, $height) = @getimagesize($app['media_dir'].$user->getPhoto());
               $sizes = array(
                    'width' => $width,
                    'height' => $height,
                );

                $contacts['result'][$key] = array(
                    'username' => $user->getUsername(),
                    'name' => $user->getName(),
                    'gender' => $user->getGender(),
                    'region' => $user->getRegion(),
                    'aboutme' => $user->getAboutme(),
                    'greeting' => $user->getGreeting(),
                    'latitude' => $user->getLatitude(),
                    'longitude' => $user->getLongitude(),
                    'distance' => null,
                    'photo' => $user->getPhoto(),
                    'photo_size' => $sizes,
                    'background' => $user->getBackground(),
                );

                // fix distance value
                if ($app['user']->getLatitude() && $app['user']->getLatitude()) {
                    $distance = null;

                    if (is_array($value) && isset($value['distance'])) {
                        $distance = $value['distance'];
                    } else {
                        $distance = $app['user.repository']->distance(
                            $app['user']->getLatitude(),
                            $app['user']->getLongitude(),
                            $user->getLatitude(),
                            $user->getLongitude(),
                            $request->cookies->get('prefer')=='kilometer'?false:true
                        );
                    }

                    if (!is_nan($distance) && is_numeric($distance)) {
                        $contacts['result'][$key]['distance'] = $distance;
                    }
                }

                // get all moments
                $request = new Request(array(
                    'username' => $user->getUsername(),
                    'page' => -1,
                    'limit' => -1
                ));

                $moments = $app['api.moments.controller']->searchAction($app, $request);
                $moments = json_decode($moments->getContent(), true);

                $contacts['result'][$key]['moments'] = $moments['data']['moments']['result'];
            }
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'type' => $type,
                'contacts' => $contacts,
            ),
        ));
    }

    /**
     * Adds a friend.
     *
     * @param string $username The friend username or email
     *
     * @Route("/api/contacts/add/{username}")
     * @method("GET")
     */
    public function addAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$friend = $app['user.repository']->findUserByUsername($username)) {
            if (!$friend = $app['user.repository']->findUserByEmail($username)) {
                throw new UsernameNotFoundException(sprintf('Friend "%s" does not exist.', $username));
            }
        }

        if ($app['friend.repository']->isBefriended($app['user'], $friend)) {
            throw new \Exception(sprintf('Friend "%s" already your friend.', $username));
        }

        $contact = new Friend();
        $contact->setUser($app['user']);
        $contact->setFriend($friend);

        $app['friend.repository']->create($contact);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Deletes a friend.
     *
     * @param string $username The friend username
     *
     * @Route("/api/contacts/delete/{username}")
     * @method("GET")
     */
    public function deleteAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$friend = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('Friend "%s" does not exist.', $username));
        }

        if (!$contact = $app['friend.repository']->isBefriended($app['user'], $friend)) {
            throw new \Exception(sprintf('Friend "%s" is not your friend.', $username));
        }

        $app['friend.repository']->delete($contact);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Mark friend as favorite.
     *
     * @param string $username The friend username
     *
     * @Route("/api/contacts/favorite/{username}")
     * @method("GET")
     */
    public function favoriteAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$friend = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('Friend "%s" does not exist.', $username));
        }

        if (!$contact = $app['friend.repository']->isBefriended($app['user'], $friend)) {
            throw new \Exception(sprintf('Friend "%s" is not your friend.', $username));
        }

        $contact->setFavorite(true);

        $app['friend.repository']->update($contact);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Unmark friend as favorite.
     *
     * @param string $username The friend username
     *
     * @Route("/api/contacts/unfavorite/{username}")
     * @method("GET")
     */
    public function unfavoriteAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$friend = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('Friend "%s" does not exist.', $username));
        }

        if (!$contact = $app['friend.repository']->isBefriended($app['user'], $friend)) {
            throw new \Exception(sprintf('Friend "%s" is not your friend.', $username));
        }

        $contact->setFavorite(false);

        $app['friend.repository']->update($contact);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Mark user as block.
     *
     * @param string $username The user username
     *
     * @Route("/api/contacts/block/{username}")
     * @method("GET")
     */
    public function blockAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }
        if ($user->getId() == $app['user']->getId()) {
            throw new \Exception('You can not block your self.');
        }

        if (!$app['user']->getBlockedUsers()->contains($user)) {
            $app['user']->getBlockedUsers()->add($user);

            $app['user.repository']->update($app['user']);
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Unmark user as block.
     *
     * @param string $username The user username
     *
     * @Route("/api/contacts/unblock/{username}")
     * @method("GET")
     */
    public function unblockAction(Application $app, Request $request)
    {
        $username = $request->get('username');

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }

        if ($app['user']->getBlockedUsers()->contains($user)) {
            $app['user']->getBlockedUsers()->removeElement($user);

            $app['user.repository']->update($app['user']);
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Gives friend an alias name.
     *
     * @param string $username The friend username
     * @param string $alias    The friend alias name
     *
     * @Route("/api/contacts/alias/{username}")
     * @method({"GET", "POST"})
     */
    public function aliasAction(Application $app, Request $request)
    {
        $username = $request->get('username');
        $alias = $request->get('alias');

        if (!$friend = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('Friend "%s" does not exist.', $username));
        }

        if (!$contact = $app['friend.repository']->isBefriended($app['user'], $friend)) {
            throw new \Exception(sprintf('Friend "%s" is not your friend.', $username));
        }

        $contact->setAlias($alias);

        $app['friend.repository']->update($contact);

        return $app->json(array(
            'success' => true,
        ));
    }
}
