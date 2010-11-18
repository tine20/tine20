<?php

class Hash_Factory
{
    /**
     * validate hash
     * 
     * @param  string  $hash
     * @param  string  $password
     * @return boolean
     */
    public static function validate($hash, $password)
    {
        if (!preg_match('/\{(.*)\}.*/', $hash, $matches)) {
            throw new Exception('unsupported hash: ' . $hash);
        }

        $algo = strtoupper($matches[1]);

        switch ($algo) {
            case (strpos($algo, 'SHA') !== false):
                $hashClass = new Hash_SHA();
                break;
                
            case (strpos($algo, 'MD5') !== false):
                $hashClass = new Hash_MD5();
                break;
                
            default:
                throw new InvalidArgumentException('Unsupported algo provided: ' . $algo);
                break;
        }
        
        return $hashClass->validate($hash, $password);
    }
}