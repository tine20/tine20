<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold mail account constants
 * 
 * @package   Tinebase
 * @subpackage    EmailUser
 */
abstract class Tinebase_EmailUser_Model_Account extends Tinebase_Record_Abstract
{
    /**
     * secure connection setting for no secure connection
     *
     */
    const SECURE_NONE = 'none';

    /**
     * secure connection setting for tls
     *
     */
    const SECURE_TLS = 'tls';

    /**
     * secure connection setting for ssl
     *
     */
    const SECURE_SSL = 'ssl';

    /**
     * adb list account
     */
    const TYPE_ADB_LIST = 'adblist';

    /**
     * shared account
     */
    const TYPE_SHARED = 'shared';

    /**
     * system account
     *
     */
    const TYPE_SYSTEM = 'system';
    
    /**
     * user defined account
     *
     */
    const TYPE_USER = 'user';

    /**
     * display format: plain
     *
     */
    const DISPLAY_PLAIN = 'plain';
    
    /**
     * display format: html
     *
     */
    const DISPLAY_HTML = 'html';
    
    /**
     * signature position above quote
     *
     */
    const SIGNATURE_ABOVE_QUOTE = 'above';
    
    /**
     * signature position above quote
     *
     */
    const SIGNATURE_BELOW_QUOTE = 'below';
    
    /**
     * display format: content type
     *
     * -> depending on content_type => text/plain show as plain text
     */
    const DISPLAY_CONTENT_TYPE = 'content_type';
}
