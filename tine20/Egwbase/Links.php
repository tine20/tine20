<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Links
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * this class handles linking between applications
 * 
 */
class Egwbase_Links
{
    protected $_linksTable;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Links
     */
    private static $_instance = NULL;
    
/**
     * the constructor
     * 
     */
    private function __construct() {
        $this->_linksTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'links'));
    }
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Links
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Egwbase_Links;
        }
        
        return self::$_instance;
    }
    
    public function getLinks($_applicationName1, $_recordId, $_applicationName2 = NULL)
    {
        $recordId = (int)$_recordId;
        if($recordId != $_recordId) {
            throw new InvalidArgumentException('$_recordId must be integer');
        }
                
        $db = Zend_Registry::get('dbAdapter');
        
        $where1 = $db->quoteInto('link_app1 = ?', $_applicationName1) . ' AND ' . $db->quoteInto('link_id1 = ?', $recordId);
        if($_applicationName2 !== NULL) {
            $where1 .= ' AND ' . $db->quoteInto('link_app2 = ?', $_applicationName2);
        }
        $where2 = $db->quoteInto('link_app2 = ?', $_applicationName1) . ' AND ' . $db->quoteInto('link_id2 = ?', $recordId);
        if($_applicationName2 !== NULL) {
            $where2 .= ' AND ' . $db->quoteInto('link_app1 = ?', $_applicationName2);
        }
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'links')
            ->where($where1)
            ->orWhere($where2);
        
        //error_log($select->__toString());
        
        $stmt = $db->query($select);
        
        $result = array();
        
        while ($row = $stmt->fetch()) {
            if($row['link_app1'] == $_applicationName1 && $row['link_id1'] == $recordId) {
                $result[] = array(
                    'applicationName'   => $row['link_app2'],
                    'recordId'          => $row['link_id2'],
                    'remark'            => $row['link_remark']
                );
            } else {
                $result[] = array(
                    'applicationName'   => $row['link_app1'],
                    'recordId'          => $row['link_id1'],
                    'remark'            => $row['link_remark']
                );
            }
        }
        
        return $result;
    }
    
    public function deleteLinks($_applicationName1, $_recordId, $_applicationName2 = NULL, $_remark = NULL)
    {
        $recordId = (int)$_recordId;
        if($recordId != $_recordId) {
            throw new InvalidArgumentException('$_recordId must be integer');
        }
                
        $db = Zend_Registry::get('dbAdapter');
        
        $where  = $db->quoteInto('((link_app1 = ?', $_applicationName1) . ' AND ' . $db->quoteInto('link_id1 = ?', $recordId);
        if($_applicationName2 !== NULL) {
            $where .= ' AND ' . $db->quoteInto('link_app2 = ?', $_applicationName2);
        }
        
        $where .= ') OR ';
        
        $where .= $db->quoteInto('(link_app2 = ?', $_applicationName1) . ' AND ' . $db->quoteInto('link_id2 = ?', $recordId);
        if($_applicationName2 !== NULL) {
            $where .= ' AND ' . $db->quoteInto('link_app1 = ?', $_applicationName2);
        }
        
        $where .= '))';
        
        if($_remark !== NULL) {
            $where .= ' AND ' . $db->quoteInto('link_remark = ?', $_remark);
        }
        
        $this->_linksTable->delete($where);
    }
    
    /**
     * Enter description here...
     *
     * @param string $_applicationName1
     * @param int $_recordId1
     * @param string $_applicationName2
     * @param array $_recordId2
     * @param string $_remark
     * @todo validate parameters
     */
    public function setLinks($_applicationName1, $_recordId1, $_applicationName2, array $_recordId2, $_remark)
    {
        $recordId1 = (int)$_recordId1;
        if($recordId1 != $_recordId1) {
            throw new InvalidArgumentException('$_recordId1 must be integer');
        }
        
        $this->deleteLinks($_applicationName1, $_recordId1, $_applicationName2, $_remark);
        
        foreach($_recordId2 as $recordId) {
            $this->addLink($_applicationName1, $_recordId1, $_applicationName2, $recordId, $_remark);
        }
    }
    
    public function addLink($_applicationName1, $_recordId1, $_applicationName2, $_recordId2, $_remark)
    {
        $data = array(
            'link_app1'     => $_applicationName1,
            'link_id1'      => $_recordId1,
            'link_app2'     => $_applicationName2,
            'link_id2'      => $_recordId2,
            'link_remark'   => $_remark
        );
        $this->_linksTable->insert($data);
    }
}