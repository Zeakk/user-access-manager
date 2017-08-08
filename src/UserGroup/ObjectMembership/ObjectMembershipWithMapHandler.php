<?php
/**
 * MembershipWithMapHandler.php
 *
 * The MembershipWithMapHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AssignmentInformation;

/**
 * Class MembershipWithMapHandler
 *
 * @package UserAccessManager\UserGroup
 */
abstract class ObjectMembershipWithMapHandler extends ObjectMembershipHandler
{
    /**
     * Returns the map.
     *
     * @return array
     */
    abstract protected function getMap();

    /**
     * Uses a map function to resolve the recursive membership.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    protected function getMembershipByMap($lockRecursive, $objectId, &$assignmentInformation = null)
    {
        // Reset value to prevent errors
        $recursiveMembership = [];

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $generalMap = isset($map[ObjectHandler::TREE_MAP_PARENTS][$this->objectType]) ?
                $map[ObjectHandler::TREE_MAP_PARENTS][$this->objectType] : [];

            if (isset($generalMap[$objectId]) === true) {
                foreach ($generalMap[$objectId] as $parentId => $type) {
                    $isAssignedToGroup = $this->userGroup->isObjectAssignedToGroup(
                        $this->objectType,
                        $parentId,
                        $rmAssignmentInformation
                    );

                    if ($isAssignedToGroup === true) {
                        $recursiveMembership[$this->objectType][$parentId] = $rmAssignmentInformation;
                    }
                }
            }
        }

        $isMember = $this->userGroup->isObjectAssignedToGroup($this->objectType, $objectId, $assignmentInformation);

        if ($isMember === true || count($recursiveMembership) > 0) {
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            return true;
        }

        return false;
    }

    /**
     * Returns the objects by the given type including the children.
     *
     * @param bool   $lockRecursive
     * @param string $objectType
     *
     * @return array
     */
    protected function getFullObjectsByMap($lockRecursive, $objectType)
    {
        $objects = $this->getSimpleAssignedObjects($objectType);

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $map = isset($map[ObjectHandler::TREE_MAP_CHILDREN][$objectType]) ?
                $map[ObjectHandler::TREE_MAP_CHILDREN][$objectType] : [];
            $map = array_intersect_key($map, $objects);

            foreach ($map as $childrenIds) {
                foreach ($childrenIds as $parentId => $type) {
                    if ($this->userGroup->isObjectMember($objectType, $parentId) === true) {
                        $objects[$parentId] = $type;
                    }
                }
            }
        }

        return $objects;
    }
}
