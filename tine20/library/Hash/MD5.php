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
 * SSHA hash class
 * 
 * @package    Hash
 */
class Hash_MD5
{
    /**
     * generate SSHA hash
     * 
     * @param  string  $password
     * @return string  the SSHA hash
     */
    public function generate($algo, $password)
    {   
        // generate hash
        switch (strtoupper($algo)) {
            case 'PLAIN-MD5':
                $hash = '{PLAIN-MD5}' . hash('md5', $password);
                break;
                
            case 'LDAP-MD5':
                $hash = '{LDAP-MD5}' . base64_encode(hash('md5', $password, true));
                break;
                
            case 'SMD5':
                // generate 4 byte salt
                $salt = chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255));
            
                $hash = '{SMD5}' . base64_encode(hash('md5', $password . $salt, true). $salt);
                break;
                
            case 'MD5-CRYPT':
                // generate 4 byte salt
                $salt = chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255));
            
                $hash = '{MD5-CRYPT}' . crypt($password, '$1$' . $salt . '$');
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported MD5 type provided: ' . $algo);
                break;
        }
        
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
        if (!preg_match('/\{(.*MD5.*)\}(.*)/i', $hash, $matches)) {
            throw new InvalidArgumentException('unsupported hash: ' . $hash);
        }

        // get original hash of password
        $originalHash = $matches[2];
        
        // recalculate hash of provided cleartext password
        switch (strtoupper($matches[1])) {
            case 'PLAIN-MD5':
                $validatedHash = hash('md5', $password);
                break;
                
            case 'LDAP-MD5':
                $validatedHash = base64_encode(hash('md5', $password, true));
                break;
                
            case 'SMD5':
                // base64 decode hash
                $decodedHash = base64_decode($originalHash);
                
                // get salted hash of password
                $originalHash = substr($decodedHash, 0, -4);
                
                // get salt
                $salt = substr($decodedHash, -4);
                
                // recalculate salted hash of provided cleartext password
                $validatedHash = hash('md5', $password . $salt, true);
                break;
                
            case 'MD5-CRYPT':
                // get salt
                $salt = substr($originalHash, 3, 8);
                
                // recalculate salted hash of provided cleartext password
                $validatedHash = crypt($password, '$1$' . $salt . '$');
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported MD5 type provided: ' . $matches[1]);
                break;
        }
        
        if ($originalHash === $validatedHash) {
            return true;
        }
        
        return false;
        
    }    
}