<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Addressbook_Model_ListRole extends Tinebase_Record_Simple
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * @var null|Tinebase_Record_Interface
     */
    protected static $_parent = null;

    /**
     * returns an array containing the parent neighbours relation objects or record(s) (ids) in the key 'parents'
     * and containing the children neighbours in the key 'children'
     *
     * @return array
     * @throws Tinebase_Exception_Record_StopPathBuild
     */
    public function getPathNeighbours()
    {
        if (!isset(static::$_parent)) {
            throw new Tinebase_Exception_Record_StopPathBuild();
        }

        $memberRolesBackend = Addressbook_Controller_List::getInstance()->getMemberRolesBackend();
        $contactController = Addressbook_Controller_Contact::getInstance();
        $listController = Addressbook_Controller_List::getInstance();

        $filter = new Addressbook_Model_ListMemberRoleFilter(array(
            array('field' => 'list_id',      'operator' => 'equals', 'value' => static::$_parent->getId()),
            array('field' => 'list_role_id', 'operator' => 'equals', 'value' => $this->getId())
        ));

        $parents = array();
        $children = array();
        /** @var Addressbook_Model_ListMemberRole $listMemberRole */
        foreach($memberRolesBackend->search($filter) as $listMemberRole)
        {
            $children[$listMemberRole->contact_id] = $listMemberRole->contact_id;
            $parents[$listMemberRole->list_id] = $listMemberRole->list_id;
        }

        if (!empty($parents)) {
            $parents = $listController->getMultiple($parents, true)->asArray();
        }
        if (!empty($children)) {
            $children = $contactController->getMultiple($children, true)->asArray();
        }

        return array(
            'parents'  => $parents,
            'children' => $children
        );
    }

    public static function setParent(Tinebase_Record_Interface $_record = null)
    {
        static::$_parent = $_record;
    }

    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return true;
    }

    public function getTitle()
    {
        return $this->name;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return true;
    }
}
