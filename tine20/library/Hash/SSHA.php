<?php
/**
 * SSHA hash class
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
class Hash_SSHA
{
    /**
     * generate ssha hash
     * 
     * @param  string  $password
     * @return string  the ssha hash
     */
    public static function generateHash($password, $algo)
    {   
        $algo = strtoupper($algo);
        
        if (substr($algo, 0, 3) != 'SHA' || array_search(strtolower($algo), hash_algos()) === false) {
            throw new \Exception('unsupported algo: ' . $algo);
        }

        // generate 4 byte salt
        $salt = chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255));
        
        // generate salted hash
        $hash = '{S' . (($algo == 'SHA1') ? 'SHA' : $algo) . '}' . base64_encode(pack("H*", hash($algo, $password . $salt)) . $salt);
        
        return $hash;
    }        

    /**
     * validate ssha hash
     * 
     * @param  string  $hash
     * @param  string  $password
     * @return boolean
     */
    public static function validateHash($hash, $password)
    {
        if (!preg_match('/\{S(SHA.*)\}(.*)/', $hash, $matches)) {
            throw new \Exception('unsupported hash: ' . $hash);
        }

        $algo = ($matches[1] == 'SHA') ? 'sha1' : strtolower($matches[1]);
        
        // does php support this algo?
        if (array_search($algo, hash_algos()) === false) {
            throw new \Exception('unsupported algo: ' . $algo);
        }
        
        // base64 decode hash
        $decodedHash = base64_decode($matches[2]);
        
        // get salted hash of password
        $originalHash = substr($decodedHash, 0, -4);
        
        // get salt
        $salt = substr($decodedHash, -4);
        
        // recalculate salted hash of provided cleartext password
        $validatedHash = pack("H*", hash($algo, $password . $salt));
        
        if ($originalHash === $validatedHash) {
            return true;
        }
        
        return false;
        
    }    
}