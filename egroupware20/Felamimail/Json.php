<?php
class Felamimail_Json
{
	public function getTree($nodeid) 
	{
		$nodes = array();
		$nodeID = $nodeid;
		$config = new Zend_Config_Ini('../../config.ini', 'felamimail');

		error_log("reading folder for $nodeID");

		try {
			$mail = new Zend_Mail_Storage_Imap($config->toArray());
			
			if($nodeID == 'mailbox1' || $nodeID == 'mailbox2') {
				$folder = $mail->getFolders('', '%');
			} else {
				$folder = $mail->getFolders($nodeID.'/', '%');
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
				$nodes[] = $treeNode;
				
			}
			
		#	$nameSpaces = $mail->getNameSpace();
		#	
		#	foreach($nameSpaces as $nameSpace) {
		#	
		#		$basePath = (empty($nameSpace['path']) ? $nameSpace['path'] : $nameSpace['path'] . $nameSpace['delimiter']);
		#	
		#		try {
		#			$topfolder = $mail->getFolders($basePath, '%');
		#			foreach($topfolder as $folder) {
		#				//print $folder."<br>";
		#			}
		#		} catch (Exception $e) {
		#		}
		#	}
		} catch (Exception $e) {
			error_log('ERROR: '. $e->getMessage());
		}
		
		echo Zend_Json::encode($nodes); 

		// exit here, as the Zend_Server's  processing is adding a result code, which breaks the result array
		exit;
	}
	
	public function getMainTree() 
	{
		$treeNode = new Egwbase_Ext_Treenode('Felamimail', 'overview', 'email', 'Email', FALSE);
		$treeNode->setIcon('apps/kmail.png');
		$treeNode->cls = 'treemain';

		$childNode = new Egwbase_Ext_Treenode('Felamimail', 'email', 'mailbox1', 'l.kneschke@officespot.net', FALSE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Felamimail', 'email', 'mailbox2', 'lars@kneschke.de', FALSE);
		$treeNode->addChildren($childNode);

		return $treeNode;
	}
}
?>