<?php

namespace GroupBundle\Manager;

use AppBundle\Manager\AbstractManager;

class GroupManager extends AbstractManager
{
    public function getGroupList($filters = null)
    {
        $results = [];
        $order = (!array_has($filters, 'order')) ? [['column' => 'createdAt', 'dir' => 'DESC']] : array_get($filters, 'order', []);

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getGroupList($filters, $order);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data['group'] = $data;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('group.update_page', ['id' => $data['group']['id']]),
                        'view' => $this->getRouter()->generate('group.view_page', ['id' => $data['group']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getGroupListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getGroupListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) {
                return [
                    'id' => $group['id'],
                    'text' => $group['name'],
                ];
            }, $this->getRepository()->getGroupList($filters));
            $results['recordsFiltered'] = $this->getRepository()->getGroupListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getGroupList($filters);
        }

        return $results;
    }
    
    /**
     * Get Group Repository.
     *
     * @return \DbBundle\Repository\UserGroupRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:UserGroup');
    }
}
