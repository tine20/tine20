<?php
/**
 * Addressbook xls generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Addressbook xls generation class
 * 
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'adb_default_xls';
}
