<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @author     Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author     Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright  Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright  Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle X509 certificates
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @todo       parse authorityInfoAccess, caissuer and ocsp
 * @todo       parse authorityKeyIdentifier
 * @todo       phpdoc of all methods
 */
class Zend_Auth_Adapter_ModSsl_Certificate_X509 extends Zend_Auth_Adapter_ModSsl_Certificate_Abstract
{
    protected $certificate;
    protected $casfile;
    protected $crlspath;
    protected $validFrom    = null;
    protected $validTo      = null;
    protected $canSign      = false;
    protected $canEncrypt   = false;
    protected $email        = null;
    protected $ca           = false;
    protected $authorityKeyIdentifier = null;
    protected $crlDistributionPoints  = null;
    protected $authorityInfoAccess    = null;
    protected $status       = array(
                                  'isValid' => false,
                                  'errors'  => array()
                              );
    
    protected $_parsedCertificate     = null;
    
    /**
     * 
     * @param string $certificate
     */
    public function __construct(array $options = array(), $certificate)
    {
        parent::__construct($options, $certificate);
        
        $this->casfile  = isset($this->_options['casfile'])  && strtolower($this->_options['casfile'])  !== 'skip' ? $this->_options['casfile']  : null;
        $this->crlspath = isset($this->_options['crlspath']) && strtolower($this->_options['crlspath']) !== 'skip' ? $this->_options['crlspath'] : null;
        
        $this->_parsedCertificate = openssl_x509_parse($this->_certificate['SSL_CLIENT_CERT']);
        
        $this->_parseExtensions($this->_parsedCertificate['extensions']);
    }
    
    /**
     * parse certificate extensions
     * 
     * @param array $extensions
     */
    protected function _parseExtensions($extensions)
    {
        foreach ($extensions as $extension => $value) {
            $matches = array();
            
            switch ($extension) {
                case 'subjectAltName':
                    if (preg_match('/email:(\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b)/i', $value, $matches)) {
                        $this->email = $matches[1];
                    }
                    
                    break;
                    
                case 'basicConstraints' :
                    if (preg_match('/\bCA:(FALSE|TRUE)\b/', $value, $matches)) {
                        $this->ca = $matches[1] == 'TRUE' ? TRUE : FALSE;
                    }
                    
                    break;
                    
                case 'crlDistributionPoints' :
                    $lines = explode(chr(0x0A), trim($value));
                    
                    foreach ($lines as &$line) {
                        preg_match('/URI:/', $line, $matches);
                        $line = preg_replace('/URI:/', '', $line);
                    }
                    
                    $this->crlDistributionPoints = $lines;
                    
                    break;
                
//                case 'authorityKeyIdentifier' :
//                    if (preg_match('/\bkeyid:(\b([A-F0-9]{2}:)+[[A-F0-9]{2}]\b)/', $value, $matches))
//                    {
//                        $tmp = '';
//                    }
//                    break;
//                 // TODO: ocsp
//                case 'authorityInfoAccess' :
//                    if (preg_match('/\bCA Issuers - URI:(http(?:s)?\:\/\/[a-zA-Z0-9\-]+(?:\.[a-zA-Z0-9\-]+)*\.[a-zA-Z]{2,6}(?:\/?|(?:\/[\w\-]+)*)(?:\/?|\/\w+\.[a-zA-Z]{2,4}(?:\?[\w]+\=[\w\-]+)?)?(?:\&[\w]+\=[\w\-]+)*\b)/', $value, $matches))
//                    {
//                        $tmp = '';
//                    }
//                    break;
            }
        }
    }
    
    /**
     * parse certificate purpose
     * 
     * @param array $purposes
     */
    protected function _parsePurpose($purposes)
    {
        foreach ($purposes as $purpose) {
            switch ($purpose[2]) {
                case 'smimesign' :
                    $this->canSign    = $purpose[0] == 1 ? true : false;
                    
                    break;
                    
                case 'smimeencrypt' :
                    $this->canEncrypt = $purpose[0] == 1 ? true : false;
                    
                    break;
            }
        }
    }
    
    /**
     * check certificate validity
     */
    protected function _validityCheck() 
    {
        if (!is_file($this->casfile)) {
            $this->status['errors'][] = 'Invalid Certificate .(CA-01)';  //'CAs file not found.';
            $this->status['isValid'] = false;
            
            return;
        }
        
        $temporary_files = array();
        $certTempFile = self::generateTempFilename($temporary_files, Tinebase_Core::getTempDir());
        self::writeTo($certTempFile,$this->certificate);
        
        $out = array();
        // certificate verify ...
        $w = exec('openssl verify -CAfile '.$this->casfile.' '.$certTempFile,$out);
        self::removeTempFiles($temporary_files);
        $aux = explode(' ',$w);
        if (isset($aux[1])) {
            if ($aux[1] != 'OK') {
                foreach($out as $item) {
                    $aux = explode(':',$item);
                    if(isset($aux[1])) {
                        $this->status['errors'][] = trim($aux[1]);
                        $this->status['isValid'] = false;
                    }
                }
                return;
            }
        } else {
            $this->status['errors'][] = (isset($aux[1]) ? trim($aux[1]) : 'Couldn\'t verify if certificate was revoked.(CD-01)');
            $this->status['isValid'] = false;
        }
       
    }

    /**
     * check if certificate is revoked
     */
    protected function _testRevoked()
    {
        if (!is_dir($this->crlspath)) {
            $this->status['errors'][] = 'Couldn\'t verify if certificate was revoked.(CD-02)';  // CRL path not found.';
            $this->status['isValid'] = false;
            return;
        }
        
        if (!isset($this->crlDistributionPoints[0])) {
            // Haven't found crl at the certificate
            $this->status['errors'][] = 'Couldn\'t verify if certificate was revoked.(CD-03)';  // Crl file not found;
            $this->status['isValid'] = false;
            return;
        }
        
        $aux = explode('/',$this->crlDistributionPoints[0]);
        $crl = file_get_contents($this->crlspath . '/' . $aux[count($aux)-1],true);
        $out = array();
        $w = exec('openssl crl -in ' . $this->crlspath . '/' . $aux[count($aux)-1] . ' -inform DER -noout -text',$out);

        if (strpos($out[5],'        Next Update: ') === false) {
            $this->status['errors'][] = 'Couldn\'t verify if certificate was revoked.(CD-04)';  // Invalid crl file found.';
            $this->status['isValid'] = false;
            return;
        } else {
            // - verify expired crl...
            $a1 = explode(' Update: ',$out[5]);
            if (time() >= date_timestamp_get(date_create($a1[1]))) {
                $this->status['errors'][] = 'Couldn\'t verify if certificate was revoked.(CD-05)';   // Invalid crl file found.';
                $this->status['isValid'] = false;
                return;
            }
        }

        $aux = array_search('    Serial Number: ' . $this->serialNumber, $out);
        
        if ($aux) {
            // cert revoked...
            $this->status['isValid'] = false;
            $a1 = explode('Date: ',$out[$aux+1]);
            $this->status['errors'][] = 'REVOKED Certificate at: ' . $a1[1];
            return;
        }
    }

    /**
     *
     * @param type $ab
     * @param type $q
     * @param type $flag
     * @return type 
     */
    public static function xBase128($ab,$q,$flag)
    {
        $abc = $ab;
        if( $q > 127 ) {
            $abc = self::xBase128($abc, floor($q / 128), 0 );
        }
        
        $q = $q % 128;
        if( $flag) {
            $abc[] = $q;
        } else {
            $abc[] = 0x80 | $q;
        }
        
        return $abc;
    }
    
    /**
     *
     * @param type $oid
     * @return type 
     */
    public static function oid2Hex($oid)
    {
        $abBinary = array();
        $parts = explode('.',$oid);
        $n = 0;
        $b = 0;
        
        for($n = 0; $n < count($parts); $n++) {
            if($n==0) {
                $b = 40 * $parts[$n];
            } elseif($n==1) {
                $b +=  $parts[$n];
                $abBinary[] = $b;
            } else {
                $abBinary = self::xBase128($abBinary, $parts[$n], 1 );
            }
        }
        
        $value =chr(0x06) . chr(count($abBinary));
        foreach($abBinary as $item) {
            $value .= chr($item);
        }
        
        return $value;
    }
    
    /**
     * Transform cert from PEM format to DER
     *
     * @param string Certificate PEM format
     * @return string Certificate DER format
     */
    static public function pem2Der($pemCertificate)
    {
        $aux = explode(chr(0x0A),$pemCertificate);
        $derCertificate = '';
        foreach ($aux as $i) {
            if($i != '') {
                if(substr($i, 0, 5) !== '-----') {
                    $derCertificate .= $i;
                }
            }
        }
        
        return base64_decode($derCertificate);
    }
    
    public function getSerialNumber()
    {
        return $this->_parsedCertificate['serialNumber'];
    }
    
    public function getVersion()
    {
        return $this->_parsedCertificate['version'];
    }

    public function getSubject()
    {
        return $this->_parsedCertificate['subject'];
    }

    public function getCn()
    {
        return $this->_parsedCertificate['subject']['CN'];
    }

    public function getIssuer()
    {
        return $this->_parsedCertificate['issuer'];
    }

    public function getIssuerCn()
    {
        return $this->_parsedCertificate['issuer']['CN'];
    }

    public function getHash()
    {
        return $this->_parsedCertificate['hash'];
    }

    public function getValidFrom()
    {
        if (!isset($this->validFrom)) {
            $this->validFrom = new \DateTime('@' . $this->_parsedCertificate['validFrom_time_t'], new \DateTimeZone('utc'));
        }
        
        return $this->validFrom;
    }

    public function getValidTo()
    {
        if (!isset($this->validTo)) {
            $this->validTo = new \DateTime('@' . $this->_parsedCertificate['validTo_time_t'], new \DateTimeZone('utc'));
        }
        
        return $this->validTo;
    }
      
    public function isCanSign()
    {
        $this->_parsePurpose($this->_parsedCertificate['purposes']);
        
        return $this->canSign;
    }

    public function isCanEncrypt()
    {
        $this->_parsePurpose($this->_parsedCertificate['purposes']);
        
        return $this->canEncrypt;
    }

    public function getEmail()
    {
        if (!isset($this->email)) {
            if (isset($this->_parsedCertificate['subject']['emailAddress'])) {
                $this->email = $this->_parsedCertificate['subject']['emailAddress'];
            } else {
                $this->_parseExtensions($this->_parsedCertificate['extensions']);
            }
        }
        
        
        return $this->email;
    }

    public function getUserName()
    {
        if (!isset($this->username)) {
            if ($this->_options['tryUsernameSplit']) {
                $parts = explode('@' , $this->getEmail(), 2);
                $this->username = $parts[0];
            } else {
                $this->username = $this->getEmail();
            }
        }
        
        return $this->username;
    }
    
    public function isCA()
    {
        return $this->ca;
    }

    public function isValid()
    {
        $this->status['isValid'] = isset($this->_certificate['SSL_CLIENT_VERIFY']) && 
                                       $this->_certificate['SSL_CLIENT_VERIFY'] === 'SUCCESS';
        
        // skip validation if not set, we trust the server's result
        if ($this->casfile) {
            $this->_validityCheck();
        }
        
        // skip test if not set
        if ($this->crlspath) {
            $this->_testRevoked(); 
        }
        
        return $this->status['isValid'];
    }
    
    public function getAuthorityKeyIdentifier()
    {
        return $this->authorityKeyIdentifier;
    }

    public function getCrlDistributionPoints()
    {
        return $this->crlDistributionPoints;
    }

    public function getAuthorityInfoAccess()
    {
        return $this->authorityInfoAccess;
    }

    public function getStatusErrors()
    {
        return $this->status['errors'];
    }
    
    public static function generateTempFilename(&$tab_arqs, $path)
    {
        $list = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $N = $list[rand(0,count($list)-1)].date('U').$list[rand(0,count($list)-1)].RAND(12345,9999999999).$list[rand(0,count($list)-1)].$list[rand(0,count($list)-1)].RAND(12345,9999999999).'.tmp';
        $aux = $path.'/'.$N;
        array_push($tab_arqs ,$aux);
        return  $aux;
    }
    
    private static function removeTempFiles($tab_arqs)
    {
        foreach ($tab_arqs as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    public static function writeTo($file, $content)
    {
        return file_put_contents($file, $content);   
    }

}