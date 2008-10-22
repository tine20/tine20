<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add possiblity to change salutations
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_Salutation extends Tinebase_Application_Controller_Abstract
{
    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Controller_Salutation
     */
    private static $_instance = NULL;
        
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Salutation
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Salutation;
        }
        
        return self::$_instance;
    }
            
    /**
     * get salutations
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Addressbook_Model_Salutation
     */
    public function getSalutations($_sort = 'id', $_dir = 'ASC')
    {
        $backend = Addressbook_Backend_Salutation::getInstance();        
        $result = $backend->getAll($_sort, $_dir);

        return $result;    
    }
}
