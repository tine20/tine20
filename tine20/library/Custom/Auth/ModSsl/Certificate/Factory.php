<?php

/**
 * Tine 2.0
 *
 * @package     Custom
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2014 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @todo        verify certificate format
 * @todo        support other possible formats pkcs7, pkcs12, etc?
 * 
 */

class Custom_Auth_ModSsl_Certificate_Factory
{
    
    /**
     *
     * @param type $certificate
     * @return \Custom_Auth_ModSsl_Certificate_ICPBrasil|\Custom_Auth_ModSsl_Certificate_X509
     * @throws Custom_Auth_ModSsl_Exception_OpensslNotLoaded 
     */
    public static function buildCertificate($certificate, $dontSkip = FALSE)
    {   
        if(!extension_loaded('openssl'))
        {
            // No suport to openssl.....
            throw new Custom_Auth_ModSsl_Exception_OpensslNotLoaded('Openssl not supported!');
        }
        
        if (!preg_match('/^-----BEGIN CERTIFICATE-----/', $certificate)){
            // TODO: convert to pem
        }
        
        // get Oids from ICPBRASIL
        $icpBrasilData = Custom_Auth_ModSsl_Certificate_ICPBrasil::parseICPBrasilData($certificate);
        if ($icpBrasilData)
        {
            return new Custom_Auth_ModSsl_Certificate_ICPBrasil($certificate, $icpBrasilData, $dontSkip);
        }
        return new Custom_Auth_ModSsl_Certificate_X509($certificate, $dontSkip);
        
    }
    
}
