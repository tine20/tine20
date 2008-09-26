<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the phone application
 * 
 * @package Phone
 */
class Phone_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Phone';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Phone/js/Phone.js'
        );
    }
    
    /**
     * Returns initial data which is send to the app at creation time.
     *
     * When the mainScreen is created, Tinebase_Http_Controller queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial data objects are just javascript variables declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * variable names get prefixed with Tine.<applicationname>
     * 
     * - this function returns the user phones
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getInitialMainScreenData()
    {   
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__);
    
        $accountId = Zend_Registry::get('currentAccount')->getId();
        $json = new Phone_Json();
        
        $initialData = array(
            'Phones' => $json->getUserPhones($accountId)
        );
        
        return $initialData;
    }
}
