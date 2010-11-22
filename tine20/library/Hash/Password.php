<?php

class Hash_Password
{
    /**
     * validate password hash
     * 
     * @param  string   $hash
     * @param  string   $password
     * @return boolean
     */
    public static function validate($hash, $password)
    {
        if (!preg_match('/\{(.*)\}.*/', $hash, $matches)) {
            throw new Exception('unsupported hash: ' . $hash);
        }

        $algo = strtoupper($matches[1]);

        switch ($algo) {
            case (strpos($algo, 'CRYPT') !== false):
                $hashClass = new Hash_Password_Crypt();
                break;
                
            case (strpos($algo, 'SHA') !== false):
                $hashClass = new Hash_Password_SHA();
                break;
                
            case (strpos($algo, 'MD5') !== false):
                $hashClass = new Hash_Password_MD5();
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported algo provided: ' . $algo);
                break;
        }
        
        return $hashClass->validate($hash, $password);
    }
    
    /**
     * generate password hash
     * 
     * @param  string   $hashType
     * @param  string   $password
     * @param  boolean  $addPrefix  add {HASHTYPE} prefix
     * @return string
     */
    public static function generate($hashType, $password, $addPrefix = true)
    {
        $hashType = strtoupper($hashType);

        switch ($hashType) {
            case (strpos($hashType, 'CRYPT') !== false):
                $hashClass = new Hash_Password_Crypt();
                break;
                
            case (strpos($hashType, 'SHA') !== false):
                $hashClass = new Hash_Password_SHA();
                break;
                
            case (strpos($hashType, 'MD5') !== false):
                $hashClass = new Hash_Password_MD5();
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported algo provided: ' . $algo);
                break;
        }
        
        return $hashClass->generate($hashType, $password, $addPrefix);
    }
}