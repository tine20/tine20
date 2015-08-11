<?php

/**
 * Tine 2.0
 *
 * @package     Custom
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2015 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Custom_Auth_ModSsl_UsernameCallback_Cpf extends Custom_Auth_ModSsl_UsernameCallback_Standard
{
    /**
     * (non-PHPdoc)
     * @see Custom_Auth_ModSsl_UsernameCallback_Abstract::getUsername()
     */
    public function getUsername()
    {
        if ($this->certificate instanceof Custom_Auth_ModSsl_Certificate_ICPBrasil && $this->certificate->isPF()) {
            return $this->certificate->getCPF();
        } else {
            // TODO: throw an exception
            return null;
        }
    }
}
