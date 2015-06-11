<?php

namespace ChatApp\Model\Repository;

class MomentRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\Moment';
    }

    /**
     * Finds moments by filters.
     *
     * @param array   $filters Filters (optional)
     * @param string  $sort    Sort result (optional)
     * @param integer $page    Pagination offset (optional)
     * @param integer $limit   Maximum number of results to retrieve (optional)
     *
     * Filters:
     *
     * @param string  $query         Search query (optional)
     * @param array   $equals        List of fields with values (optional)
     * @param string  $username      User's username (optional)
     * @param string  $friends_of    User entity (optional)
     * @param float   $latitude      Distance from.. (optional)
     * @param float   $longitude     Distance from.. (optional)
     * @param integer $distance      Maximum distance (optional)
     * @param array   $block_moments List of blocked moments (optional)
     * @param Boolean $block_only    Whether or not to only return blocked moments (optional)
     * @param Boolean $cover_flag    Whether or not marked as cover (optional)
     * @param string  $from_date     Filter results by date (optional)
     * @param string  $to_date       Filter results by date (optional)
     */
    public function search(array $filters = array(), $sort = null, $page = 1, $limit = 20)
    {
        $query = $this
            ->repository
            ->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->andWhere('u.enabled = true and u.captcha_expired = false and (u.captcha_expire_at is null or u.captcha_expire_at > :now)')
            ->setParameter('now', new \DateTime())
        ;

        if (isset($filters['query']) && $filters['query']) {
            $filters['query'] = '%'.$filters['query'].'%';

            $query
                ->andWhere('m.name like :name')
                ->setParameter('name', $filters['query'])
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

        if (isset($filters['username']) && $filters['username']) {
            $query
                ->andWhere('u.username = :username')
                ->setParameter('username', $filters['username'])
            ;
        }

        if (isset($filters['friends_of']) && $filters['friends_of']) {
            $friends = array();
            foreach ($filters['friends_of']->getFriends() as $friend) {
                $friends[] = $friend->getFriend();
            }
            $query
                ->andWhere('m.user in (:user)')
                ->setParameter('user', $friends)
            ;
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

        if (isset($filters['gender']) && $filters['gender']) {
            $query
                ->andWhere('u.gender = :gender')
                ->setParameter('gender', $filters['gender'])
            ;
        }

        if (isset($filters['block_moments']) && $filters['block_moments']) {
            if (isset($filters['block_only']) && $filters['block_only']) {
                $query
                    ->andWhere('m.id in (:id)')
                    ->setParameter('id', $filters['block_moments'])
                ;
            } else {
                $query
                    ->andWhere('m.id not in (:id)')
                    ->setParameter('id', $filters['block_moments'])
                ;
            }
        }

        if (isset($filters['cover_flag'])) {
            $query
                ->andWhere('m.cover_flag = :cover_flag')
                ->setParameter('cover_flag', $filters['cover_flag'])
            ;
        }

        if (isset($filters['from_date']) && $filters['from_date']) {
            $query
                ->andWhere('m.date_created > :date_created')
                ->setParameter('date_created', $filters['from_date'])
            ;
        }

        if (isset($filters['to_date']) && $filters['to_date']) {
            $query
                ->andWhere('m.date_created < :date_created')
                ->setParameter('date_created', $filters['to_date'])
            ;
        }

        switch ($sort) {
            case 'distance':
                if (isset($filters['latitude']) && isset($filters['longitude'])) {
                    $query->orderBy('distance');
                } else {
                    $query->orderBy('u.region');
                }

                break;

            default:
                $query->orderBy('m.date_created', 'desc');
                break;
        }

        return parent::doSearch($query, 'm', $filters, $page, $limit);
    }
}
