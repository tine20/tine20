<?php
/**
 * MD5 hash class
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * 
 * @package     Hash
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke
*/

/**
 * MD5 hash class
 * 
 * @package    Hash
 */
class Hash_Password_MD5
{
    /**
     * generate MD5 hash
     * 
     * @param  string  $password
     * @return string  the MD5 hash
     */
    public function generate($algo, $password, $addPrefix = true)
    {   
        $algo = strtoupper($algo);
        
        // generate hash
        switch ($algo) {
            case 'MD5':
            case 'PLAIN-MD5':
                $algo = 'MD5';
                $hash = hash('md5', $password);
                break;
                
            case 'LDAP-MD5':
                $hash = base64_encode(hash('md5', $password, true));
                break;
                
            case 'SMD5':
                // generate 4 byte salt
                $salt = chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255));
            
                $hash = base64_encode(hash('md5', $password . $salt, true). $salt);
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported MD5 type provided: ' . $algo);
                break;
        }
        
        $hash = ($addPrefix === true ? '{' . $algo . '}' : null) . $hash;
        
        return $hash;
    }        

    /**
     * validate SSHA hash
     * 
     * @param  string  $hash
     * @param  string  $password
     * @return boolean
     */
    public function validate($hash, $password)
    {
        $hash = (($pos = strpos($hash, '}')) !== false) ? substr($hash, $pos + 1) : $hash;
        
        switch (strlen($hash)) {
            case 32:
                // get original hash of password
                $originalHash = $hash;
                
                $validatedHash = hash('md5', $password);
                break;
                
            case 24:
                // get original hash of password
                $originalHash = $hash;
                
                $validatedHash = base64_encode(hash('md5', $password, true));
                break;
                
            case 28:
                // base64 decode hash
                $decodedHash = base64_decode($hash);
                
                // get salted hash of password
                $originalHash = substr($decodedHash, 0, -4);
                
                // get salt
                $salt = substr($decodedHash, -4);
                
                // recalculate salted hash of provided cleartext password
                $validatedHash = hash('md5', $password . $salt, true);
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported MD5 hash provided: ' . $hash);
                break;
        }
        
        if ($originalHash === $validatedHash) {
            return true;
        }
        
        return false;
        
    }    
}