<?php
/**
 * Tine 2.0
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Http_Server to handle call forwarding from Asterisk(func_curl)
 * 
 * cfi call forward immediate
 * cfd call forward duration
 * cfb call forward busy
 *
 * @package     Voipmanager
 */
class Voipmanager_Frontend_Asterisk_CallForward
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';

    /**
     * set call forwarding
     *
     * @param string $type call forward type (cfi, cfd, cfb)
     * @param string $name sip peer name
     * @param string $mode off, number, voicemail 
     * @param string $destination number to redirect
     * @param string $timeout timeout for cfd
     */
    public function setCF($type, $name, $mode, $destination, $timeout)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " enable cfi for $name to $mode => $destination");
        
        $sipPeer = $this->_getSipPeer($name);
        
        try {
            $redirect = $this->_getRedirect($sipPeer);
        } catch(Voipmanager_Exception_NotFound $e) {
            $redirect = new Voipmanager_Model_Asterisk_Redirect(array(
                'sippeer_id'    => $sipPeer->getId(),
            ));
        }
        
        switch($type) {
            case 'cfi':
                $redirect->cfi_mode   = $mode;
                if($mode == Voipmanager_Model_Asterisk_Redirect::CFMODE_NUMBER) {
                    $redirect->cfi_number = $destination;
                }
                break;
                
            case 'cfd':
                $redirect->cfd_mode   = $mode;
                $redirect->cfd_time   = $timeout;
                if($mode == Voipmanager_Model_Asterisk_Redirect::CFMODE_NUMBER) {
                    $redirect->cfd_number = $destination;
                }
                break;
                
            case 'cfb':
                $redirect->cfb_mode   = $mode;
                if($mode == Voipmanager_Model_Asterisk_Redirect::CFMODE_NUMBER) {
                    $redirect->cfb_number = $destination;
                }
                break;
        }
        
        $id = $redirect->getId();
        
        if(empty($id)) {
            Voipmanager_Controller_Asterisk_Redirect::getInstance()->create($redirect);
        } else {
            Voipmanager_Controller_Asterisk_Redirect::getInstance()->update($redirect);
        }
    }
    
    /**
     * disable all call forwardings off
     *
     * @param string $name sip peer name
     */
    public function setAllCFOff($name)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " disable all cf for $name");
        
        $sipPeer = $this->_getSipPeer($name);
        
        try {
            $redirect = $this->_getRedirect($sipPeer);
        } catch(Voipmanager_Exception_NotFound $e) {
            $redirect = new Voipmanager_Model_Asterisk_Redirect(array(
                'sippeer_id'    => $sipPeer->getId(),
            ));
        }
        
        $redirect->cfi_mode   = Voipmanager_Model_Asterisk_Redirect::CFMODE_OFF;
        $redirect->cfd_mode   = Voipmanager_Model_Asterisk_Redirect::CFMODE_OFF;
        $redirect->cfb_mode   = Voipmanager_Model_Asterisk_Redirect::CFMODE_OFF;
        
        $id = $redirect->getId();
        
        if(empty($id)) {
            Voipmanager_Controller_Asterisk_Redirect::getInstance()->create($redirect);
        } else {
            Voipmanager_Controller_Asterisk_Redirect::getInstance()->update($redirect);
        }
    }
    
    /**
     * get call forwarding settings separated by :
     *
     * @param string $type
     * @param string $name
     * 
     * @throws Voipmanager_Exception_InvalidArgument
     */
    public function getCF($type, $name)
    {
        if(empty($name)) {
            throw new Voipmanager_Exception_InvalidArgument('$name can not be empty');
        }
        
        $sipPeer = $this->_getSipPeer($name);
        
        try {
            $redirect = $this->_getRedirect($sipPeer);
            
            switch($type) {
                case 'cfi':
                    $result = $redirect->cfi_mode . ':' . $redirect->cfi_number;
                    break;
                    
                case 'cfd':
                    $result = $redirect->cfd_mode . ':' . $redirect->cfd_number . ':' . $redirect->cfd_time;
                    break;
                    
                case 'cfb':
                    $result = $redirect->cfb_mode . ':' . $redirect->cfb_number;
                    break;
                    
                case 'all':
                    $result = $redirect->cfb_mode . ':' . $redirect->cfb_number;
                    $result = sprintf("%s:%s:%s:%s:%s:%s:%s",
                        $redirect->cfi_mode,
                        $redirect->cfi_number,
                        $redirect->cfd_mode,
                        $redirect->cfd_number,
                        $redirect->cfd_time,
                        $redirect->cfb_mode,
                        $redirect->cfb_number
                    );
                    break;
                    
                default;
                    $result = Voipmanager_Model_Asterisk_Redirect::CFMODE_OFF . ':';
                    break;
            }
             
        } catch(Voipmanager_Exception_NotFound $e) {
            $result = Voipmanager_Model_Asterisk_Redirect::CFMODE_OFF . ':';
        } 
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " get cf($type) for $name => $result");
        
        echo $result;
    }
        
    /**
     * get sippeer by name
     * 
     * @param string $_name the name of the sippeer
     * 
     * @return Voipmanager_Model_Asterisk_SipPeer
     */
    protected function _getSipPeer($_name) 
    {
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $_name
            )
        ));
        $sipPeers = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);
        
        return $sipPeers[0];
    }
    
    /**
     * get redirects by sippeer_id
     * 
     * @param string|Voipmanager_Model_Asterisk_SipPeer $_sipPeer the sippeer id|object
     * @return Voipmanager_Model_Asterisk_Redirect
     * 
     * @throws Voipmanager_Exception_NotFound
     */
    protected function _getRedirect($_sipPeer) 
    {
        $sipPeerId = Voipmanager_Model_Asterisk_SipPeer::convertAsteriskSipPeerIdToInt($_sipPeer);
        
        $filter = new Voipmanager_Model_Asterisk_RedirectFilter(array(
            array(
                'field'     => 'sippeer_id',
                'operator'  => 'equals',
                'value'     => $sipPeerId
            )
        ));
        $redirects = Voipmanager_Controller_Asterisk_Redirect::getInstance()->search($filter);
        
        if(count($redirects) === 0) {
            throw new Voipmanager_Exception_NotFound();
        }
        
        return $redirects[0];
    }
}