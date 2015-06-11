<?php

namespace ChatApp\Model\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Default ORM User implementation.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="users",
 *      indexes={
 *           @ORM\Index(columns={"username"}),
 *           @ORM\Index(columns={"name"})
 *      }
 * )
 */
class User extends Base implements UserInterface, \Serializable
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Since some of the URLs on the site actually have the username,
     * we need to be able to map from the username to the user id.
     *
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $phone_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $password;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $roles = array();

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $reported = false;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $captcha;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $captcha_expired = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $captcha_expire_at;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $facebook_uid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $promo_code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $endroid_gcm_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $ios_device_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    protected $gender;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birthday;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $ethnicity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $region;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $interest;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $aboutme;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $greeting;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $photo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $background;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $latitude;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $longitude;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_login;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_modified;

    /**
     * @ORM\OneToMany(targetEntity="Friend", mappedBy="user")
     */
    protected $friends = array();

    /**
     * @ORM\OneToMany(targetEntity="Moment", mappedBy="user")
     */
    protected $moments = array();

    /**
     * List of blocked moments.
     *
     * @ORM\ManyToMany(targetEntity="Moment")
     * @ORM\JoinTable(
     *     name="users_to_block_moments",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="moments_id", referencedColumnName="id", onDelete="cascade")}
     * )
     */
    protected $blockedMoments;

    /**
     * List of blocked users.
     *
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(
     *     name="users_to_block_users",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="blocked_user_id", referencedColumnName="id", onDelete="cascade")}
     * )
     */
    protected $blockedUsers;

    /**
     * Returns the unique id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a sha1() version of the unique id.
     *
     * @return mixed
     */
    public function getToken()
    {
        return sha1($this->id);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {
        // do nothing
    }

    /**
     * Returns the user email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns the user name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the user birthday.
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Returns the user ethnicity.
     *
     * @return string
     */
    public function getEthnicity()
    {
        return $this->ethnicity;
    }

    /**
     * Returns the user region.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Sets the user interest.
     *
     * @param string $interest
     *
     * @return self
     */
    public function setInterest($interest)
    {
        if (is_array($interest)) {
            foreach ($interest as $key => $value) {
                if (!$value) unset($interest[$key]);
            }
            $interest = implode('|', $interest);
        }

        $this->interest = $interest;

        return $this;
    }

    /**
     * Returns the user interest.
     *
     * @return string
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Returns the user aboutme.
     *
     * @return string
     */
    public function getAboutme()
    {
        return $this->aboutme;
    }

    /**
     * Returns the user greeting.
     *
     * @return string
     */
    public function getGreeting()
    {
        return $this->greeting;
    }

    /**
     * Returns the user photo.
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Returns the user background.
     *
     * @return string
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Set the user latitude.
     *
     * @param string $lat
     * 
     * @return self
     */
    public function setLatitude($lat)
    {
        $this->latitude = $lat;

        return $this;
    }

    /**
     * Returns the user latitude.
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set the user longitude.
     *
     * @param string $lng
     * 
     * @return self
     */
    public function setLongitude($lng)
    {
        $this->longitude = $lng;

        return $this;
    }

    /**
     * Returns the user longitude.
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Sets the user gender.
     *
     * @param string $gender
     *
     * @return self
     */
    public function setGender($gender)
    {
        $this->gender = strtoupper($gender);

        return $this;
    }

    /**
     * Returns the user gender.
     *
     * @return string
     */
    public function getGender()
    {
        switch (strtolower($this->gender)) {
            case 'm': return 'Male';
            case 'f': return 'Female';
        }
        return $this->gender;
    }

    /**
     * Get friends
     *
     * @return Collection
     */
    public function getFriends()
    {
        return $this->friends ?: $this->friends = new ArrayCollection();
    }

    /**
     * Get moments
     *
     * @return Collection
     */
    public function getMoments()
    {
        return $this->moments ?: $this->moments = new ArrayCollection();
    }

    /**
     * Get blocked moments
     *
     * @return Collection
     */
    public function getBlockedMoments()
    {
        return $this->blockedMoments ?: $this->blockedMoments = new ArrayCollection();
    }

    /**
     * Get blocked users
     *
     * @return Collection
     */
    public function getBlockedUsers()
    {
        return $this->blockedUsers ?: $this->blockedUsers = new ArrayCollection();
    }

    /**
     * Never use this to check if this user has access to anything!
     *
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return Boolean
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Sets the roles of the user.
     *
     * This overwrites any previous roles.
     *
     * @param array $roles
     *
     * @return self
     */
    public function setRoles(array $roles = array())
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Gets the user roles.
     *
     * @return array
     */
    public function getRoles()
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    /**
     * Adds a role to the user.
     *
     * @param string $role
     *
     * @return self
     */
    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * Removes a role from the user.
     *
     * @param string $role
     *
     * @return self
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * Returns user status.
     *
     * @return Boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Checks whether the user's captcha has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return Boolean true if the user's captcha are non expired, false otherwise
     */
    public function isCaptchaNonExpired()
    {
        if (true === $this->captcha_expired) {
            return false;
        }

        if (null !== $this->captcha_expire_at && $this->captcha_expire_at->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    /**
     * Tells if the the given user is this user.
     *
     * Useful when not hydrating all fields.
     *
     * @param null|User $user
     *
     * @return Boolean
     */
    public function isUser(User $user = null)
    {
        return null !== $user && $this->getId() === $user->getId();
    }

    /**
     * Tells if the the given user has the super admin role.
     *
     * @return Boolean
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    /**
     * Sets the super admin status
     *
     * @param Boolean $boolean
     *
     * @return self
     */
    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     * Hook on pre-persist operations
     */
    public function prePersist()
    {
        $this->date_created = new \DateTime();
        $this->last_modified = new \DateTime();
    }

    /**
     * Hook on pre-update operations
     */
    public function preUpdate()
    {
        $this->last_modified = new \DateTime();
    }

    /**
     * Serializes the user.
     *
     * The serialized data have to contain the fields used by the equals method and the username.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
        ));
    }

    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));

        list(
            $this->password,
            $this->username,
            $this->enabled,
            $this->id
        ) = $data;
    }

    /**
     * Returns a string representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getUsername();
    }
}
