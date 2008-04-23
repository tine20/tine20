<?php
/**
 * Tine 2.0
 *
 * @package     Dialer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the dialer application
 *
 * @package     Dialer
 */
class Dialer_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Dialer';
    
    /**
     * delete multiple contacts
     *
     * @param array $_contactIDs list of contactId's to delete
     * @return array
     */
    public function dialNumber($number)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Dialer_Controller::getInstance()->dialNumber($number);
        
        return $result;
    }
}