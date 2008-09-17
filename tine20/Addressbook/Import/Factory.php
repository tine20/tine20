<?php
/**
 * import factory class for the addressbook
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
/**
 * import factory class for the addressbook
 * 
 * An instance of the addressbook import class should be created using this class
 * $importer = Addressbook_Import_Factory::factory(Addressbook_Import::$type);
 * currently implemented import classes: Addressbook_Backend_Factory::Csv
 * 
 * @package     Addressbook
 * @subpackage  Import
 */
class Addressbook_Import_Factory
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const CSV = 'Csv';

    /**
     * factory function to return a selected import class
     *
     * @param string $type
     * @return Addressbook_Import_Interface
     */
    static public function factory ($type)
    {
        switch ($type) {
            case self::CSV:
                $instance = Addressbook_Import_Csv::getInstance();
                break;
            default:
                throw new Exception('unknown type');
                break;
        }
        return $instance;
    }
}    
