<?php

namespace ChatApp\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Default ORM Chat Message implementation.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="chats_messages")
 */
class ChatMessage extends Base
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chat", inversedBy="messages")
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $chat;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="friends")
     * @ORM\JoinColumn(onDelete="cascade")
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $message;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $files = array();

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_created;

    /**
     * Hook on pre-persist operations
     */
    public function prePersist()
    {
        $this->date_created = new \DateTime();
    }

    /**
     * Overwrite the magic setter to avoid unnecessary string
     * processing like strip splashes and html tags.
     *
     * @param unknown $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Returns a string representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getMessage();
    }
}
