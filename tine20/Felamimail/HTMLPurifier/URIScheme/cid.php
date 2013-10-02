<?php
/**
 * Tine 2.0
 * 
 * HTMLPurifier_URIScheme_cid: Implements cid uri scheme
 *
 * @package     Felamimail
 * @subpackage  HTMLPurifier
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class HTMLPurifier_URIScheme_cid extends HTMLPurifier_URIScheme
{
    /**
     * browsable
     * 
     * @var boolean
     */
    public $browsable = true;
    
    /**
     * allowed types
     * 
     * @var array
     * 
     * @todo maybe this can be removed as we overwrite doValidate()
     */
    public $allowed_types = array(
        // you better write validation code for other types if you
        // decide to allow them
        'image/jpeg' => true,
        'image/gif' => true,
        'image/png' => true,
    );
    
    /**
     * may omit host
     * 
     * @var boolean
     * 
     * this is actually irrelevant since we only write out the path
     * component
     */
    public $may_omit_host = true;

    /**
     * (non-PHPdoc)
     * @see HTMLPurifier_URIScheme::doValidate()
     */
    public function doValidate(&$uri, $config, $context)
    {
        $messageId = $config->get('Felamimail.messageId');
        
        $uri->scheme = NULL;
        $uri->host = NULL;
        $uri->query = 'method=Felamimail.getResource&cid=' . $uri->path . '&messageId=' . $messageId;
        $uri->path = 'index.php';
        
        return true;
    }

    /**
     * mute error handler
     * 
     * @param unknown_type $errno
     * @param unknown_type $errstr
     */
    public function muteErrorHandler($errno, $errstr)
    {
    }
}
