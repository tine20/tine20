<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * convert factory class
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Factory
{
    /**
     * json converter type
     * 
     * @var string
     */
    const TYPE_JSON     = 'Json';
    
    /**
     * factory function to return a selected converter backend class
     *
     * @param   string|Tinebase_Record_Interface $_record record object or class name
     * @param   string $_type
     * @return  Tinebase_Convert_Interface
     * @throws  Tinebase_Exception_NotImplemented
     */
    static public function factory($_record, $_type = self::TYPE_JSON)
    {
        switch ($_type) {
            case self::TYPE_JSON:
                $recordClass = ($_record instanceof Tinebase_Record_Interface) ? get_class($_record) : $_record;
                $converterClass = str_replace('Model', 'Convert', $recordClass);
                $converterClass .= '_Json';
                
                $converter = class_exists($converterClass) ? new $converterClass($recordClass) : new Tinebase_Convert_Json($recordClass);
                return $converter;
                 
                break;
            default:
                throw new Tinebase_Exception_NotImplemented('type ' . $_type . ' not supported yet.');
        }
    }
}
