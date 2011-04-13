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
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson) {
            if (is_array($result['value'])) {
                foreach ($result['value'] as $key => $value) {
                    $result['value'][$key] = $this->_resolveContact($value);
                }
            } else {
                $result['value'] = $this->_resolveContact($result['value']);
            }
        }
        
        return $result;
    }
    
    /**
     * resolves a contact
     * 
     * @param string $value
     * @return array
     */
    protected function _resolveContact($value)
    {
            if ($value === Addressbook_Model_Contact::CURRENTCONTACT) {
                $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId(), TRUE)->toArray();
            } else {
                try {
                    $contact = Addressbook_Controller_Contact::getInstance()->get($value)->toArray();
                } catch (Exception $e) {
                    $contact = $value;
                }
            }
            
            return $contact;
    }
}
