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
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Auth HTTP NTLM Resolver Mock
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Auth_Adapter_Http_Ntlm_Resolver_Mock implements Zend_Auth_Adapter_Http_Ntlm_Resolver_Interface
{
    /**
     * Resolve username to password/hash/etc.
     *
     * @param  string $username Username
     * @return string|false User's shared secret, if the user is found in the
     *         realm, false otherwise.
     */
    public function resolve($username)
    {
        switch ($username) {
            case 'user':
                $ntPassword = hash('md4', Zend_Auth_Adapter_Http_Ntlm::toUTF16LE('SecREt01'), TRUE);
                break;
            default:
                $ntPassword = NULL;
                break;
        }
        
        return $ntPassword;
    }
}