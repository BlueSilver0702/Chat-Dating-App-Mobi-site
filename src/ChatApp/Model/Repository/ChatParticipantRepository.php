<?php

namespace ChatApp\Model\Repository;

use ChatApp\Model\Entity\User;

class ChatParticipantRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\ChatParticipant';
    }

    /**
     * Find all open chats user participant.
     *
     * @param User    $user  User object
     * @param integer $page  Pagination offset (optional)
     * @param integer $limit Maximum number of results to retrieve (optional)
     *
     * @return \ChatApp\Model\Entity\Chat[]
     */
    public function findOpenChats(User $user, $page = 1, $limit = 20)
    {
        // finds chats along with latest message ordered by latest message date desc
        $queryString = '
            select sql_calc_found_rows c.id, m1.*, u.username
            from chats c
                join chats_participants p on (p.chat_id = c.id)
                join chats_messages m1 on (m1.chat_id = c.id)
                left outer join chats_messages as m2 on (
                    c.id = m2.chat_id and (m1.date_created < m2.date_created or m1.date_created = m2.date_created and m1.id < m2.id)
                )
                join users u on (u.id = m1.user_id)
            where p.open = 1 and m2.id is null and p.user_id = :user_id
            order by m1.date_created desc
        ';

        $offset = -1;
        if ($limit > -1) {
            $offset = ($page - 1) * $limit;
            $queryString .= sprintf(' limit %s, %s', (int)$offset, (int)$limit);
        }

        $query = $this->em->getConnection()->prepare($queryString);
        $query->execute(array('user_id' => $user->getId()));
        $results = $query->fetchAll();

        $totalQuery = $this->em->getConnection()->executeQuery('select found_rows()');
        $total = $totalQuery->fetchColumn();

        $chats = array();
        foreach ($results as $result) {
            $id = $result['chat_id'];
            $chats[$id] = array(
                'chat_id' => $id,
                'participants' => array(),
                'last_message' => array(
                    'from_username' => $result['username'],
                    'to_usernames' => array(),
                    'message' => $result['message'],
                    'files' => unserialize($result['files']),
                    'date_created' => new \DateTime($result['date_created']),
                ),
            );
        }

        $participants = $this->repository->createQueryBuilder('p')
            ->select('partial p.{id, unread}, partial c.{id}, partial u.{id, username, photo, aboutme}')
            ->join('p.user', 'u')
            ->join('p.chat', 'c')
            ->where('p.chat in (:chats)')
            ->setParameter('chats', array_keys($chats))
            ->getQuery()
            ->getResult();

        foreach ($participants as $participant) {
            $user = $participant->getUser();
            $id = $participant->getChat()->getId();
            $chats[$id]['participants'][] = array(
                'username' => $user->getUsername(),
                'photo' => $user->getPhoto(),
                'aboutme' => $user->getAboutme(),
                'unread' => $participant->getUnread(),
            );

            if ($user->getUsername() !== $chats[$id]['last_message']['from_username']) {
                $chats[$id]['last_message']['to_usernames'][] = $user->getUsername();
            }
        }

        return array(
            'info' => array(
                'offset' => $offset,
                'limit'  => $limit,
                'count'  => $total,
            ),
            'count'  => count($chats),
            'result' => array_values($chats),
        );
    }
}
