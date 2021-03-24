<?php

namespace ZendeskBundle\Manager;

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of ViewManager.
 *
 * @author cnonog
 */
class ViewManager extends AbstractManager
{
    public function getList(Request $request)
    {
        $results = $this->getZendeskAPI()->views()->findAllActive();
        $results = (array) $results;

        return $results;
    }

    public function execute(Request $request, $id)
    {
        $perPage = $request->get('limit', 10);
        $page = ($request->get('start', 0) / $perPage) + 1;

        $results = $this->getZendeskAPI()->views()->execute(['id' => $id, 'page' => $page, 'per_page' => $perPage]);
        $results = $results;

        return $results;
    }
}
