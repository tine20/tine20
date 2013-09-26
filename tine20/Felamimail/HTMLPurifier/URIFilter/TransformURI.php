<?php
/**
 * Tine 2.0
 * 
 * uri filter
 *
 * @package     Felamimail
 * @subpackage  HTMLPurifier
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Felamimail_HTMLPurifier_AttrTransform_AValidator
 *
 */
class Felamimail_HTMLPurifier_URIFilter_TransformURI extends HTMLPurifier_URIFilter
{
    public $name = 'TransformURI';
    
    /**
     * filter
     * 
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return boolean
     */
    public function filter(&$uri, $config, $context)
    {
        $result = TRUE;
        $token = $context->get('CurrentToken', true);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' URI: ' . var_export($uri, TRUE) . ' ' 
            . ' TOKEN: ' . var_export($token, TRUE));
        
        if ($uri->host) {
            $result = $this->_checkExternalUrl($uri, $token);
        }
        return $result;
    }
    
    /**
     * check external url
     * 
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Token $token
     * @return boolean
     * 
     * @todo we need a preference / on demand button if loading external ressources is allowed
     * @todo use a different namespace for src= e.g. tine20:src= $context->attr[tine20:URI] = OR use "library/extjs/blank.gif?resourceURI"
     */
    protected function _checkExternalUrl($uri, $token)
    {
        $result = in_array($uri->scheme, array('http', 'https', 'mailto'));
        
        // only allow external urls in anchors for the moment
        $result = $result && $token->name === 'a';
        
//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
//             . ' Moving uri to another namespace and replace current uri with blank.gif: ' . $uri->toString());
        
//         //$scheme, $userinfo, $host, $port, $path, $query, $fragment
//         $uri = new HTMLPurifier_URI('http', null, $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], null,
//             '/index.php', 'Felamimail.getResource&uri=' . base64_encode($uri->toString()) . '&type=' . $token->name, null);
        
        if (! $result) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Remove  URI: ' . $uri->toString());
        }
        
        return $result;
    }
}
