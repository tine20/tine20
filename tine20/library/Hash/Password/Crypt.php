<?php
/**
 * crypt hash class
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
 * crypt hash class
 * 
 * @package    Hash
 */
class Hash_Password_Crypt
{
    const CRYPT          = 'CRYPT';
    const CRYPT_MD5      = 'CRYPT-MD5';
    const CRYPT_BLOWFISH = 'CRYPT-BLOWFISH';
    const CRYPT_SHA256   = 'CRYPT-SHA256';
    const CRYPT_SHA512   = 'CRYPT-SHA512';
    
    /**
     * generate crypt hash
     * 
     * @param  string  $password
     * @return string  the crypt hash
     */
    public function generate($algo, $password, $addPrefix = true)
    {   
        // generate hash
        switch (strtoupper($algo)) {
            case self::CRYPT:
                // generate 2 character salt
                $salt = self::getRandomString(2);
                
                break;
                
            case self::CRYPT_BLOWFISH:
                // generate 29 character salt
                $salt = '$2a$07$' . self::getRandomString(22);
                
                break;
                
            case self::CRYPT_MD5:
            case 'MD5-CRYPT':
                // generate 12 character salt
                $salt = '$1$' . self::getRandomString(9);
            
                break;
                
            case self::CRYPT_SHA256:
                // generate 16 character salt
                $salt = '$5$' . self::getRandomString(13);
            
                break;
                
            case self::CRYPT_SHA512:
                // generate 16 character salt
                $salt = '$6$' . self::getRandomString(13);
            
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported crypt type provided: ' . $algo);
                break;
        }
        
        $hash = ($addPrefix === true ? '{' . self::CRYPT . '}' : null) . crypt($password, $salt);
        
        return $hash;
    }        

    /**
     * validate crypt hash
     * 
     * @param  string  $hash
     * @param  string  $password
     * @return boolean
     */
    public function validate($hash, $password)
    {
        // get original hash of password
        $originalHash = (($pos = strpos($hash, '}')) !== false) ? substr($hash, $pos + 1) : $hash;
        
        // recalculate hash of provided cleartext password
        switch (substr($originalHash, 0, 2)) {
            case '$1': // crypt md5 12 byte salt
                // get salt
                $salt = substr($originalHash, 0, 12);
                
                $validatedHash = crypt($password, $salt);
                break;
                                
            case '$2': // crypt blowfish 29 byte salt
                // get salt
                $salt = substr($originalHash, 0, 29);
                
                $validatedHash = crypt($password, $salt);
                break;
                                
            case '$5': // crypt sha256 16 byte salt
            case '$6': // crypt sha512 16 byte salt
                // get salt
                $salt = substr($originalHash, 0, 16);
                
                $validatedHash = crypt($password, $salt);
                break;
                                
            default:
                // get salt
                $salt = substr($originalHash, 0, 2);
                
                $validatedHash = crypt($password, $salt);
                break;
        }
        
        if ($originalHash === $validatedHash) {
            return true;
        }
        
        return false;
    }
        
    /**
     * generates a randomstrings of given length
     *
     * @param int $_length
     */
    public static function getRandomString($_length)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        $randomString = '';
        for ($i=0; $i<(int)$_length; $i++) {
            $randomString .= $chars[mt_rand(1, strlen($chars)) -1];
        }
        
        return $randomString;
    }    
}