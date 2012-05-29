<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * PasswordPolicyViolation exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_PasswordPolicyViolation extends Tinebase_Exception_SystemGeneric
{
    /**
     * @var string _('Password Policy Violation')
     */
    protected $_title = 'Password Policy Violation';
}
