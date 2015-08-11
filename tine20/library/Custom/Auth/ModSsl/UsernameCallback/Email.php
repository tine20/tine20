<?php

/**
 * Tine 2.0
 *
 * @package     Custom
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Guilherme Striquer Bisotto <guilherme.bisotto@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2015 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Custom_Auth_ModSsl_UsernameCallback_Email extends Custom_Auth_ModSsl_UsernameCallback_Cpf
{
    public function getUsername()
    {
        // TODO: throw an exception in case of error
        return $this->certificate->getEmail();
    }
}