<?php

/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */
 

/**
 * backend to handle Snom template
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Template
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    

	/**
	 * the constructor
	 */
    public function __construct($_db = NULL)
    {
        if($_db instanceof Zend_Db_Adapter_Abstract) {
            $this->_db = $_db;
        } else {
            $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        }
    }
      
	/**
	 * search templates
	 * 
     * @param Voipmanager_Model_SnomTemplateFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomTemplate
	 */
    public function search(Voipmanager_Model_SnomTemplateFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_templates');
            
        $_pagination->appendPagination($select);

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(model LIKE ? OR description LIKE ? OR name LIKE ?)', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }
       
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomTemplate', $rows);
		
        return $result;
	}  
  
      
	/**
	 * get Template by id
	 * 
     * @param string|Voipmanager_Model_SnomTemplate $_id
	 * @return Voipmanager_Model_SnomTemplate
	 */
    public function get($_id)
    {	
        $templateId = Voipmanager_Model_SnomTemplate::convertSnomTemplateIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_templates')
            ->where($this->_db->quoteInto('id = ?', $templateId));
        
        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('template not found');
        }

        $result = new Voipmanager_Model_SnomTemplate($row);
        
        return $result;
	}
	   
    /**
     * add new template
     *
     * @param Voipmanager_Model_SnomTemplate $_template the template data
     * @return Voipmanager_Model_SnomTemplate
     */
    public function create(Voipmanager_Model_SnomTemplate $_template)
    {
        if (! $_template->isValid()) {
            throw new Exception('invalid template');
        }

        if ( empty($_template->id) ) {
            $_template->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $template = $_template->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_templates', $template);

        return $this->get($_template->getId());
    }
    
    /**
     * update an existing template
     *
     * @param Voipmanager_Model_SnomTemplate $_template the template data
     * @return Voipmanager_Model_SnomTemplate
     */
    public function update(Voipmanager_Model_SnomTemplate $_template)
    {
        if (! $_template->isValid()) {
            throw new Exception('invalid template');
        }
        $templateId = $_template->getId();
        $templateData = $_template->toArray();
        unset($templateData['id']);

        $where = array($this->_db->quoteInto('id = ?', $templateId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_templates', $templateData, $where);
        
        return $this->get($templateId);
    }    


    /**
     * delete template(s) identified by template id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $templateId = Voipmanager_Model_SnomTemplate::convertSnomTemplateIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $templateId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_templates', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
	        

}
