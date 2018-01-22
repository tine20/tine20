<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * SecondFactorRequired exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_SecondFactorRequired extends Tinebase_Exception_SystemGeneric
{
    /**
     * @var string _('Second Factor Required')
     */
    protected $_title = 'Second Factor Required';

    public function __construct($_message, $_code=630)
    {
        parent::__construct($_message, $_code);
    }
}
