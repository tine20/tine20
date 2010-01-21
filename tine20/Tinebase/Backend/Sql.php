<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * default sql backend
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * the constructor
     *
     * @param string $_modelName
     * @param string $_tableName
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param string $_tablePrefix (optional)
     * @param boolean $_modlogActive (optional)
     * @param boolean $_useSubselectForCount (optional)
     */
    public function __construct ($_modelName, $_tableName, $_dbAdapter = NULL, $_tablePrefix = NULL, $_modlogActive = NULL, $_useSubselectForCount = NULL)
    {
        parent::__construct($_dbAdapter, $_modelName, $_tableName, $_tablePrefix, $_modlogActive, $_useSubselectForCount);
    }
}
