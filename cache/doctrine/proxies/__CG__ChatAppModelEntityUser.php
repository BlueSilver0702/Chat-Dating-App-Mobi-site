<?php

namespace DoctrineProxy\__CG__\ChatApp\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class User extends \ChatApp\Model\Entity\User implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function __get($name)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__get', array($name));

        return parent::__get($name);
    }

    /**
     * {@inheritDoc}
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__set', array($name, $value));

        return parent::__set($name, $value);
    }



    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'id', 'username', 'email', 'phone_number', 'password', 'roles', 'enabled', 'reported', 'captcha', 'captcha_expired', 'captcha_expire_at', 'facebook_uid', 'promo_code', 'endroid_gcm_id', 'ios_device_id', 'name', 'gender', 'birthday', 'ethnicity', 'region', 'interest', 'aboutme', 'greeting', 'photo', 'background', 'latitude', 'longitude', 'last_login', 'date_created', 'last_modified', 'friends', 'moments', 'blockedMoments', 'blockedUsers');
        }

        return array('__isInitialized__', 'id', 'username', 'email', 'phone_number', 'password', 'roles', 'enabled', 'reported', 'captcha', 'captcha_expired', 'captcha_expire_at', 'facebook_uid', 'promo_code', 'endroid_gcm_id', 'ios_device_id', 'name', 'gender', 'birthday', 'ethnicity', 'region', 'interest', 'aboutme', 'greeting', 'photo', 'background', 'latitude', 'longitude', 'last_login', 'date_created', 'last_modified', 'friends', 'moments', 'blockedMoments', 'blockedUsers');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (User $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getToken()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getToken', array());

        return parent::getToken();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsername', array());

        return parent::getUsername();
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPassword', array());

        return parent::getPassword();
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSalt', array());

        return parent::getSalt();
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'eraseCredentials', array());

        return parent::eraseCredentials();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmail', array());

        return parent::getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getBirthday()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBirthday', array());

        return parent::getBirthday();
    }

    /**
     * {@inheritDoc}
     */
    public function getEthnicity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEthnicity', array());

        return parent::getEthnicity();
    }

    /**
     * {@inheritDoc}
     */
    public function getRegion()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRegion', array());

        return parent::getRegion();
    }

    /**
     * {@inheritDoc}
     */
    public function setInterest($interest)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setInterest', array($interest));

        return parent::setInterest($interest);
    }

    /**
     * {@inheritDoc}
     */
    public function getInterest()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getInterest', array());

        return parent::getInterest();
    }

    /**
     * {@inheritDoc}
     */
    public function getAboutme()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAboutme', array());

        return parent::getAboutme();
    }

    /**
     * {@inheritDoc}
     */
    public function getGreeting()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGreeting', array());

        return parent::getGreeting();
    }

    /**
     * {@inheritDoc}
     */
    public function getPhoto()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPhoto', array());

        return parent::getPhoto();
    }

    /**
     * {@inheritDoc}
     */
    public function getBackground()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBackground', array());

        return parent::getBackground();
    }

    /**
     * {@inheritDoc}
     */
    public function setLatitude($lat)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLatitude', array($lat));

        return parent::setLatitude($lat);
    }

    /**
     * {@inheritDoc}
     */
    public function getLatitude()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLatitude', array());

        return parent::getLatitude();
    }

    /**
     * {@inheritDoc}
     */
    public function setLongitude($lng)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLongitude', array($lng));

        return parent::setLongitude($lng);
    }

    /**
     * {@inheritDoc}
     */
    public function getLongitude()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLongitude', array());

        return parent::getLongitude();
    }

    /**
     * {@inheritDoc}
     */
    public function setGender($gender)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setGender', array($gender));

        return parent::setGender($gender);
    }

    /**
     * {@inheritDoc}
     */
    public function getGender()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGender', array());

        return parent::getGender();
    }

    /**
     * {@inheritDoc}
     */
    public function getFriends()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFriends', array());

        return parent::getFriends();
    }

    /**
     * {@inheritDoc}
     */
    public function getMoments()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMoments', array());

        return parent::getMoments();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockedMoments()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBlockedMoments', array());

        return parent::getBlockedMoments();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockedUsers()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBlockedUsers', array());

        return parent::getBlockedUsers();
    }

    /**
     * {@inheritDoc}
     */
    public function hasRole($role)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'hasRole', array($role));

        return parent::hasRole($role);
    }

    /**
     * {@inheritDoc}
     */
    public function setRoles(array $roles = array (
))
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRoles', array($roles));

        return parent::setRoles($roles);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRoles', array());

        return parent::getRoles();
    }

    /**
     * {@inheritDoc}
     */
    public function addRole($role)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRole', array($role));

        return parent::addRole($role);
    }

    /**
     * {@inheritDoc}
     */
    public function removeRole($role)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeRole', array($role));

        return parent::removeRole($role);
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isEnabled', array());

        return parent::isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function isCaptchaNonExpired()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isCaptchaNonExpired', array());

        return parent::isCaptchaNonExpired();
    }

    /**
     * {@inheritDoc}
     */
    public function isUser(\ChatApp\Model\Entity\User $user = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isUser', array($user));

        return parent::isUser($user);
    }

    /**
     * {@inheritDoc}
     */
    public function isSuperAdmin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSuperAdmin', array());

        return parent::isSuperAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function setSuperAdmin($boolean)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSuperAdmin', array($boolean));

        return parent::setSuperAdmin($boolean);
    }

    /**
     * {@inheritDoc}
     */
    public function prePersist()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'prePersist', array());

        return parent::prePersist();
    }

    /**
     * {@inheritDoc}
     */
    public function preUpdate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'preUpdate', array());

        return parent::preUpdate();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'serialize', array());

        return parent::serialize();
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'unserialize', array($serialized));

        return parent::unserialize($serialized);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__toString', array());

        return parent::__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'has', array($name));

        return parent::has($name);
    }

    /**
     * {@inheritDoc}
     */
    public function __call($method, $arguments)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__call', array($method, $arguments));

        return parent::__call($method, $arguments);
    }

}
