<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
class OpenDocument_SpreadSheet_Cell
{
    static function factory($_cellType, $_cellValue = null)
    {
        $cellType = ucfirst($_cellType);
        switch($cellType) {
            case 'Date':
            case 'Float':
            case 'String':
                $className = 'OpenDocument_SpreadSheet_Cell_' . $cellType;
                $class = new $className($_cellValue);
                break;
         
            default:
                throw new Exception('unsupported cell type: ' . $_cellType);
                break;
        }
        
        return $class;
    }
}