<?php
/**
 * json frontend for Felamimail
 *
 * This class handles all Json requests for the Felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Felamimail';
    
    public function getEmailOverview($accountId, $folderName, $filter, $sort, $dir, $limit, $start)
    {
        $controller = new Felamimail_Controller();

        $result = array(
            'results'   => $controller->getEmailOverview($accountId, $folderName, $filter, $sort, $dir, $limit, $start)
        );
        
        foreach($result['results'] as $key => $message) {
            $result['results'][$key]['sent']     = $message['sent']->get(ISO8601LONG);
            $result['results'][$key]['received'] = $message['received']->get(ISO8601LONG);
        }
        
        return $result;
    }
    
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
    public static function getInitialTree()
    {
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
	}
}