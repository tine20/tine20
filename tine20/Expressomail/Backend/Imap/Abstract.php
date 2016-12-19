<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2016 SERPRO (http://www.serpro.gov.br)
 */

/**
 * Abstraction Expressomail IMAP backend
 *
 * @package     Expressomail
 * @subpackage  Backend
 */
abstract class Expressomail_Backend_Imap_Abstract extends Zend_Mail_Storage_Imap implements Expressomail_Backend_Imap_Interface
{
    const ACLREAD = 1;

    const ACLWRITE = 2;

    const ACLSENDAS = 4;

    const DEFAULT_IMAP_QUOTA = 51200;// 50MB = 51200KB

    /**
     * protocol handler
     * @var null|Expressomail_Protocol_Imap
     */
    protected $_protocol;

    /**
     * wheter to use UID as message identifier
     *
     * @var bool
     */
    protected $_useUid;

    /**
     * Stores the default user namespace
     *
     * @var string
     * @TODO: Create a setter for the namespace to facilitate the manipulation
     */
    protected $_userNameSpace = 'user';
}