<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use ChatApp\Model\Entity\Chat;
use ChatApp\Model\Entity\ChatMessage;
use ChatApp\Model\Entity\ChatParticipant;
use ChatApp\Util\PushNotification;

class MessagesController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->match('/open_chats', 'ChatApp\Controller\Api\MessagesController::openChatsAction')->before($app['apiSecurityCheck']);
        $index->match('/close_chat', 'ChatApp\Controller\Api\MessagesController::closeAction')->before($app['apiSecurityCheck']);
        $index->match('/history', 'ChatApp\Controller\Api\MessagesController::historyAction')->before($app['apiSecurityCheck']);
        $index->get('/typing/{command}', 'ChatApp\Controller\Api\MessagesController::typingAction')->before($app['apiSecurityCheck']);
        $index->match('/add', 'ChatApp\Controller\Api\MessagesController::addAction')->before($app['apiSecurityCheck']);
        $index->get('/read', 'ChatApp\Controller\Api\MessagesController::readAction')->before($app['apiSecurityCheck']);
        $index->get('/invite/{username}', 'ChatApp\Controller\Api\MessagesController::inviteAction')->before($app['apiSecurityCheck']);

        return $index;
    }

    /**
     * Returns all "open" chat messages users.
     *
     * @param string $username A user username
     * @param integer $page    Pagination offset (optional)
     * @param integer $limit   Maximum number of results to retrieve (optional)
     * @param boolean $is_site   Site or Mobile App (optional)
     *
     * @return array List of chats
     *
     * @Route("/api/messages/open_chats")
     * @method({"GET", "POST"})
     */
    public function openChatsAction(Application $app, Request $request)
    {
        $username = $request->get('username', $app['user']->getUsername());
        $page = $request->get('page', 1);
        $limit = $request->get('limit', $app['limit']);
        $is_site = $request->get('site', false);

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }

        $chats = $app['chat_participant.repository']->findOpenChats($user, $page, $limit);

        if ($is_site) {
            return $chats;
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'chats' => $chats,
            ),
        ));
    }

    /**
     * Adds a user to a chat.
     *
     * @param integer $chat_id  Chat's id
     * @param string  $username A user username (optional)
     *
     * @Route("/api/messages/close_chat")
     * @method({"GET", "POST"})
     */
    public function closeAction(Application $app, Request $request)
    {
        $chatId = $request->get('chat_id');
        $username = $request->get('username', $app['user']->getUsername());

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }

        if (!$chat = $app['chat.repository']->find($chatId)) {
            throw new \Exception(sprintf('Chat "%d" does not exist.', $chatId));
        }

        foreach ($chat->getParticipants() as $participant) {
            if ($participant->getUser()->getUsername() == $user->getUsername()) {
                $chat->getParticipants()->removeElement($participant);

                $app['chat_participant.repository']->delete($participant);

                $app['chat.repository']->update($chat);
            }
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Searches messages history.
     *
     * @param integer $chat_id Chat's id
     * @param integer $from_id   Filter results by id (optional)
     * @param string  $from_date Filter results by date (optional)
     * @param integer $page      Pagination offset (optional)
     * @param integer $limit     Maximum number of results to retrieve (optional)
     * @param boolean $is_site   Site or Mobile App (optional)
     *
     * @return array List of messages
     *
     * @Route("/api/messages/history")
     * @method({"GET", "POST"})
     */
    public function historyAction(Application $app, Request $request)
    {
        $chatId = $request->get('chat_id');
        $fromId = $request->get('from_id');
        $fromDate = $request->get('from_date');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', $app['limit']);
        $is_site = $request->get('site', false);

        if (!$chat = $app['chat.repository']->find($chatId)) {
            throw new \Exception(sprintf('Chat "%d" does not exist.', $chatId));
        }

        $filters = array(
            'chat' => $chat
        );

        if ($fromId) $filters['from_id'] = $fromId;
        if ($fromDate) $filters['from_date'] = $fromDate;

        $messages = $app['chat_message.repository']->search($filters, null, $page, $limit);

        if (isset($messages['result'])) {
            foreach ($messages['result'] as $key => $value) {
                $to = array();
                foreach ($chat->getParticipants() as $participant) {
                    if ($participant->getUser()->getUsername() != $value->getUser()->getUsername()) {
                        $to[] = $participant->getUser()->getUsername();
                    }
                }

                if ($is_site) {
                    $messages['result'][$key] = array(
                        'chat_id' => $value->getChat()->getId(),
                        'from_username' => $value->getUser()->getUsername(),
                        'from_photo' => $value->getUser()->getPhoto(),
                        'to_usernames' => $to,
                        'message' => $value->getMessage(),
                        'files' => $value->getFiles(),
                        'date_created' => $value->getDateCreated(),
                    );
                } else {
                    $messages['result'][$key] = array(
                        'chat_id' => $value->getChat()->getId(),
                        'from_username' => $value->getUser()->getUsername(),
                        'to_usernames' => $to,
                        'message' => $value->getMessage(),
                        'files' => $value->getFiles(),
                        'date_created' => $value->getDateCreated(),
                    );
                }
            }
        }

        if ($is_site) {
            return $messages;
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'messages' => $messages,
            ),
        ));
    }

    /**
     * Sends start/stop typing to user.
     *
     * @param string  $command Where "start" or "stop"
     * @param integer $chat_id Chat's id
     *
     * @Route("/api/messages/typing/{command}")
     * @method("GET")
     */
    public function typingAction(Application $app, Request $request)
    {
        $command = $request->get('command');
        $chatId = $request->get('chat_id');

        if (!$chat = $app['chat.repository']->find($chatId)) {
            throw new \Exception(sprintf('Chat "%d" does not exist.', $chatId));
        }

        $pushNotification = array();

        foreach ($chat->getParticipants() as $participant) {
            $user = $participant->getUser();

            if ($user->getUsername() == $app['user']->getUsername()) continue;

            switch ($command) {
                case 'start':
                    $pushNotification[$user->getUsername()] = PushNotification::send($app, $user, array(
                        'parameters' => $chat->getId(),
                        'from_username' => $app['user']->getUsername(),
                        'title' => 'START_TYPING',
                        'message' => null,
                    ));

                    break;

                case 'stop':
                    $pushNotification[$user->getUsername()] = PushNotification::send($app, $user, array(
                        'parameters' => $chat->getId(),
                        'from_username' => $app['user']->getUsername(),
                        'title' => 'STOP_TYPING',
                        'message' => null,
                    ));


                    break;
            }
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'push_notification' => $pushNotification,
            ),
        ));
    }

    /**
     * Adds a message.
     *
     * @param integer $chat_id      Chat's id (optional)
     * @param string  $text         Text message
     * @param string  $file         Filename
     * @param array   $participants List of usernames (@...) (optional)
     *                              Use when creating a new chat for the first time
     *
     * @return Message Saved message
     *
     * @Route("/api/messages/add")
     * @method({"GET", "POST"})
     */
    public function addAction(Application $app, Request $request)
    {
        $chatId = $request->get('chat_id');
        $text = $request->get('text');
        $participants = (array) $request->get('participants');

        foreach ($participants as $key => $username) {
            if (
                $username != $app['user']->getUsername() &&
                $user = $app['user.repository']->findUserByUsername($username)
            ) {
                $participants[$key] = $user;
            } else {
                unset($participants[$key]);
            }
        }

        // add logged-in user
        $participants[] = $app['user'];

        if (!$chat = $app['chat.repository']->find($chatId)) {
            $chat = new Chat();

            $app['chat.repository']->create($chat);
        }

        // assign participants
        foreach ($participants as $user) {
            $found = false;
            foreach ($chat->getParticipants() as $participant) {
                if ($user->getId() == $participant->getUser()->getId()) {
                    $found = true;

                    break;
                }
            }
            if (!$found) {
                $participant = new ChatParticipant();
                $participant->setUser($user);
                $participant->setChat($chat);
                $participant->setOpen(true);
                $participant->setUnread(1);

                $app['chat_participant.repository']->create($participant);

                $chat->getParticipants()->add($participant);
            }
        }

        // making sure chat include more than one participant
        if ($chat->getParticipants()->count() <= 1) {
            throw new \Exception('You must provide at least two participants (including sender) when creating a new chat.');
        }

        $message = new ChatMessage();
        $message->setChat($chat);
        $message->setUser($app['user']);
        $message->setMessage($text);

        $files = array ($request->get('file'));
        foreach ($files as $key => $file) {
            if (!$file || !file_exists($app['media_dir'].$file)) {
                unset($files[$key]);
            }
        }
        $message->setFiles($files);

        $app['chat_message.repository']->create($message);

        // set "to" usernames
        $to = array();
        foreach ($chat->getParticipants() as $participant) {
            if ($participant->getUser()->getId() == $app['user']->getId()) {
                $participant->setUnread(0);
            } else {
                $to[] = $participant->getUser()->getUsername();

                $participant->setUnread($participant->getUnread() + 1);
            }

            if (!$participant->getOpen()) {
                $participant->setOpen(true);
            }

            $app['chat_participant.repository']->update($participant);
        }

        // set response
        $message = array(
            'chat_id' => $chat->getId(),
            'from_username' => $app['user']->getUsername(),
            'to_usernames' => $to,
            'message' => $message->getMessage(),
            'files' => $message->getFiles(),
            'date_created' => $message->getDateCreated(),
        );

        // send message to friend(s)
        $pushNotification = array();

        foreach ($chat->getParticipants() as $participant) {
            $user = $participant->getUser();

            if ($user->getUsername() == $app['user']->getUsername()) continue;

            $pushNotification[$user->getUsername()] = PushNotification::send($app, $user, array(
                'parameters' => $chat->getId(),
                'from_username' => $app['user']->getUsername(),
                'title' => 'NEW_MESSAGE',
                'message' => $message,
            ));
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'message' => $message,
                'push_notification' => $pushNotification,
            ),
        ));
    }

    /**
     * Mark a message as read.
     *
     * Note: we only count unread messages, to actually tag each message as read..
     *
     * @param integer $chat_id Chat's id
     *
     * @Route("/api/messages/read")
     * @method("GET")
     */
    public function readAction(Application $app, Request $request)
    {
        $chatId = $request->get('chat_id');

        if (!$chat = $app['chat.repository']->find($chatId)) {
            throw new \Exception(sprintf('Chat "%d" does not exist.', $chatId));
        }

        foreach ($chat->getParticipants() as $participant) {
            if (
                $participant->getUser()->getUsername() == $app['user']->getUsername()
                &&
                $participant->getUnread() > 0
            ) {
                $participant->setUnread(0);

                $app['chat_participant.repository']->update($participant);
            }
        }

        return $app->json(array(
            'success' => true,
        ));
    }

    /**
     * Invites (add) a user to the chat.
     *
     * @param string  $username A user username
     * @param integer $chat_id  Chat's id
     *
     * @Route("/api/messages/invite/{username}")
     * @method("GET")
     */
    public function inviteAction(Application $app, Request $request)
    {
        $username = $request->get('username');
        $chatId = $request->get('chat_id');

        if (!$user = $app['user.repository']->findUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username));
        }
        if (!$chat = $app['chat.repository']->find($chatId)) {
            throw new \Exception(sprintf('Chat "%d" does not exist.', $chatId));
        }

        foreach ($chat->getParticipants() as $participant) {
            if ($participant->getUser()->getUsername() == $user->getUsername()) {
                throw new \Exception(sprintf('User "%s" is already one of the participants of the chat.', $user->getUsername()));
            }
        }

        $participant = new Participant();
        $participant->setUser($user);
        $participant->setChat($chat);
        $participant->setOpen(true);

        $app['chat_participant.repository']->create($participant);

        $chat->getParticipants()->add($participant);

        // send message to friend(s)
        $pushNotification = array();

        foreach ($chat->getParticipants() as $participant) {
            if ($participant->getUser()->getUsername() == $app['user']->getUsername()) continue;

            $pushNotification[$participant->getUser()->getUsername()] = PushNotification::send($app, $participant->getUser(), array(
                'parameters' => $chat->getId(),
                'from_username' => $app['user']->getUsername(),
                'title' => 'INVITE',
                'message' => sprintf('%s invited % to the group chat.', $app['user']->getUsername(), $user->getUsername()),
            ));
        }

        return $app->json(array(
            'success' => true,
            'data' => array(
                'push_notification' => $pushNotification,
            ),
        ));
    }
}
