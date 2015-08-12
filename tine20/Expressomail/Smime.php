<?php
/**
 * Expresso 3.0
 *
 * @package     Expresso
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Serpro (www.serpro.gov.br)
 * @author      Mário César Kolling <mario.kolling@serpro.gov.br>
 *
 */

class Expressomail_Smime extends Zend_Mime
{

    const CONTENT_TYPE_MULTIPART_SIGNED = 'multipart/signed';
    const CONTENT_TYPE_APPLICATION_X_PKCS7_MIME = 'application/x-pkcs7-mime';
    const CONTENT_TYPE_APPLICATION_PKCS7_MIME = 'application/pkcs7-mime';
    const CONTENT_TYPE_APPLICATION_PKCS7_SIGNATURE = 'application/pkcs7-signature';

    const CONTENT_TYPE_PROPERTY_SMIME_TYPE = 'smime-type';
    const CONTENT_TYPE_PROPERTY_NAME = 'name';

    const SMIME_TYPE_SIGNED_DATA = 'signed-data';
    const SMIME_TYPE_ENVELOPED_DATA = 'enveloped-data';
    const SMIME_TYPE_COMPRESSED_DATA = 'compressed-data';
    const SMIME_TYPE_CERTS_ONLY = 'certs-only';

    const TYPE_SIGNED_DATA_VALUE = 1;
    const TYPE_ENVELOPED_DATA_VALUE = 2;
    const TYPE_COMPRESSED_DATA_VALUE = 3;
    const TYPE_CERTS_ONLY_VALUE = 4;
    const TYPE_UNDEFINED_VALUE = 5;

    const PKCS7_SIGNATURE_EXTENSION = '.p7s';
    const PKCS7_ENVELOPED_SIGNED_EXTENSION = '.p7m';
    const PKCS7_CERTS_EXTENSION = '.p7c';
    const PKCS7_COMPRESSED_EXTENSION = '.p7z';

    /**
     * Verify a integrity of a signed message
     *
     * @return array
     */
    public static function verify($rawHeaders, $rawBody, $fromEmail, $smime) // Only message Integrity
    {
        $return = array();
        $path = Tinebase_Core::getTempDir();
        $translate = Tinebase_Translation::getTranslation('Expressomail');
        $ret_type = False;

        if (!empty($rawHeaders) && !empty($rawBody))
        {
            $msg = $rawHeaders . $rawBody;
            $ret_type = null;
            if ($smime == Expressomail_Smime::TYPE_ENVELOPED_DATA_VALUE || $smime == Expressomail_Smime::TYPE_SIGNED_DATA_VALUE) {
                $ret_type = self::verify_p7m($rawBody);
                // Encrypted Message ??
                if ($ret_type == 'cipher') {
                    $return['success'] = false;
                    $return['msgs'] = array("Encrypted Message.");
                    $return['ret_type'] = $ret_type;
                    // return raw msg to others process.
                    $return['content'] = $msg;
                    return $return;
                }
            }
            $config = Tinebase_Config::getInstance()->get('modssl');

            // creates temporary files
            $temporary_files = array();
            $msgTempFile = self::generateTempFilename($temporary_files, $path);
            if (!self::writeTo($msgTempFile, $msg))
            {
                $return['success'] = false;
                $return['msgs'] = array("Coudn't write temporary files!");
            }

            $certificateTempFile = self::generateTempFilename($temporary_files, $path);
            $contentTempFile = self::generateTempFilename($temporary_files, $path);

            // do verification
            $result = openssl_pkcs7_verify($msgTempFile,0,$certificateTempFile, array($config->casfile), $config->casfile,$contentTempFile);

            if (is_file($certificateTempFile)) {
                $aux_certificate = file_get_contents($certificateTempFile);
            }
            else {
                $aux_certificate = '';
            }

            if ($aux_certificate != '') {
                // E-mail validation is unskipable, we always verify chain and crls
                $certificate = Custom_Auth_ModSsl_Certificate_Factory::buildCertificate($aux_certificate, TRUE);
            }
            else {
                // try get certificate from message (other way) ....
                $certificate = self::pullCertificateFromMessage($msgTempFile);
            }

            if ($result === -1 || !$result)
            {
                // error on openssl_pkcs7_verify() call
                $return['success'] = false;
                $return['msgs'] = self::getOpensslErrors();

                if ($certificate) {
                    $return['certificate'] = $certificate->toArray();
                }
            }
            else {
                $mailMismatch = ($fromEmail !== $certificate->getEmail());

                if ($certificate->isValid()) {
                    if (!$mailMismatch) {
                        $return['success'] = true;
                        $return['msgs'] = array('Message Verification Successful');
                    }

                    // If certificate is valid store it in database
                    $controller = Addressbook_Controller_Certificate::getInstance();
                    try {
                        $controller->create(new Addressbook_Model_Certificate($certificate));
                    } catch (Tinebase_Exception_Duplicate $e) {
                        // Fail silently if certificate already exists
                    }
                } else {
                    $return['success'] = false;
                    $return['msgs'] = $certificate->getStatusErrors();
                    if ($mailMismatch) $return['msgs'][] = $translate->_('Sender\'s email is different from Digital Certificate\'s email');
                }
                $return['certificate'] = $certificate->toArray();
            }
            if (is_file($contentTempFile)) {
                // get original msg
                $return['content'] = file_get_contents($contentTempFile);
            }
            if ($ret_type) {
                $return['ret_type'] = $ret_type;
            }
            self::removeTempFiles($temporary_files);

            return $return;
        }
        else
        {
            return array(
                'success'   => false,
                'msgs'       => array("Empty message"),
            );
        }
    }

    public static function writeTo($file, $content)
    {
        return file_put_contents($file, $content);
    }

    public static function generateTempFilename(&$tab_arqs, $path)
    {

        $list = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $N = $list[rand(0,count($list)-1)].date('U').$list[rand(0,count($list)-1)].RAND(12345,9999999999).$list[rand(0,count($list)-1)].$list[rand(0,count($list)-1)].RAND(12345,9999999999).'.tmp';
        $aux = $path.'/'.$N;
        array_push($tab_arqs ,$aux);
        return  $aux;
    }

    public static function verify_p7m($msg)
    {
        // search oids:
        // 1.2.840.113549.1.7.2     digital signature
        // 1.2.840.113549.1.7.3     cipher
        $aux1 = explode('MIME-Version: 1.0',$msg);
        $aux2 = explode('filename="smime.p7m"',$aux1[count($aux1)-1]);
        $aux3 = str_replace(' ','',$aux2[count($aux2)-1]);
        $p7m_formato_der = base64_decode($aux3);
        $oid_hexa = self::OIDtoHex('1.2.840.113549.1.7.2');       // converte oid de texto para hexadecimal ...
        $parts = explode($oid_hexa,$p7m_formato_der);    // Faz o split pela oid...
        if(count($parts)>1)
        {
            return 'signature' ;
        }
        $oid_hexa = self::OIDtoHex('1.2.840.113549.1.7.3');
        $parts = explode($oid_hexa,$p7m_formato_der);    // Faz o split pela oid...
        if(count($parts)>1)
        {
            return 'cipher' ;
        }
        return False;
    }

    public static function xBase128($ab,$q,$flag )
    {
        $abc = $ab;
        if( $q > 127 )
        {
            $abc = self::xBase128($abc, floor($q / 128), 0 );
        }
        $q = $q % 128;
        if( $flag)
        {
            $abc[] = $q;
        }
        else
        {
            $abc[] = 0x80 | $q;
        }
        return $abc;
    }

    public static function OIDtoHex($oid)
    {
        $abBinary = array();
        $partes = explode('.',$oid);
        $n = 0;
        $b = 0;
        for($n = 0; $n < count($partes); $n++)
        {
        if($n==0)
        {
            $b = 40 * $partes[$n];
        }
        elseif($n==1)
        {
            $b +=  $partes[$n];
            $abBinary[] = $b;
        }
        else
        {
            $abBinary = self::xBase128($abBinary, $partes[$n], 1 );
        }
        }
        $value =chr(0x06) . chr(count($abBinary));
        foreach($abBinary as $item)
        {
            $value .= chr($item);
        }
        return $value;
    }

    private static function getOpensslErrors()
    {
        $translateMail = Tinebase_Translation::getTranslation('Expressomail');
        $errors = array();
        while ($error = openssl_error_string())
        {
            if (preg_match('/error:21071065:PKCS7/', $error)) {
                $errors[] = $translateMail->_('Message Integrity Verification Failure');
            } else if (preg_match('/error:21075069:PKCS7/', $error)) {
                continue;
                // $errors[] = $translateMail->_('Signature Failure');
            } else {
                $errors[] = $translateMail->_($error);
            }
        }
        return $errors;
    }

    private static function removeTempFiles($tab_arqs)
    {
        foreach($tab_arqs as $arquivo )
        {
            if(file_exists($arquivo))
            {
                unlink($arquivo);
            }
        }
    }

    private static function pullCertificateFromMessage($msgTempFile)
	{
            $return = false;
            $path = Tinebase_Core::getTempDir();
//            if(!$msg)
//            {
//                return $return;
//            }
            $w='';
            $output = array();
            $w = exec('cat ' . $msgTempFile . ' | openssl smime -pk7out | openssl pkcs7 -print_certs',$output);
            if(!$w=='')
            {
                return $return;
            }
            $aux1 = '';
            //  string with output from command...
            foreach($output as $line)
            {
                $aux1 .= $line.chr(0x0A);
            }
            // certificates array..
            $aux2 = explode('-----BEGIN CERTIFICATE-----',$aux1);
            array_shift($aux2);
            // fix certificates..
            $aux5 = array();
            foreach($aux2 as $item)
            {
                $aux3 = explode('-----END CERTIFICATE-----',$item);
                $aux4 = '-----BEGIN CERTIFICATE-----' . $aux3[0] . '-----END CERTIFICATE-----';
                $aux5[] = $aux4;
            }
            // only one no CA certificate ....
            foreach($aux5 as $item)
            {
                $Data_cert = Custom_Auth_ModSsl_Certificate_Factory::buildCertificate($item, FALSE);
                if(!$Data_cert->isCA())
                    {
                        $return = $Data_cert;
                        break;
                    }
            }
            return $return;
	}
}

?>
