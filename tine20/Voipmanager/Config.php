<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * Config class for Voipmanager
 *
 * @package     Voipmanager
 * @subpackage  Config
 *
 */
 class Voipmanager_Config extends Tinebase_Config_Abstract
 {
     /**
      * holds the instance of the singleton
      *
      * @var Voipmanager_Config
      */
     private static $_instance = NULL;
     
     /**
      * (non-PHPdoc)
      * @see tine20/Tinebase/Config/Definition::$_properties
      */
     protected static $_properties = array();
     
     /**
      * server classes
      *
      * @var array
      */
     protected static $_serverPlugins = array(
         'Voipmanager_Server_Plugin' => 50
     );
     
     /**
      * the constructor
      *
      * don't use the constructor. use the singleton
      */
     private function __construct() {}
     
     /**
      * the constructor
      *
      * don't use the constructor. use the singleton
      */
     private function __clone() {}
     
     /**
      * Returns instance of Tinebase_Config
      *
      * @return Voipmanager_Config
      */
     public static function getInstance()
     {
         if (self::$_instance === NULL) {
             self::$_instance = new self();
         }
         
         return self::$_instance;
     }
     
     /**
      * (non-PHPdoc)
      * @see tine20/Tinebase/Config/Abstract::getProperties()
      */
     public static function getProperties()
     {
         return self::$_properties;
     }
 }