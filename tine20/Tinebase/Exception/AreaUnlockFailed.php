<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * AreaLocked exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_AreaUnlockFailed extends Tinebase_Exception_AreaLocked
{
    /**
     * @var string _('Area could not be unlocked')
     */
    protected $_title = 'Area could not be unlocked';

    /**
     * Tinebase_Exception_AreaLocked constructor.
     * @param null $_message
     * @param int $_code
     */
    public function __construct($_message, $_code = 631)
    {
        parent::__construct($_message, $_code);
    }
}
