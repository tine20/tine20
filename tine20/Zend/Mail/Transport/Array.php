<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @see Zend_Mime
 */
require_once 'Zend/Mime.php';

/**
 * @see Zend_Mail_Transport_Abstract
 */
require_once 'Zend/Mail/Transport/Abstract.php';

/**
 * Mail Array Transport (for testing)
 *
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Mail_Transport_Array extends Zend_Mail_Transport_Abstract
{
    protected $_messages = array();
    
    /**
     * pushes mail into internal array
     *
     * @return void
     */
    protected function _sendMail()
    {
        array_push($this->_messages, $this->_mail);
    }
    
    /**
     * flush all mails
     * 
     * @return void
     */
    public function flush()
    {
        $this->_messages = array();
    }
    
    /**
     * returns all messages
     * 
     * @return array of Zend_Mail
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}