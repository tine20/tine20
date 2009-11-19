<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add role members and rights
 * @todo        extend Tinebase_Model_Filter_FilterGroup
 */


/**
 * Task Filter Class
 * @package    Tinebase
 * @subpackage Tags
 */
class Tinebase_Model_TagFilter extends Tinebase_Record_Abstract
{
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Alnum'),

        'owner'                => array('allowEmpty' => true),
        'application'          => array('allowEmpty' => true),
        'name'                 => array('allowEmpty' => true),
        'description'          => array('allowEmpty' => true),
        'type'                 => array('presence'   => 'required',
                                        'allowEmpty' => true,
                                        'InArray'    => array(Tinebase_Model_Tag::TYPE_PERSONAL, Tinebase_Model_Tag::TYPE_SHARED),
                                        'default'    => ''
                                  ),
        'grant'                => array('presence'   => 'required',
                                        'allowEmpty' => false,
                                        'InArray'    => array(Tinebase_Model_TagRight::VIEW_RIGHT, Tinebase_Model_TagRight::USE_RIGHT),
                                        'default'    => Tinebase_Model_TagRight::VIEW_RIGHT
                                  ),
    );
    
    /**
     * Returns a select object according to this filter
     * 
     * @return Zend_Db_Select
     */
    public function getSelect()
    {
        $db = Tinebase_Core::getDb();
        $select = $db->select()
            ->from (array('tags' => SQL_TABLE_PREFIX . 'tags'))
            ->where($db->quoteIdentifier('is_deleted') . ' = 0')
            //->order('type', 'DESC')
            ->order('name', 'ASC');
        
        if (!empty($this->application)) {
            $applicationId = Tinebase_Application::getInstance()->getApplicationByName($this->application)->getId();
            
            $select->join(
                array('context' => SQL_TABLE_PREFIX . 'tags_context'), 
                $db->quoteIdentifier('tags.id') . ' = ' . $db->quoteIdentifier('context.tag_id'),
                array()
            )->where($db->quoteInto($db->quoteIdentifier('context.application_id') . ' IN (0, ?)', $applicationId));
        }
        
        $orWhere = array();
        if (!empty($this->name)) {
            $orWhere[] = $db->quoteInto($db->quoteIdentifier('tags.name') . ' LIKE ?', $this->name);
        }
        if (!empty($this->description)) {
            $orWhere[] = $db->quoteInto($db->quoteIdentifier('tags.description') . ' LIKE ?', $this->description);
        }
        if (! empty($orWhere)) {
            $select->where(implode(' OR ', $orWhere));
        }
        
        if ($this->type) {
            $select->where($db->quoteInto($db->quoteIdentifier('tags.type') . ' = ?', $this->type));
        }
        return $select;
    }
    
    //protected $_datetimeFields = array(
    //);
    

}