<?php

namespace ChatApp\Model\Repository;

use Doctrine\ORM\NoResultException;

class MomentCommentRepository extends Base
{
    /**
     * {@inheritDoc}
     */
    public function getRepositoryName()
    {
        return 'ChatApp\Model\Entity\MomentComment';
    }
}
