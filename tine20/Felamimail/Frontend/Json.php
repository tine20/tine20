<?php
/**
 * json frontend for Felamimail
 *
 * This class handles all Json requests for the Felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add functions for rename/create folders
 * @todo        remove deprecated code
 */
class Felamimail_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /***************************** folder funcs *******************************/
    /***************************** folder funcs *******************************/
    
    /**
     * search folders
     *
     * @param string $filter
     * @return array
     */
    public function searchFolders($filter)
    {
        return $results = $this->_search($filter, '', Felamimail_Controller_Folder::getInstance(), 'Felamimail_Model_FolderFilter');
    }

    /**
     * delete folder
     *
     * @param string $folder the folder global name to delete
     * @param string $backendId
     * @return array
     */
    public function deleteFolder($folder, $backendId)
    {
        $result = Felamimail_Controller_Folder::getInstance()->delete($folder, $backendId);

        return array(
            'status'    => ($result) ? 'success' : 'failure'
        );
    }
    
    /***************************** messages funcs *******************************/
    /***************************** messages funcs *******************************/
    
    /**
     * search messages
     *
     * @param string $filter
     * @param string $paging
     * @return array
     */
    public function searchMessages($filter, $paging)
    {
        return $this->_search($filter, $paging, Felamimail_Controller_Message::getInstance(), 'Felamimail_Model_MessageFilter');
    }

    /***************************** old funcs *******************************/
    
    /**
     * get email overview
     *
     * @param unknown_type $accountId
     * @param unknown_type $folderName
     * @param unknown_type $filter
     * @param unknown_type $sort
     * @param unknown_type $dir
     * @param unknown_type $limit
     * @param unknown_type $start
     * @return unknown
     * 
     * @deprecated 
     */
    public function getEmailOverview($accountId, $folderName, $filter, $sort, $dir, $limit, $start)
    {
        $controller = new Felamimail_Controller();

        $result = array(
            'results'   => $controller->getEmailOverview($accountId, $folderName, $filter, $sort, $dir, $limit, $start)
        );
        
        foreach($result['results'] as $key => $message) {
            $result['results'][$key]['sent']     = $message['sent']->get(Tinebase_Record_Abstract::ISO8601LONG);
            $result['results'][$key]['received'] = $message['received']->get(Tinebase_Record_Abstract::ISO8601LONG);
        }
        
        return $result;
    }
    
	/**
	 * get subfolders for specified folder
	 *
	 * @param unknown_type $accountId
	 * @param unknown_type $location
	 * @param unknown_type $folderName
	 * 
	 * @deprecated 
	 */
	public function getSubTree($accountId, $location, $folderName) 
	{
	    /*
		$nodes = array();

		$controller = new Felamimail_Controller();
		$accounts = $controller->getListOfAccounts();
		
		try {
			$mail = new Felamimail_Imap($accounts[$accountId]->toArray());
			
			if(empty($folderName)) {
				$folder = $mail->getFolders('', '%');
			} else {
				$folder = $mail->getFolders($folderName.'/', '%');
			}
			
			//error_log(print_r($folder, true));
			
			foreach($folder as $folderArray) {
				$treeNode = new Tinebase_Ext_Treenode(
					'Felamimail', 
					'email', 
					$folderArray['globalName'], 
					$folderArray['localName'], 
					!$folderArray['hasChildren']
				);
				$treeNode->contextMenuClass = 'ctxMenuTreeFellow';
                $treeNode->accountId  = $accountId;
                $treeNode->folderName = $folderArray['globalName'];
				$nodes[] = $treeNode;
				
			}
			
		} catch (Exception $e) {
			error_log('ERROR: '. $e->getMessage());
		}
		
		echo Zend_Json::encode($nodes); 
        */

		// exit here, as the Zend_Server's  processing is adding a result code, which breaks the result array
		exit;
	}
	
    /**
     * Returns the structure of the initial tree (email accounts) for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     * 
     * @deprecated 
     */
    public static function getInitialTree()
    {
        /*
        $controller = new Felamimail_Controller();
        $accounts = $controller->getListOfAccounts();        
        
        $treeNodes = array();
        
        foreach($accounts as $id => $accountData) {
            $treeNode = new Tinebase_Ext_Treenode('Felamimail', 'email', $id, $accountData->name, FALSE);
            $treeNode->accountId = $id;
            $treeNode->folderName = '';
            $treeNodes[] = $treeNode;
        }

		return $treeNodes;
        */
	}
	
	/**
     * Returns registry data of felamimail.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     * @todo rework that
     */
    public function getRegistryData()
    {
        //return array('initialTree' => self::getInitialTree());
        return array();
    }
    
    /**
     * send mail
     *
     * @todo rework that
     */
    public function sendMail($message)
    {
        $message = Zend_Json::decode($message);
        
        $mail = new Zend_Mail('utf-8');
        
        $mail->setFrom('somebody@example.com', 'somebodys name')
            ->setSubject('TestBetreff')
            ->setBodyText('Dies ist der Text dieser E-Mail.')
            ->setBodyHtml('Dies ist der <b>Text</b> dieser E-Mail.');
        
        $mail->addTo('somebody_else@example.com', 'Ein Empfänger');
        /****************************************************
            $mail->addCc('somebody_else@example.com', 'Ein Empfänger');
            $mail->addBcc('somebody_else@example.com', 'Ein Empfänger');
            $at = new Zend_Mime_Part($myImage);
            $at->type        = 'image/gif';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_8BIT;
            $at->filename    = 'test.gif';
            $message->addAttachment($at);        
        *****************************************************/

        Felamimail_Controller_Message::getInstance()->sendMessage($mail);
    }
}