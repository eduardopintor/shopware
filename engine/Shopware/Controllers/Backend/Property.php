<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Controllers
 * @subpackage Property
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Benjamin Cremer
 * @author     $Author$
 */

use Shopware\Models\Property\Value,
    Shopware\Models\Property\Option,
    Shopware\Models\Property\Group;

/**
 * Shopware Backend Controller for the property module
 */
class Shopware_Controllers_Backend_Property extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Entity Manager
     * @var null
     */
    protected $manager = null;

    /**
     * @var \Shopware\Models\Property\Repository
     */
    protected $propertyRepository = null;

    /**
     * Helper function to get access to the property repository.
     * @return \Shopware\Models\Property\Repository
     */
    private function getPropertyRepository() {
    	if ($this->propertyRepository === null) {
    		$this->propertyRepository = Shopware()->Models()->getRepository('Shopware\Models\Property\Group');
    	}
    	return $this->propertyRepository;
    }

    /**
     * Internal helper function to get access to the entity manager.
     * @return null
     */
    private function getManager() {
        if ($this->manager === null) {
            $this->manager= Shopware()->Models();
        }
        return $this->manager;
    }


    //todo@ms: pager einbauen
    /**
     * returns the groups for the sets grids
     */
    public function getSetsAction()
    {
        $query = $this->getPropertyRepository()->getSetsQuery();
        $sets = $query->getArrayResult();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $sets,
                'total' => count($sets),
            )
        );
    }


    /**
     * returns the groups for the sets grids
     */
    public function getSetAssignsAction()
    {
        $setId = $this->Request()->getParam('setId');

        $query = $this->getPropertyRepository()->getSetAssignsQuery($setId);
        $assignments = $query->getArrayResult();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $assignments,
                'total' => count($assignments),
            )
        );
    }


    public function createSetAction()
    {
        $params = $this->Request()->getPost();
        $params['attribute'] = $params['attribute'][0];

        $group = new Group();
        $group->fromArray($params);

        try {
            Shopware()->Models()->persist($group);
            Shopware()->Models()->flush();
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }
        $data = $this->getPropertyRepository()
                     ->getGroupDetailQuery($group->getId())
                     ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    /**
     * Internal helper function to save the dynamic attributes of an article price.
     * @param $group
     * @param $attributeData
     * @return mixed
     */
    private function saveGroupAttributes($group, $attributeData)
    {
    	if (empty($attributeData)) {
    		return;
    	}
    	if ($group->getId() > 0) {
            $result = $this->getPropertyRepository()
                           ->getAttributesQuery($group->getId())
                           ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
    		if (empty($result)) {
    			$attributes = new \Shopware\Models\Attribute\PropertyGroup();
    		} else {
    			$attributes = $result;
    		}
    	} else {
    		$attributes = new \Shopware\Models\Attribute\PropertyGroup();
    	}
    	$attributes->fromArray($attributeData);
    	$attributes->setPropertyGroup($group);
    	$this->getManager()->persist($attributes);
    }

    /**
     * Updates one group identified by its identifier
     */
    public function updateSetAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $group Group */
        $group = Shopware()->Models()->getRepository('Shopware\Models\Property\Group')->find($id);
        if (!$group) {
            $this->View()->assign(array('success' => false, 'message' => 'Group not found'));
            return;
        }

        $params = $this->Request()->getPost();
        $params['attribute'] = $params['attribute'][0];
        $group->fromArray($params);

        try {
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $data = $this->getPropertyRepository()
                     ->getGroupDetailQuery($group->getId())
                     ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    public function deleteSetAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $group Group */
        $group = Shopware()->Models()->getRepository('Shopware\Models\Property\Group')->find($id);
        if (!$group) {
            $this->View()->assign(array('success' => false, 'message' => 'Group not found'));
            return;
        }

        try {
            Shopware()->Models()->remove($group);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $this->View()->assign(array('success' => true));
    }

    public function onAddAssignmentAction()
    {
        if (!($optionId = $this->Request()->getParam('optionId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'OptionId not found'));
            return;
        }

        if (!($setId = $this->Request()->getParam('setId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'SetId not found'));
            return;
        }

        /* @var $group Group */
        $group = Shopware()->Models()->getRepository('Shopware\Models\Property\Group')->find($setId);
        if (!$group) {
            $this->View()->assign(array('success' => false, 'message' => 'Group not found'));
            return;
        }

        /* @var $option Option */
        $option = Shopware()->Models()->getRepository('Shopware\Models\Property\Option')->find($optionId);
        if (!$option) {
            $this->View()->assign(array('success' => false, 'message' => 'Option not found'));
            return;
        }

        $group->addOption($option);

        try {
            Shopware()->Models()->flush();
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $this->View()->assign(array('success' => true));
    }

    public function removeOptionFromGroupAction()
    {
        if (!($optionId = $this->Request()->getParam('optionId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'OptionId not found'));
            return;
        }

        if (!($groupId = $this->Request()->getParam('groupId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'GroupId not found'));
            return;
        }

        /* @var $group Group */
        $group = Shopware()->Models()->getRepository('Shopware\Models\Property\Group')->find($groupId);
        if (!$group) {
            $this->View()->assign(array('success' => false, 'message' => 'Group not found'));
            return;
        }

        /* @var $option Option */
        $option = Shopware()->Models()->getRepository('Shopware\Models\Property\Option')->find($optionId);
        if (!$option) {
            $this->View()->assign(array('success' => false, 'message' => 'Option not found'));
            return;
        }

        $group->removeOption($option);

        try {
            Shopware()->Models()->flush();
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $this->View()->assign(array('success' => true));
    }

    public function getGroupsAction()
    {
        /** @var $repository \Shopware\Components\Model\ModelRepository */
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Property\Option');
        $options = $repository->createQueryBuilder('option')->getQuery()->getArrayResult();

        $this->View()->assign(array(
           'success' => true,
           'data'    => $options,
           'total'   => count($options),
        ));
    }

    public function createGroupAction()
    {
        $params = $this->Request()->getPost();

        $option = new Option();
        $option->fromArray($params);

        try {
            Shopware()->Models()->persist($option);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $data = Shopware()->Models()->toArray($option);
        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    /**
     * Updates one option identified by its identifier
     */
    public function updateGroupAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $option Option */
        $option = Shopware()->Models()->getRepository('Shopware\Models\Property\Option')->find($id);
        if (!$option) {
            $this->View()->assign(array('success' => false, 'message' => 'Option not found'));
            return;
        }

        $option->fromArray($this->Request()->getPost());

        try {
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $data = Shopware()->Models()->toArray($option);
        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    /**
     * Deletes one option identified by its identifier
     *
     * ALso deletes associated values due to cascade={"remove"})
     */
    public function deleteGroupAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $option Option */
        $option = Shopware()->Models()->getRepository('Shopware\Models\Property\Option')->find($id);
        if (!$option) {
            $this->View()->assign(array('success' => false, 'message' => 'Snippet not found'));
            return;
        }

        try {
            // Cascades remove to associated values
            Shopware()->Models()->remove($option);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $this->View()->assign(array('success' => true));
    }

    public function getOptionsAction()
    {
        if (!($optionId = $this->Request()->getParam('optionId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'OptionId not found'));
            return;
        }



        $values = $this->getPropertyRepository()
                       ->getPropertyValueByOptionIdQuery($optionId)
                       ->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data'    => $values,
            'total'   => count($values),
        ));
    }

    public function createOptionAction()
    {
        if (!($optionId = $this->Request()->getParam('optionId'))) {
            $this->View()->assign(array('success' => false, 'message' => 'OptionId not found'));
            return;
        }

        /* @var $option Option */
        $option = Shopware()->Models()->getRepository('Shopware\Models\Property\Option')->find($optionId);
        if (!$option) {
            $this->View()->assign(array('success' => false, 'message' => 'Option not found'));
            return;
        }

        $postValue = $this->Request()->getPost('value');
        $value = new Value($option, $postValue);

        try {
            Shopware()->Models()->persist($value);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $data = Shopware()->Models()->toArray($value);
        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    public function updateOptionAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $value Value */
        $value = Shopware()->Models()->getRepository('Shopware\Models\Property\Value')->find($id);
        if (!$value) {
            $this->View()->assign(array('success' => false, 'message' => 'Value not found'));
            return;
        }

        $value->setValue($this->Request()->getPost('value'));

        try {
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $data = Shopware()->Models()->toArray($value);
        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    /**
     * Deletes one value identified by its identifier
     */
    public function deleteOptionAction()
    {
        if (!($id = $this->Request()->getParam('id'))) {
            $this->View()->assign(array('success' => false, 'message' => 'Id not found'));
            return;
        }

        /* @var $value Value */
        $value = Shopware()->Models()->getRepository('Shopware\Models\Property\Value')->find($id);
        if (!$value) {
            $this->View()->assign(array('success' => false, 'message' => 'Value not found'));
            return;
        }

        try {
            Shopware()->Models()->remove($value);
            Shopware()->Models()->flush();
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            return;
        }

        $this->View()->assign(array('success' => true));
    }

    /**
     * Changes Position of option in group
     */
    public function changeGroupPositionAction()
    {
        $data = $this->Request()->getParam('data');
        $data = json_decode($data, true);

        foreach ($data as $row) {
            Shopware()->Db()->update(
                's_filter_relations',
                array('position' => $row['position']),
                array('groupID = ?' => $row['groupId'], 'optionID = ?' => $row['optionId'])
            );
        }

        $this->View()->assign(array('success' => true, 'data' => $data));
    }

    /**
     * Changes Position of field
     */
    public function changeOptionPositionAction()
    {
        $data = $this->Request()->getParam('data');
        $positions = json_decode($data);

        $qb = $this->getManager()->createQueryBuilder();
        $qb->update('Shopware\Models\Property\Value', 'value')
           ->andWhere('value.id = :valueId');

        foreach ($positions as $position => $valueId) {
            $qb->set('value.position', $position)
               ->setParameter('valueId', $valueId)
               ->getQuery()
               ->execute();
        }

        $this->View()->assign(array('success' => true));
    }

    /**
     * Changes Position of the set assignments
     */
    public function changeAssignmentPositionAction()
    {
        $data = $this->Request()->getParam('data');
        $setId = $this->Request()->getParam('setId');
        $positions = json_decode($data);

        foreach ($positions as $position => $valueId) {
           $test[] = (array(array("position"=>$position,"optionId"=>$valueId,"groupId"=>$setId)));

            Shopware()->Db()->update(
                's_filter_relations',
                array('position' => $position),
                array('groupID = ?' => $setId, 'optionID = ?' => $valueId)
            );
        }


        $this->View()->assign(array('success' => true));
    }
}
