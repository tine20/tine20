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
 * @todo        parse authorityInfoAccess, caissuer and ocsp
 * @todo        parse authorityKeyIdentifier
 * @todo        phpdoc of all methods
 * @todo        change the name of the protected variables to the standart coding defalt (_var)
 * 
 */

class Custom_Auth_ModSsl_Certificate_X509
{
    protected $certificate;
    /*
     * default values for casfile and crlspath
     */
    protected $casfile = 'skip';
    protected $crlspath = 'skip';
    
    protected $serialNumber = null;
    protected $version = null;
    protected $subject = null;
    protected $cn = null;
    protected $issuer = null;
    protected $issuerCn = null;
    protected $hash = null;
    protected $validFrom = null;
    protected $validTo = null;
    protected $canSign = false;
    protected $canEncrypt = false;
    protected $email = null;
    protected $ca = false;
    protected $authorityKeyIdentifier = null;
    protected $crlDistributionPoints = null;
    protected $authorityInfoAccess = null;
    protected $status =  array();
    
    public function __construct($certificate, $dontSkip = FALSE)
    {   
        $config = Tinebase_Config::getInstance()->get('modssl');
        if (is_object($config)){
            $this->casfile = $config->casfile;
            $this->crlspath = $config->crlspath;
        }
        $this->status = array('isValid' => true,'errors' => array());
        $this->certificate = self::_fixPemCertificate($certificate);
        $c = openssl_x509_parse($this->certificate);
        
        // define certificate properties
        $this->serialNumber = $c['serialNumber'];
        $this->version = $c['version'];
        $this->subject = $c['subject'];
        $this->cn = $c['subject']['CN'];
        $this->issuer = $c['issuer'];
        $this->issuerCn = $c['issuer']['CN'];
        $this->hash = $this->_calcHash();
        
//        $dateTimezone = new DateTimeZone(Tinebase_Core::getUserTimezone());
//        $locale = new Zend_Locale($_translation->getAdapter()->getLocale());
        
        // Date valid from
        
        $this->validFrom = Tinebase_Translation::dateToStringInTzAndLocaleFormat(new Tinebase_DateTime($c['validFrom_time_t']));
        
        // Date valid to
        $this->validTo = Tinebase_Translation::dateToStringInTzAndLocaleFormat(new Tinebase_DateTime($c['validTo_time_t']));
        $this->_parsePurpose($c['purposes']);
        $this->_parseExtensions($c['extensions']);
              
        if(strtolower($this->casfile) != 'skip') {
            $this->_validityCheck(); // skip validation, we trust the server's result
        }
        
        if(strtolower($this->crlspath) != 'skip' | $dontSkip) {
            $this->_testRevoked(); // skip test,
        }
    }

    /**
     * Reads a file with digital certificates in PEM format
     *
     * @param String $filename
     * @return array of Addresbook_Model_Certificate
     * @todo Add auditing code when implementation becomes available
     */
    static public function readCertificatesFile($filename){
        
        $fileContents = file_get_contents($filename);
        if ($fileContents !== false) {
            self::_fixPemCertificate($fileContents);
        } else {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__
                . ' Error reading certificates file ' . $filename
            );
            return array();
        }

        $result = array();
        $match = array();
        preg_match_all('/(-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----)/s', $fileContents, $match);
        foreach ($match[0] as $pemCertificate) {
            //$x509Certificate = Custom_Auth_ModSsl_Certificate_Factory::buildCertificate($pemCertificate, true);
            $certificateModel = Custom_Model_DigitalCertificateValidation::createFromCertificate($pemCertificate, TRUE, TRUE);
            $result[] = $certificateModel->toArray();
        }
        return $result;
    }

    static protected function _fixPemCertificate($certificate)
    {
        $pemFormat = preg_replace('/ (?!CERTIFICATE-----)/', chr(0x0D).chr(0x0A), $certificate);
        
        return $pemFormat;
    }


    protected function _calcHash() {
        $derCertificate = self::pem2Der($this->certificate);
        return hash('sha256', $derCertificate);
    }
    
    protected function _parseExtensions($extensions)
    {
        if (is_array($extensions)){
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
                        $crlDist = array();
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (preg_match('/URI:/', $line) === 1){
                                $crlDist[] = preg_replace('/URI:/', '', $line);
                            }
                        }
                        $this->crlDistributionPoints = $crlDist;
                        break;

                    case 'authorityKeyIdentifier' :
                        if (preg_match('/\bkeyid:(\b([a-f0-9]{2}:)+[a-f0-9]{2}\b)/i', $value, $matches))
                        {
                            $this->authorityKeyIdentifier = $matches[1];
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ":\nauthorityKeyIdentifier not found!");
                        }

                        break;
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
    }
    
    protected function _parsePurpose($purposes)
    {
        if (is_array($purposes)){
            foreach ($purposes as $purpose) {
                switch ($purpose[2]) {
                    case 'smimesign' :
                        $this->canSign = $purpose[0] == 1 ? true : false;
                        break;
                    case 'smimeencrypt' :
                        $this->canEncrypt = $purpose[0] == 1 ? true : false;
                        break;
                }
            }
        }
    }
    
    protected function _validityCheck() 
    {
        $cdError = Tinebase_Translation::getTranslation('Tinebase')->_('Couldn\'t verify if certificate was revoked.');
        if(!is_file($this->casfile)) {
            $this->status['errors'][] = 'Invalid Certificate .(CA-01)';  //'CAs file not found.';
            $this->status['isValid'] = false;
            return;
        }
        
        $temporary_files = array();
        $certTempFile = self::generateTempFilename($temporary_files, Tinebase_Core::getTempDir());
        self::writeTo($certTempFile,$this->certificate);
        
        // Get serialnumber  by comand line ...
        $out = array();
        $w = exec('openssl x509 -inform PEM -in ' . $certTempFile . ' -noout -serial',$out);
        $aux = explode('serial=',$out[0]);
        
        if(isset($aux[1]))  {
            $this->serialNumber = $aux[1];
        } else {
            $this->serialNumber = null;
        }
    
        $out = array();
        // certificate verify ...
        $w = exec('openssl verify -CAfile '.$this->casfile.' '.$certTempFile,$out);
        self::removeTempFiles($temporary_files);
        $aux = explode(' ',$w);
        if(isset($aux[1])) {
            if($aux[1] != 'OK') {
                foreach($out as $item) {
                    $aux = explode(':',$item);
                    if(isset($aux[1])) {
                        $this->status['errors'][] = Tinebase_Translation::getTranslation('Tinebase')->_(trim($aux[1]));
                        $this->status['isValid'] = false;
                    }
                }
                return;
            }
        } else {
            $this->status['errors'][] = (isset($aux[1]) ? trim($aux[1]) : $cdError.'(CD-01)');
            $this->status['isValid'] = false;
        }
       
    }

    /**
     *
     * @return type 
     */
    protected function _testRevoked()
    {
        $error = Tinebase_Translation::getTranslation('Tinebase')->_('Couldn\'t verify if certificate was revoked.');
        if(!is_dir($this->crlspath)) {
            $this->status['errors'][] = $error.'(CD-02)';  // CRL path not found.';
            $this->status['isValid'] = false;
            return;
        }

        // TODO: Manage our own CRLs, downloading it if needed
        $pathToCrl = null;
        if (!empty($this->crlDistributionPoints) && is_array($this->crlDistributionPoints)) {
            foreach ($this->crlDistributionPoints as $url) {
                $pathToCrl = $this->crlspath . '/' . md5($url) . '.crl';
                if (file_exists($pathToCrl)) {
                    break;
                } else {
                    $pathToCrl = null;
                }
            }
        }

        if (empty($pathToCrl)) {
            // Haven't found crl at the certificate
            $this->status['errors'][] = $error.'(CD-03)';  // Crl file not found;
            $this->status['isValid'] = false;
            return;
        }

        $out = array();
        exec('openssl crl -in ' . $pathToCrl . ' -inform DER -noout -text',$out);

        if(strpos($out[5],'        Next Update: ') === false) {
            $this->status['errors'][] = $error.'(CD-04)';  // Invalid crl file found.';
            $this->status['isValid'] = false;
            return;
        } else {
            // - verify expired crl...
            $a1 = explode(' Update: ',$out[5]);
            if(time() >= date_timestamp_get(date_create($a1[1]))) {
                $this->status['errors'][] = $error.'(CD-05)';   // Invalid crl file found.';
                $this->status['isValid'] = false;
                return;
            }
        }

        $aux = array_search('    Serial Number: ' . $this->serialNumber, $out);
        
        if($aux) {
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
    
    public function getPemCertificateData() {
        return $this->certificate;
    }
    
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getCn()
    {
        return $this->cn;
    }

    public function getIssuer()
    {
        return $this->issuer;
    }

    public function getIssuerCn()
    {
        return $this->issuerCn;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getValidFrom()
    {
        return $this->validFrom;
    }

    public function getValidTo()
    {
        return $this->validTo;
    }
      
    public function isCanSign()
    {
        return $this->canSign;
    }

    public function isCanEncrypt()
    {
        return $this->canEncrypt;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function isCA()
    {
        return $this->ca;
    }

    /**
     * Returns the validity of the Certificate
     * @return boolean
     */
    public function isValid()
    {
        return ($this->status['isValid'])?true:false;
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
    
    public function toArray()
    {
        
        $reflectionClass = new ReflectionClass(get_class($this));
        $array = array();
        foreach ($reflectionClass->getProperties() as $property) {
            $name = $property->getName();
            if ($name !== 'casfile' &&  $name !== 'crlspath') {
                $array[$name] = $this->$name;
            }
        }
        return $array;
        
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
        foreach($tab_arqs as $file ) {
            if(file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    public static function writeTo($file, $content)
    {
        return file_put_contents($file, $content);   
    }

}