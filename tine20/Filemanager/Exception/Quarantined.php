<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @todo        extend Tinebase_Exception_Data
 */

/**
 * Filemanager exception
 *
 * @package     Filemanager
 * @subpackage  Exception
 */
class Filemanager_Exception_Quarantined extends Filemanager_Exception
{
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'file_quarantined', $_code = 904) {

        parent::__construct($_message, $_code);
    }
}
