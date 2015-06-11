<?php

namespace ChatApp\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Default ORM Chat implementation.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="chats")
 */
class Chat extends Base
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * List of participants
     *
     * @ORM\OneToMany(targetEntity="ChatParticipant", mappedBy="chat")
     */
    protected $participants;

    /**
     * @ORM\OneToMany(targetEntity="ChatMessage", mappedBy="chat")
     */
    protected $messages;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_modified;

    /**
     * Get participants
     *
     * @return Collection
     */
    public function getParticipants()
    {
        return $this->participants ?: $this->participants = new ArrayCollection();
    }

    /**
     * Get messages
     *
     * @return Collection
     */
    public function getMessages()
    {
        return $this->messages ?: $this->messages = new ArrayCollection();
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
}
