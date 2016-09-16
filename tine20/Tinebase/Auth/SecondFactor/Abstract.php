<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
abstract class Tinebase_Auth_SecondFactor_Abstract
{
    protected $_options;
    
    public function __construct($options)
    {
        $this->_options = $options;
    }
    
    abstract public function validate($username, $password);
}
