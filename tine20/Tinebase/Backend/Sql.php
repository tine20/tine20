<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * allowed options:
     *  - modelName
     *  - tableName
     *  - tablePrefix
     *  - modlogActive
     *
     * @param array $_options (optional)
     * @param Zend_Db_Adapter_Abstract $_db (optional) the db adapter
     * @see Tinebase_Backend_Sql_Abstract::__construct()
     */
    public function __construct($_options = array(), $_dbAdapter = NULL)
    {
        parent::__construct($_dbAdapter, $_options);
    }
}
