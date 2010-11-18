<?php
/**
 * SHA hash class
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
 * SHA hash class
 * 
 * @package    Hash
 */
class Hash_SHA
{
    /**
     * generate SHA hash
     *
     * @param  string  $algo
     * @param  string  $password
     * @return string  the SHA hash
     */
    public function generate($hashType, $password)
    {   
        $hashType = strtoupper($hashType);
        
        if (substr($hashType, 0, 3) !== 'SHA' && substr($hashType, 0, 4) !== 'SSHA') {
            throw new InvalidArgumentException('unsupported hash type: ' . $hashType);
        }
        
        # sha1 ssha1 sha256 ssha256
        $algo = substr($hashType, 0, 4) === 'SSHA' ? strtolower(substr($hashType, 1)) : strtolower($hashType);
        
        if (array_search($algo, hash_algos()) === false) {
            throw new UnexpectedValueException('unsupported algo: ' . $algo);
        }

        if (substr($hashType, 0, 4) === 'SSHA') {
            // generate 4 byte salt
            $salt = chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255)) . chr(mt_rand(0,255));
            
            // generate salted hash
            $hash = '{' . (($hashType == 'SSHA1') ? 'SSHA' : $hashType) . '}' . base64_encode(pack("H*", hash($algo, $password . $salt)) . $salt);
        } else {
            // generate hash
            $hash = '{' . (($hashType == 'SHA1') ? 'SHA' : $hashType) . '}' . base64_encode(pack("H*", hash($algo, $password)));
        }
        
        return $hash;
    }        

    /**
     * validate SHA hash
     * 
     * @param  string  $hash
     * @param  string  $password
     * @return boolean
     */
    public function validate($hash, $password)
    {
        if (!preg_match('/\{(S*SHA.*)\}(.*)/', $hash, $matches)) {
            throw new InvalidArgumentException('unsupported hash: ' . $hash);
        }
        
        $hashType = $matches[1];
        
        if (substr($hashType, 0, 3) !== 'SHA' && substr($hashType, 0, 4) !== 'SSHA') {
            throw new InvalidArgumentException('unsupported hash type: ' . $hashType);
        }
        
        # sha1 ssha1 sha256 ssha256
        $algo = substr($hashType, 0, 4) === 'SSHA' ? strtolower(substr($hashType, 1)) : strtolower($hashType);
        
        $algo = ($algo === 'sha') ? 'sha1' : $algo;
        
        // does php support this algo?
        if (array_search($algo, hash_algos()) === false) {
            throw new UnexpectedValueException('unsupported algo: ' . $algo);
        }
        
        if (substr($hashType, 0, 4) === 'SSHA') {
            // base64 decode hash
            $decodedHash = base64_decode($matches[2]);
            
            // get salted hash of password
            $originalHash = substr($decodedHash, 0, -4);
            
            // get salt
            $salt = substr($decodedHash, -4);
            
            // recalculate salted hash of provided cleartext password
            $validatedHash = pack("H*", hash($algo, $password . $salt));
        } else {
            $originalHash  = base64_decode($matches[2]);
            
            // recalculate hash of provided cleartext password
            $validatedHash = pack("H*", hash($algo, $password));
        }
        
        if ($originalHash === $validatedHash) {
            return true;
        }
        
        return false;
        
    }    
}