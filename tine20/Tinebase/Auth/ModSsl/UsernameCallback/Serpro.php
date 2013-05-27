<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @todo        throw an exception in case of error
 * 
 */

class Tinebase_Auth_ModSsl_UsernameCallback_Serpro extends Tinebase_Auth_ModSsl_UsernameCallback_Abstract
{
    public function getUsername()
    {
        if ($this->certificate instanceof Tinebase_Auth_ModSsl_Certificate_ICPBrasil && $this->certificate->isPF()) {
            return $this->certificate->getCPF();
        } else {
            // TODO: throw an exception
            return null;
        }
    }
}
