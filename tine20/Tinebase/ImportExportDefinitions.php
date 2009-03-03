<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_ImportExportDefinition extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_modlogActive = TRUE;
        
        parent::__construct(SQL_TABLE_PREFIX . 'importexport_definitions', 'Tinebase_Model_ImportExportDefinition');
    }
}
