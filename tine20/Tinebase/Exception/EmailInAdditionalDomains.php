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
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_EmailInAdditionalDomains extends Tinebase_Exception_ProgramFlow
{
    /**
     * @var string
     */
    protected $_appName = 'Tinebase';
    
    public function __construct($_message = 'email address in additional domains', $_code=601)
    {
        parent::__construct($_message, $_code);
    }
}
