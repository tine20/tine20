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
        } else if ($uri->scheme == 'data') {
            $result = $this->_checkData($uri, $token);
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
        $result = FALSE;
//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
//             . ' Moving uri to another namespace and replace current uri with blank.gif: ' . $uri->toString());
        
//         //$scheme, $userinfo, $host, $port, $path, $query, $fragment
//         $uri = new HTMLPurifier_URI('http', null, $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], null,
//             '/index.php', 'Felamimail.getResource&uri=' . base64_encode($uri->toString()) . '&type=' . $token->name, null);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Remove  URI: ' . $uri->toString());
        
        return $result;
    }
    
    /**
     * check if data is valid, check and allow
     * 
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Token $token
     * @return boolean
     */
    protected function _checkData($uri, $token)
    {
        $result = FALSE;
        if ($token->name === 'img' && isset($token->attr['src'])) {
            $imgSrc = $token->attr['src'];
            $imgSrc = str_replace(array("\r", "\n"), '', $imgSrc);
            if (preg_match('/([a-z\/]*);base64,(.*)/', $imgSrc, $matches)) {
                $mimetype = $matches[1];
                $base64 = $matches[2];
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' Found base64 image: ' . $base64);
                $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_imgdata');
                file_put_contents($tmpPath, @base64_decode($base64));
                // @todo check given mimetype or all images types?
                if (! Tinebase_ImageHelper::isImageFile($tmpPath)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' URI data is no image file: ' . $uri->toString());
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                        . ' Verified ' . $mimetype . ' image.');
                    $result = TRUE;
                }
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Only allow images data uris, discarding: ' . $token->name);
        }
        
        return $result;
    }
}
