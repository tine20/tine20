<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Id
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * 
 * filters one or more ids
 */
class Addressbook_Model_ContactIdFilter extends Tinebase_Model_Filter_Id
{
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (is_array($this->_value)) {
            foreach($this->_value as $key => $value) {
                if ($value === Addressbook_Model_Contact::CURRENTCONTACT) {
                    $this->_value[$key] = Tinebase_Core::getUser()->contact_id;
                }
            }
        } else if ($this->_value === Addressbook_Model_Contact::CURRENTCONTACT) {
            $this->_value = Tinebase_Core::getUser()->contact_id;
        }
        
        parent::appendFilterSql($_select, $_backend);
    }
    
    /**
     * resolves a record
     * 
     * @param string $value
     * @return array|string
     */
    protected function _resolveRecord($value)
    {
        if ($value === Addressbook_Model_Contact::CURRENTCONTACT) {
            $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId(), TRUE)->toArray();
        } else {
            $contact = parent::_resolveRecord($value);
        }
        
        return $contact;
    }
}
