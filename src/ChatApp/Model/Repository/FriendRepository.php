<?php

namespace ChatApp\Model\Repository;

use Doctrine\ORM\NoResultException;

class FriendRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\Friend';
    }

    /**
     * Checks if user already befriended with friend.
     *
     * @param ChatApp\Model\Entity\User $user
     * @param ChatApp\Model\Entity\User $friend
     *
     * @return \ChatApp\Model\Entity\Friend or null if user does not exist
     */
    public function isBefriended($user, $friend)
    {
        try {
            return $this->repository
                ->createQueryBuilder('f')
                ->andWhere('f.user = :user')
                ->setParameter('user', $user)
                ->andWhere('f.friend = :friend')
                ->setParameter('friend', $friend)
                ->getQuery()
                ->getSingleResult();
            ;
        } catch (NoResultException $e) {}

        return null;
    }

    /**
     * Finds friends by filters.
     *
     * @param array   $filters Filters (optional)
     * @param string  $sort    Sort result (optional)
     * @param integer $page    Pagination offset (optional)
     * @param integer $limit   Maximum number of results to retrieve (optional)
     *
     * Filters:
     *
     * @param string  $user        User entity (optional)
     * @param string  $query       Search query (optional)
     * @param array   $equals      List of fields with values (optional)
     * @param float   $latitude    Distance from.. (optional)
     * @param float   $longitude   Distance from.. (optional)
     * @param integer $distance    Maximum distance (optional)
     * @param array   $block_users List of blocked users (optional)
     * @param Boolean $block_only  Whether or not to only return blocked profiles (optional)
     * @param Boolean $only_photo  Whether or not to only return profiles with photo (optional)
     */
    public function search(array $filters = array(), $sort = null, $page = 1, $limit = 20)
    {
        $query = $this
            ->repository
            ->createQueryBuilder('f')
            ->leftJoin('f.friend', 'u')
            ->andWhere('u.enabled = true and u.captcha_expired = false and (u.captcha_expire_at is null or u.captcha_expire_at > :now)')
            ->setParameter('now', new \DateTime())
        ;

        if (isset($filters['user']) && $filters['user']) {
            $query
                ->andWhere('f.user = :user')
                ->setParameter('user', $filters['user'])
            ;
        }

        if (isset($filters['query']) && $filters['query']) {
            $filters['query'] = '%'.$filters['query'].'%';

            $query
                ->andWhere('u.name like :name')
                ->setParameter('name', $filters['query'])
                ->orWhere('u.username like :username')
                ->setParameter('username', $filters['query'])
                ->orWhere('u.phone_number like :phone_number')
                ->setParameter('phone_number', $filters['query'])
                ->orWhere('u.aboutme like :aboutme')
                ->setParameter('aboutme', $filters['query'])
            ;
        }

        if (isset($filters['equals']) && is_array($filters['equals'])) {
            foreach ($filters['equals'] as $key => $value) {
                if ($value) {
                    $query
                        ->andWhere(sprintf('u.%s like :%s', $key, $key))
                        ->setParameter($key, '%'.$value.'%')
                    ;
                } else {
                    $query
                        ->andWhere(sprintf('u.%s = :%s or u.%s is null', $key, $key, $key))
                        ->setParameter($key, $value)
                    ;
                }
            }
        }

        if (isset($filters['latitude']) && isset($filters['longitude'])) {
            // to search by kilometers instead of miles, replace 3959 with 6371
            $query->addSelect(sprintf('(3959 * acos(cos(radians(%f)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(u.latitude)))) as distance', $filters['latitude'], $filters['longitude'], $filters['latitude']));

            $query
                ->andWhere('u.latitude is not null')
                ->andWhere('u.longitude is not null')
            ;

            if (isset($filters['distance'])) {
                $query
                    ->andWhere(sprintf('(3959 * acos(cos(radians(%f)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(u.latitude)))) <= :distance', $filters['latitude'], $filters['longitude'], $filters['latitude']))
                    ->setParameter('distance', $filters['distance'])
                ;
            }
        }

        if (isset($filters['block_users']) && $filters['block_users']) {
            if (isset($filters['block_only']) && $filters['block_only']) {
                $query
                    ->andWhere('u.id in (:id)')
                    ->setParameter('id', $filters['block_users'])
                ;
            } else {
                $query
                    ->andWhere('u.id not in (:id)')
                    ->setParameter('id', $filters['block_users'])
                ;
            }
        }

        if (isset($filters['only_photo']) && $filters['only_photo']) {
            $query->andWhere('u.photo is not null');
        }

        switch ($sort) {
            default:
                $query->orderBy('f.last_modified', 'desc');
                break;
        }

        return parent::doSearch($query, 'f', $filters, $page, $limit);
    }
}
