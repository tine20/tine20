<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk peer sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_SipPeer extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db
     */
    public function __construct($_db = NULL)
    {
        parent::__construct(SQL_TABLE_PREFIX . 'asterisk_sip_peers', 'Voipmanager_Model_AsteriskSipPeer', $_db);
    }
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select $_select current where filter
     * @param  Voipmanager_Model_AsteriskSipPeerFilter $_filter the string to search for
     */
    protected function _addFilter(Zend_Db_Select $_select, Voipmanager_Model_AsteriskSipPeerFilter $_filter)
    {
        if(!empty($_filter->query)) {
            $search_values = explode(" ", $_filter->query);
            
            $search_fields = array('callerid', 'name', 'ipaddr');
            $fields = '';
            foreach($search_fields AS $search_field) {
                $fields .= " OR " . $search_field . " LIKE ?";    
            }
            $fields = substr($fields, 3);
        
            foreach($search_values AS $search_value) {
                $_select->where($this->_db->quoteInto('('.$fields.')', '%' . trim($search_value) . '%'));                            
            }
        }
        
        if(!empty($_filter->name)) {
            $_select->where($this->_db->quoteInto('name = ?', $_filter->name));
        }

        if(!empty($_filter->context)) {
            $_select->where($this->_db->quoteInto('context = ?', $_filter->context));
        }

        if(!empty($_filter->username)) {
            $_select->where($this->_db->quoteInto('username = ?', $_filter->username));
        }
    }            
}
