<?php

namespace BonusBundle\Manager;

use AppBundle\Manager\AbstractManager;

/**
 * Description of Bonus Manager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class BonusManager extends AbstractManager
{
    /**
     * Get notice repository.
     *
     * @return \DbBundle\Repository\BonusRepository
     */
    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Bonus');
    }

    /**
     * @param type $filters
     *
     * @return type
     */
    public function getBonusList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getList($filters);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data = [
                        'bonus' => $data,
                        'routes' => [
                            'update' => $this->getRouter()->generate('bonus.update_page', ['id' => $data['id']]),
                            'delete' => $this->getRouter()->generate('bonus.delete', ['id' => $data['id']]),
                        ],
                    ];

                    return $data;
                }, $results['data']);
            }

            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = $this->getRepository()->getList($filters);
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getUserList($filters);
        }

        return $results;
    }
}
