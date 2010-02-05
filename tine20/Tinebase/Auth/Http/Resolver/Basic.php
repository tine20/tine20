<?php

require_once('Zend/Auth/Http/Resolver/Interface.php');

class Tinebase_Auth_Http_Resolver_Basic implements Zend_Auth_Http_Resolver_Interface
{
    // shit, the Zend_Auth_Adapter_Http does not support a crypt fn for the password.
    // the basic resolver is expected to return a cleartext pwd
    
    
}