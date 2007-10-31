<?php
/**
 * json frontend for Felamimail
 *
 * This class handles all Json requests for the FeLaMiMail application
 *
 * @package     FeLaMiMail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Json
{
	/**
	 * get subfolders for specified folder
	 *
	 * @param unknown_type $accountId
	 * @param unknown_type $location
	 * @param unknown_type $folderName
	 */
	public function getSubTree($accountId, $location, $folderName) 
	{
		$nodes = array();
		$nodeID = $nodeId;

		$controller = new Felamimail_Controller();
		$accounts = $controller->getListOfAccounts();
		
		error_log("reading folder for $nodeID");

		try {
			$mail = new Zend_Mail_Storage_Imap($accounts[$accountId]->toArray());
			
			if(empty($folderName)) {
				$folder = $mail->getFolders('', '%');
			} else {
				$folder = $mail->getFolders($folderName.'/', '%');
			}
			
			//error_log(print_r($folder, true));
			
			foreach($folder as $folderObject) {
				#error_log("{$folderObject->getLocalName()} - {$folderObject->getGlobalName()} - {$folderObject->isLeaf()} - {$folderObject->hasChildren()}");
				$treeNode = new Egwbase_Ext_Treenode(
					'Felamimail', 
					'email', 
					$folderObject->getGlobalName(), 
					$folderObject->getLocalName(), 
					!$folderObject->hasChildren()
				);
				$treeNode->contextMenuClass = 'ctxMenuTreeFellow';
                $treeNode->accountId = $accountId;
                $treeNode->folderName = $folderObject->getGlobalName();
				$nodes[] = $treeNode;
				
			}
			
		} catch (Exception $e) {
			error_log('ERROR: '. $e->getMessage());
		}
		
		echo Zend_Json::encode($nodes); 

		// exit here, as the Zend_Server's  processing is adding a result code, which breaks the result array
		exit;
	}
	
    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     */
    public function getInitialTree($_location)
    {
        $controller = new Felamimail_Controller();
        $accounts = $controller->getListOfAccounts();        
        
        $treeNodes = array();
        
        foreach($accounts as $id => $accountData) {
            $treeNode = new Egwbase_Ext_Treenode('Felamimail', 'email', $id, $accountData->name, FALSE);
            $treeNode->accountId = $id;
            $treeNode->folderName = '';
            $treeNodes[] = $treeNode;
        }

		return $treeNodes;
	}
}