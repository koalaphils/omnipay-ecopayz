<?php

namespace ZendeskBundle\Manager;

/**
 * Description of UserManager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class UserManager extends AbstractManager
{
    public function create($data)
    {
        $user = $this->getZendeskAPI()->users()->createOrUpdate($data);

        return $user;
    }
}
