<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Addressbook http frontend class
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * export contact
     * 
     * @param string $_filter JSON encoded string with contact ids for multi export or contact filter
     * @param string $_format pdf or csv or ...
     */
    public function exportContacts($_filter, $_format = 'pdf')
    {
        $decodedFilter = Zend_Json::decode($_filter);
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
        
        $filter = new Addressbook_Model_ContactFilter($decodedFilter);
        parent::_export($filter, $_format, Addressbook_Controller_Contact::getInstance());
    }
    
    /**
     * Returns all JS files which must be included for Addressbook
     * 
     * @return array array of filenames
     */
    public function getJsFilesToInclude ()
    {
        return array(
            'Addressbook/js/Model.js',
            'Addressbook/js/Addressbook.js',
            'Addressbook/js/ContactGridDetailsPanel.js',
            'Addressbook/js/ContactGrid.js',
            'Addressbook/js/ContactEditDialog.js',
            'Addressbook/js/SearchCombo.js',
        );
    }
}
