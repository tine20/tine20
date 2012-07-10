<?php
/**
 * FAQ Controller for SimpleFAQ application
 * 
 * @package     SimpleFAQ
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Faq Controller Class
 *
 * @package     SimpleFAQ
 * @subpackage  Controller
 */
class SimpleFAQ_Controller_Faq extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'SimpleFAQ';
        $this->_backend = new SimpleFAQ_Backend_Faq();
        $this->_modelName = 'SimpleFAQ_Model_Faq';
        $this->_purgeRecords = FALSE;
        $this->_doContainerACLChecks = TRUE;
    }

     /**
      * holds the instance of the singleton
      *
      * @var SimpleFAQ_Controller_Faq
      */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return ExampleApplication_Controller_ExampleRecord
     */
    public static function getInstance()
    {
        if(self::$_instance === NULL) {
           self::$_instance = new SimpleFAQ_Controller_Faq();
        }
        
        return self::$_instance;
    }

}
