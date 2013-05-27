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
 * @todo        verify certificate format
 * @todo        support other possible formats pkcs7, pkcs12, etc?
 * 
 */

class Tinebase_Auth_ModSsl_Certificate_Factory
{
    
    /**
     *
     * @param type $certificate
     * @return \Tinebase_Auth_ModSsl_Certificate_ICPBrasil|\Tinebase_Auth_ModSsl_Certificate_X509
     * @throws Tinebase_Auth_ModSsl_Exception_OpensslNotLoaded 
     */
    public static function buildCertificate($certificate)
    {   
        if(!extension_loaded('openssl'))
        {
            // No suport to openssl.....
            throw new Tinebase_Auth_ModSsl_Exception_OpensslNotLoaded('Openssl not supported!');
        }
        
        if (!preg_match('/^-----BEGIN CERTIFICATE-----/', $certificate)){
            // TODO: convert to pem
        }
        
        // get Oids from ICPBRASIL
        $icpBrasilData = Tinebase_Auth_ModSsl_Certificate_ICPBrasil::parseICPBrasilData($certificate);
        if ($icpBrasilData)
        {
            return new Tinebase_Auth_ModSsl_Certificate_ICPBrasil($certificate, $icpBrasilData);
        }
        return new Tinebase_Auth_ModSsl_Certificate_X509($certificate);
        
    }
    
}
