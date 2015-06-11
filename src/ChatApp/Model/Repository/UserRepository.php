<?php

namespace ChatApp\Model\Repository;

use Doctrine\ORM\NoResultException;
use ChatApp\Util\Inflector;

class UserRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\User';
    }

    /**
     * Returns promo stats.
     */
    public function getPromoterStats()
    {
        try {
            return $this->repository
                ->createQueryBuilder('u')
                ->select('u.promo_code, date_format(u.date_created, \'%Y-%m-%d\') as date, count(u.id) as total')
                ->where('u.promo_code is not null')
                ->groupBy('u.promo_code, date')
                ->getQuery()
                ->getArrayResult()
            ;
        } catch (NoResultException $e) {}

        return null;
    }

    /**
     * Calculate the Distance Between Two Coordinates (latitude, longitude).
     *
     * @return float The distance in miles or kilometers
     */
    public function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
    {
        return (($miles ? 3959 : 6371) * acos(cos(deg2rad($lat2)) * cos(deg2rad($lat1)) * cos(deg2rad($lng1) - deg2rad($lng2)) + sin(deg2rad($lat2)) * sin(deg2rad($lat1))));
    }

    /**
     * Find a user by its token = sha1(password).
     *
     * @param string $token
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByToken($token)
    {
        try {
            return $this->repository
                ->createQueryBuilder('u')
                ->andWhere('u.enabled = true and u.captcha_expired = false and (u.captcha_expire_at is null or u.captcha_expire_at > :now)')
                ->setParameter('now', new \DateTime())
                ->andWhere('sha1(u.id) = :id')
                ->setParameter('id', $token)
                ->getQuery()
                ->getSingleResult();
            ;
        } catch (NoResultException $e) {}

        return null;
    }

    /**
     * Find a user by its username.
     *
     * @param string $username
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByUsername($username)
    {
        return $this->repository->findOneByUsername($username);
    }

    /**
     * Finds a user by its phone number.
     *
     * @param string $phone_number
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByPhoneNumber($phone_number)
    {
        return $this->repository->findOneBy(array('phone_number' => $phone_number));
    }

    /**
     * Finds a user by its email.
     *
     * @param string $email
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByEmail($email)
    {
        return $this->repository->findOneByEmail($email);
    }

    /**
     * Finds a user by its facebook user id.
     *
     * @param string $facebookUid
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByFacebookUid($facebookUid)
    {
        return $this->repository->findOneBy(array('facebook_uid' => $facebookUid));
    }

    /**
     * Finds a user by its username, phone number or email.
     *
     * @param string $usernameOrPhoneNumberOrEmail
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByUsernameOrPhoneNumberOrEmail($usernameOrPhoneNumberOrEmail)
    {
        try {
            return $this->repository
                ->createQueryBuilder('u')
                ->andWhere('u.username = :username')
                ->setParameter('username', $usernameOrPhoneNumberOrEmail)
                ->orWhere('u.phone_number = :phone_number')
                ->setParameter('phone_number', $usernameOrPhoneNumberOrEmail)
                ->orWhere('u.email = :email')
                ->setParameter('email', $usernameOrPhoneNumberOrEmail)
                ->getQuery()
                ->getSingleResult();
            ;
        } catch (NoResultException $e) {}

        return null;
    }

    /**
     * Find all users by their username.
     *
     * @param array $usernames
     *
     * @return array or null if no users found
     */
    public function findUsersByUsername(array $usernames = array())
    {
        try {
            $fields = array(
                'u.username',
                'u.email',
                'u.phone_number',
                'u.name',
                'u.gender',
                'u.region',
                'u.interest',
                'u.aboutme',
                'u.greeting',
                'u.latitude',
                'u.longitude',
                'u.photo',
                'u.background',
            );
            return $this->repository
                ->createQueryBuilder('u')
                ->select(implode($fields, ','))
                ->andWhere('u.enabled = true and u.captcha_expired = false and (u.captcha_expire_at is null or u.captcha_expire_at > :now)')
                ->setParameter('now', new \DateTime())
                ->andWhere('u.username in (:username)')
                ->setParameter('username', $usernames)
                ->getQuery()
                ->getArrayResult()
            ;
        } catch (NoResultException $e) {}

        return null;
    }

    /**
     * Returns a unique captcha.
     *
     * @return string
     */
    public function generateCaptcha()
    {
        do {
            $found = $this->findOneBy(array('captcha' => $captcha = Inflector::getRandomString(6, '0123456789')));
        } while ($found);

        return $captcha;
    }

    /**
     * Finds a user by its captcha.
     *
     * @param string $captcha
     *
     * @return \ChatApp\Model\Entity\User or null if user does not exist
     */
    public function findUserByCaptcha($captcha)
    {
        return $this->repository->findOneBy(array('captcha' => $captcha));
    }

    /**
     * Finds users by filters.
     *
     * @param array   $filters Filters (optional)
     * @param string  $sort    Sort result (optional)
     * @param integer $page    Pagination offset (optional)
     * @param integer $limit   Maximum number of results to retrieve (optional)
     *
     * Filters:
     *
     * @param string  $query       Search query (optional)
     * @param string  $username    Search query (optional)
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
            ->createQueryBuilder('u')
            ->andWhere('u.enabled = true and u.captcha_expired = false and (u.captcha_expire_at is null or u.captcha_expire_at > :now)')
            ->setParameter('now', new \DateTime())
        ;

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

        if (isset($filters['username']) && $filters['username']) {
            $filters['username'] = '%'.$filters['username'].'%';

            $query
                ->andWhere('u.username like :username')
                ->setParameter('username', $filters['username'])
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

        if (isset($filters['gender']) && $filters['gender']) {
            $query
                    ->andWhere('u.gender = :gender')
                    ->setParameter('gender', $filters['gender'])
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

            case 'username':
                $query->orderBy('u.username');

                break;

            default:
                $query->orderBy('u.last_modified', 'desc');
                break;
        }

        return parent::doSearch($query, 'u', $filters, $page, $limit);
    }
}
