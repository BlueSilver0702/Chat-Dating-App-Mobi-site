<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use ChatApp\Model\Entity\Moment;
use ChatApp\Model\Entity\MomentComment;
use ChatApp\Util\PushNotification;

class MomentsController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->match('/search', 'ChatApp\Controller\Api\MomentsController::searchAction')->before($app['apiSecurityCheck']);
        $index->match('/gsearch', 'ChatApp\Controller\Api\MomentsController::gSearchAction');
        $index->match('/add', 'ChatApp\Controller\Api\MomentsController::addAction')->before($app['apiSecurityCheck']);
        $index->match('/delete/{moment_id}', 'ChatApp\Controller\Api\MomentsController::deleteAction')->before($app['apiSecurityCheck']);
        $index->match('/add_comment', 'ChatApp\Controller\Api\MomentsController::addCommentAction')->before($app['apiSecurityCheck']);
        $index->match('/delete_comment/{moment_id}/{moment_comment_id}', 'ChatApp\Controller\Api\MomentsController::deleteCommentAction')->before($app['apiSecurityCheck']);
        $index->match('/like/{moment_id}', 'ChatApp\Controller\Api\MomentsController::likeAction')->before($app['apiSecurityCheck']);
        $index->match('/unlike/{moment_id}', 'ChatApp\Controller\Api\MomentsController::unlikeAction')->before($app['apiSecurityCheck']);
        $index->match('/block/{moment_id}', 'ChatApp\Controller\Api\MomentsController::blockAction')->before($app['apiSecurityCheck']);
        $index->match('/unblock/{moment_id}', 'ChatApp\Controller\Api\MomentsController::unblockAction')->before($app['apiSecurityCheck']);
        $index->match('/popular_moments', 'ChatApp\Controller\Api\MomentsController::poMomentsAction')->before($app['apiSecurityCheck']);
        $index->match('/popular_members', 'ChatApp\Controller\Api\MomentsController::poMembersAction')->before($app['apiSecurityCheck']);

        return $index;
    }

    /**
     * Returns list of moments.
     *
     * @param array   $equals        List of user fields with values (optional)
     * @param string  $type          Search type by.. (all, user or friends)
     * @param string  $sort          Search sort by.. (recent, distance)
     * @param integer $distance      Maximum distance (optional)
     * @param string  $username      User's username (optional)
     * @param mix     $include_block Whether or not to include block moments (optional)
     *                               true  = Include
     *                               false = Not_include
     *                               -1    = Only blocked moments
     * @param integer $page          Pagination offset (optional)
     * @param integer $limit         Maximum number of results to retrieve (optional)
     *
     * @return array List of moments
     *
     * @Route("/api/moments/search")
     * @method({"GET", "POST"})
     */
    public function searchAction(Application $app, Request $request)
    {
        $type = $request->get('type', 'user');
        $sort = $request->get('sort');
        $distance = $request->get('distance');
        $gender = $request->get('gender');
        $username = $request->get('username', $app['user']->getUsername());
        $includeBlock = $request->get('include_block', false);
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
        
        if ($gender == 1) {
            $gender = 'M';
        } else if ($gender == 2) {
            $gender = 'F';
        } else {
            $gender = null;
        }
        if ($distance == 1) {
            $distance = 10;
        } else if ($distance == 2) {
            $distance = 100;
        } else if ($distance == 3) {
            $distance = 1000;
        } else if ($distance == 4) {
            $distance = 2500;
        } else {
            $distance = null;
        }

        // get all blocked moments ids
        $blockedMoments = array();
        if ($includeBlock !== true) {
            foreach ($app['user']->getBlockedMoments() as $moment) {
                $blockedMoments[] = $moment->getId();
            }
        }

        $moments = array();

        switch ($type) {
            case 'friends':
                $moments = $app['moment.repository']->search(array(
                    'equals' => $equals,
                    'friends_of' => $app['user'],
                    'latitude' => $app['user']->getLatitude(),
                    'longitude' => $app['user']->getLongitude(),
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_moments' => $blockedMoments,
                    'block_only' => $includeBlock == -1,
                ), $sort, $page, $limit);

                break;

            case 'all':
                $moments = $app['moment.repository']->search(array(
                    'equals' => $equals,
                    'latitude' => $app['user']->getLatitude(),
                    'longitude' => $app['user']->getLongitude(),
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_moments' => $blockedMoments,
                    'block_only' => $includeBlock == -1,
                ), $sort, $page, $limit);

                break;

            case 'user':
            default:
                $moments = $app['moment.repository']->search(array(
                    'equals' => $equals,
                    'username' => $username,
                    'block_moments' => $blockedMoments,
                    'block_only' => $includeBlock == -1,
                ), null, $page, $limit);
        }

        $result = array();

        // temp array for limit moments per user to 3
        $ra_tmp_result = array();

        if (isset($moments['result'])) {
            $ids = array();

            foreach ($moments['result'] as $moment) {
                // set value user
                $moment = is_array($moment) ? $moment[0] : $moment;

                // get comments
                $comments = array();
                foreach ($moment->getComments() as $comment) {
                    if ($comment->getUser()) {
                        $comments[] = array(
                            'id' => $comment->getId(),
                            'user' => $comment->getUser()->getUsername(),
                            'photo' => $comment->getUser()->getPhoto(),
                            'comment' => $comment->getComment(),
                            'date_created' => $comment->getDateCreated(),
                        );
                    }
                }
                // reverse: new to old
                $comments = array_reverse($comments);

                // get likes
                $likes = array();
                foreach ($moment->getLikes() as $user) {
                    $likes[] = array(
                        'id' => $user->getId(),
                        'user' => $user->getUsername(),
                        'photo' => $user->getPhoto(),
                    );
                }

                // get mentions
                $mentions = array();
                foreach ($moment->getMentions() as $user) {
                    $mentions[] = array(
                        'id' => $user->getId(),
                        'user' => $user->getUsername(),
                        'photo' => $user->getPhoto(),
                    );
                }

                // get images sizes
                $sizes = array();
                foreach ($moment->getImages() as $image) {
                    list($width, $height) = @getimagesize($app['media_dir'].$image);

                    $sizes[] = array(
                        'width' => $width,
                        'height' => $height,
                    );
                }

                $userId = intval($moment->getUser()->getId());
                if ($userId != '') {
                    if (array_key_exists($userId, $ra_tmp_result)) {
                        $ra_tmp_result[$userId] ++;
                        if ($ra_tmp_result[$userId] > 3) {
                            continue;
                        }
                    } else {
                        $ra_tmp_result[$userId] = 1;
                        //array_push($ra_tmp_result, array($userId=>2));
                    }
                }

                $result[$moment->getId()] = array(
                    'id' => $moment->getId(),
                    'username' => $moment->getUser()->getUsername(),
                    'photo' => $moment->getUser()->getPhoto(),
                    'cover_flag' => (bool) $moment->getCoverFlag(),
                    'name' => $moment->getName(),
                    'images' => $moment->getImages(),
                    'images_sizes' => $sizes,
                    'location' => $moment->getLocation(),
                    'latitude' => $moment->getLatitude(),
                    'longitude' => $moment->getLongitude(),
                    'unread' => $moment->getUnread(),
                    'comments' => $comments,
                    'likes' => $likes,
                    'mention' => $mentions,
                    'date_created' => $moment->getDateCreated(),
                );

                // reset unread moments when profile view his moments
                if ($username == $app['user']->getUsername() && $moment->getUnread() > 0) {
                    $moment->setUnread(0);

                    $app['moment.repository']->update($moment);
                }
            }
        }

        $moments['result'] = array_values($result);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'moments' => $moments,
            ),
        ));
    }

/**
     * Returns list of moments.
     * @param string  $type          Search type by.. (all, user or friends)
     * @param string  $sort          Search sort by.. (recent, distance)
     * @param integer $distance      Maximum distance (optional)
     * @param mix     $include_block Whether or not to include block moments (optional)
     *                               true  = Include
     *                               false = Not_include
     *                               -1    = Only blocked moments
     * @param integer $page          Pagination offset (optional)
     * @param integer $limit         Maximum number of results to retrieve (optional)
     *
     * @return array List of moments
     *
     * @Route("/api/moments/gsearch")
     * @method({"GET", "POST"})
     */
    public function gSearchAction(Application $app, Request $request)
    {
        $type = $request->get('type', 'all');
        $sort = $request->get('sort');
        $distance = $request->get('distance');
        $gender = $request->get('gender');
        $includeBlock = $request->get('include_block', false);
        $page = $request->get('page', 1);
        $limit = $request->get('limit', $app['limit']);

        $moments = array();

        switch ($type) {
            case 'friends':
                $moments = $app['moment.repository']->search(array(
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_only' => $includeBlock == -1,
                ), $sort, $page, $limit);

                break;

            case 'all':
                $moments = $app['moment.repository']->search(array(
                    'distance' => $distance,
                    'gender' => $gender,
                    'block_only' => $includeBlock == -1,
                ), $sort, $page, $limit);

                break;

            case 'user':
            default:
                $moments = $app['moment.repository']->search(array(
                    'block_only' => $includeBlock == -1,
                ), null, $page, $limit);
        }

        $result = array();

        // temp array for limit moments per user to 3
        $ra_tmp_result = array();

        if (isset($moments['result'])) {
            $ids = array();

            foreach ($moments['result'] as $moment) {
                // set value user
                $moment = is_array($moment) ? $moment[0] : $moment;

                // get comments
                $comments = array();
                foreach ($moment->getComments() as $comment) {
                    if ($comment->getUser()) {
                        $comments[] = array(
                            'id' => $comment->getId(),
                            'user' => $comment->getUser()->getUsername(),
                            'photo' => $comment->getUser()->getPhoto(),
                            'comment' => $comment->getComment(),
                            'date_created' => $comment->getDateCreated(),
                        );
                    }
                }
                // reverse: new to old
                $comments = array_reverse($comments);

                // get likes
                $likes = array();
                foreach ($moment->getLikes() as $user) {
                    $likes[] = array(
                        'id' => $user->getId(),
                        'user' => $user->getUsername(),
                        'photo' => $user->getPhoto(),
                    );
                }

                // get mentions
                $mentions = array();
                foreach ($moment->getMentions() as $user) {
                    $mentions[] = array(
                        'id' => $user->getId(),
                        'user' => $user->getUsername(),
                        'photo' => $user->getPhoto(),
                    );
                }

                // get images sizes
                $sizes = array();
                foreach ($moment->getImages() as $image) {
                    list($width, $height) = @getimagesize($app['media_dir'].$image);

                    $sizes[] = array(
                        'width' => $width,
                        'height' => $height,
                    );
                }

                $userId = intval($moment->getUser()->getId());
                if ($userId != '') {
                    if (array_key_exists($userId, $ra_tmp_result)) {
                        $ra_tmp_result[$userId] ++;
                        if ($ra_tmp_result[$userId] > 3) {
                            continue;
                        }
                    } else {
                        $ra_tmp_result[$userId] = 1;
                        //array_push($ra_tmp_result, array($userId=>2));
                    }
                }

                $result[$moment->getId()] = array(
                    'id' => $moment->getId(),
                    'username' => $moment->getUser()->getUsername(),
                    'photo' => $moment->getUser()->getPhoto(),
                    'cover_flag' => (bool) $moment->getCoverFlag(),
                    'name' => $moment->getName(),
                    'images' => $moment->getImages(),
                    'images_sizes' => $sizes,
                    'location' => $moment->getLocation(),
                    'latitude' => $moment->getLatitude(),
                    'longitude' => $moment->getLongitude(),
                    'unread' => $moment->getUnread(),
                    'comments' => $comments,
                    'likes' => $likes,
                    'mention' => $mentions,
                    'date_created' => $moment->getDateCreated(),
                );
            }
        }

        $moments['result'] = array_values($result);

        return $app->json(array(
            'success' => true,
            'data' => array(
                'moments' => $moments,
            ),
        ));
    }

    /**
     * Adds a moment.
     *
     * @param string  $name      Moment's name
     * @param array   $photos    List of filename's
     * @param array   $mention   List of usernames (@...)
     * @param string  $location  The location the moment(s) was taken
     * @param string  $latitude  The latitude the moment(s) was taken
     * @param string  $longitude The longitude the moment(s) was taken
     * @param Boolean $is_cover  Whether or not to flag moment as "cover page"
     *
     * @return Moment Saved moments
     *
     * @Route("/api/moments/add")
     * @method({"GET", "POST"})
     */
    public function addAction(Application $app, Request $request)
    {
        $name = $request->get('name');
        $mentions = (array) $request->get('mention');
        $location = $request->get('location');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');
        $isCover = $request->get('is_cover');

        // check number of moments created today
        $now = new \DateTime();
        if ($app['moment.repository']->search(array(
            'count' => true,
            'username' => $app['user']->getUsername(),
            'from_date' => $now->format('Y-m-d'),
            'to_date' => $now->add(new \DateInterval('P1D'))->format('Y-m-d'),
        )) === 10) {
            throw new \Exception('You have already posted the maximum amount of moments for today.');
        }

        $moment = new Moment();
        $moment->setUser($app['user']);
        $moment->setName($name);
        $moment->setLocation($location);
        $moment->setLatitude($latitude);
        $moment->setLongitude($longitude);
        $moment->setCoverFlag(false);

        if ($isCover) {
            $moment->setCoverFlag(true);

            // "uncover" previous "cover"
            $moments = $app['moment.repository']->search(array(
                'all' => true,
                'username' => $app['user']->getUsername(),
                'cover_flag' => true
            ));
            foreach ($moments as $m) {
                $m->setCoverFlag(false);

                $app['moment.repository']->update($m);
            }
        }

        $photos = (array) $request->get('photos');
        foreach ($photos as $key => $photo) {
            if (!$photo || !file_exists($app['media_dir'].$photo)) {
                unset($photos[$key]);
            }
        }
        if (count($photos) === 0) {
            throw new \Exception('Moment must include at least 1 photo.');
        }
        $moment->setImages($photos);

        foreach ($mentions as $username) {
            if ($user = $app['user.repository']->findUserByUsername($username)) {
                $moment->getMentions()->add($user);
            }
        }

        $app['moment.repository']->create($moment);

        // get comments
        $comments = array();
        foreach ($moment->getComments() as $comment) {
            $comments[] = array(
                'id' => $comment->getId(),
                'user' => $comment->getUser()->getUsername(),
                'comment' => $comment->getComment(),
                'date_created' => $comment->getDateCreated(),
            );
        }

        // get mention
        $mentions = array();
        foreach ($moment->getMentions() as $user) {
            $mentions[] = array(
                'id' => $user->getId(),
                'user' => $user->getUsername(),
            );
        }

        // get likes
        $likes = array();
        foreach ($moment->getLikes() as $user) {
            $likes[] = array(
                'id' => $user->getId(),
                'user' => $user->getUsername(),
            );
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'moment' => array(
                    'id' => $moment->getId(),
                    'name' => $moment->getName(),
                    'images' => $moment->getImages(),
                    'location' => $moment->getLocation(),
                    'latitude' => $moment->getLatitude(),
                    'longitude' => $moment->getLongitude(),
                    'comments' => $comments,
                    'likes' => $likes,
                    'mention' => $mentions,
                    'date_created' => $moment->getDateCreated(),
                ),
            ),
        ));
    }

    /**
     * Deletes a moment.
     *
     * @param integer $moment_id Moment's id
     *
     * @Route("/api/moments/delete/{moment_id}")
     * @method("GET")
     */
    public function deleteAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');

        // allow admin to delete any moment
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $moment = $app['moment.repository']->find($momentId);
        } else {
            $moment = $app['moment.repository']->findOneBy(array(
                'user' => $app['user'],
                'id' => $momentId,
            ));
        }

        if (!$moment) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        foreach ((array) $moment->getImages() as $filename) {
            $filename = $app['media_dir'].$filename;

            if ($filename && is_file($filename) && file_exists($filename)) {
                unlink($filename);
            }
        }

        $app['moment.repository']->delete($moment);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Adds a moment comment.
     *
     * @param integer $moment_id Moment's id
     * @param string  $username  User's username (owner of moment)
     * @param string  $comment   Comment
     *
     * @Route("/api/moments/add_commentt")
     * @method({"GET", "POST"})
     */
    public function addCommentAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');
        $username = $request->get('username');
        $comment = $request->get('comment');

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }

        if (!$moment = $app['moment.repository']->findOneBy(array(
            'user' => $user,
            'id' => $momentId,
        ))) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        $momentComment = new MomentComment();
        $momentComment->setMoment($moment);
        $momentComment->setUser($app['user']);
        $momentComment->setComment($comment);

        $app['moment_comment.repository']->create($momentComment);

        // increase the unread count
        $moment->setUnread($moment->getUnread() + 1);

        $app['moment.repository']->update($moment);

        // push notification
        PushNotification::send($app, $user, array(
            'parameters' => $momentId,
            'from_username' => $app['user']->getUsername(),
            'title' => 'MOMENT_COMMENT',
            'message' => $comment,
        ));

        return $app->json(array(
            'success' => true,
            'comment' => array(
                'id' => $momentComment->getId(),
                'user' => $momentComment->getUser()->getUsername(),
                'photo' => $momentComment->getUser()->getPhoto(),
                'comment' => $momentComment->getComment(),
                'date_created' => $momentComment->getDateCreated(),
            ),
        ));
    }

    /**
     * Deletes a moment comment.
     *
     * @param integer $moment_id         Moment's id
     * @param integer $momnet_comemnt_id Moment comment's id
     *
     * @Route("/api/moments/delete_comment/{moment_id}/{moment_comment_id}")
     * @method("GET")
     */
    public function deleteCommentAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');
        $momentCommentId = $request->get('moment_comment_id');

        // allow admin to delete any moment comment
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $moment = $app['moment.repository']->find($momentId);
        } else {
            $moment = $app['moment.repository']->findOneBy(array(
                'user' => $app['user'],
                'id' => $momentId,
            ));
        }

        if (!$moment) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        if (!$momentComment = $app['moment_comment.repository']->find($momentCommentId)) {
            throw new \Exception(sprintf('Moment comment "%d" does not exist.', $momentCommentId));
        }

        if ($momentComment->getMoment()->getId() != $moment->getId()) {
            throw new \Exception('Moment comment does not belong to the moment requested.');
        }

        $app['moment_comment.repository']->delete($momentComment);

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Mark moment as like.
     *
     * @param integer $moment_id Moment's id
     *
     * @Route("/api/moments/like/{moment_id}")
     * @method("GET")
     */
    public function likeAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');

        if (!$moment = $app['moment.repository']->find($momentId)) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        if (!$moment->getLikes()->contains($app['user'])) {
            $moment->getLikes()->add($app['user']);
        }

        // increase the unread count
        $moment->setUnread($moment->getUnread() + 1);

        $app['moment.repository']->update($moment);

        // push notification
        PushNotification::send($app, $moment->getUser(), array(
            'parameters' => $momentId,
            'from_username' => $app['user']->getUsername(),
            'title' => 'MOMENT_LIKE',
            'message' => 'like',
        ));

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Unmark moment as like.
     *
     * @param integer $moment_id Moment's id
     *
     * @Route("/api/moments/unlike/{moment_id}")
     * @method("GET")
     */
    public function unlikeAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');

        if (!$moment = $app['moment.repository']->find($momentId)) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        if ($moment->getLikes()->contains($app['user'])) {
            $moment->getLikes()->removeElement($app['user']);

            $app['moment.repository']->update($moment);
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Mark moment as block.
     *
     * @param integer $moment_id Moment's id
     *
     * @Route("/api/moments/block/{moment_id}")
     * @method("GET")
     */
    public function blockAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');

        if (!$moment = $app['moment.repository']->find($momentId)) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        if (!$app['user']->getBlockedMoments()->contains($moment)) {
            $app['user']->getBlockedMoments()->add($moment);

            $app['user.repository']->update($app['user']);
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Unmark moment as block.
     *
     * @param integer $moment_id Moment's id
     *
     * @Route("/api/moments/unblock/{moment_id}")
     * @method("GET")
     */
    public function unblockAction(Application $app, Request $request)
    {
        $momentId = $request->get('moment_id');

        if (!$moment = $app['moment.repository']->find($momentId)) {
            throw new \Exception(sprintf('Moment "%d" does not exist.', $momentId));
        }

        if ($app['user']->getBlockedMoments()->contains($moment)) {
            $app['user']->getBlockedMoments()->removeElement($moment);

            $app['user.repository']->update($app['user']);
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * get popular moments id.
     *
     * @Route("/api/moments/popular_moments")
     * @method("GET")
     */
    public function poMomentsAction(Application $app, Request $request)
    {
        $limit = $request->get('limit');

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * get popular members id.
     *
     * @Route("/api/moments/popular_members")
     * @method("GET")
     */
    public function poMembersAction(Application $app, Request $request)
    {
        $limit = $request->get('limit');


        return $app->json(array(
            'success' => true,
        ));
    }
}
