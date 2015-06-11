<?php

namespace ChatApp\Model\Repository;

class ChatRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\Chat';
    }
}
