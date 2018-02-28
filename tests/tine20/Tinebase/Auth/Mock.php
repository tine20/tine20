<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Auth_Mock extends Tinebase_Auth_Adapter_Abstract
{
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if ($this->_options['url'] === 'https://localhost/validate/check' && $this->_credential === 'phil' && $this->_identity === 'phil') {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_identity);
        } else {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $this->_identity);
        }
    }
}
