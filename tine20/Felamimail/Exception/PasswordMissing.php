<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Felamimail_Exception_PasswordMissing exception
 * 
 * @package     Felamimail
 * @subpackage  Exception
 */
class Felamimail_Exception_PasswordMissing extends Tinebase_Exception_SystemGeneric
{
    /**
     * @var string _('shared / adb_list accounts need to have a password set')
     */
    protected $_title = 'shared / adb_list accounts need to have a password set';

    public function __construct($_message, $_code=925)
    {
        parent::__construct($_message, $_code);
    }
}
