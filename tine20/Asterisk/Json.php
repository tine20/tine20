<?php
class Asterisk_Json
{
	const SORTDESC = 'DESC';
	const SORTASC = 'ASC';
	                
	public function getData() 
	{
		$offset		= $_REQUEST['start'];
		$sort		= $_REQUEST['sort'];
		$order		= $_REQUEST['dir'];
		$count		= $_REQUEST['limit'];
		$datatype	= $_REQUEST['datatype'];
		error_log("OFFSET: $offset SORT: $sort ORDER: $order COUNT: $count DATATYPE: $datatype");

		$result = array();

		switch($datatype) {
			case 'classes':
				$snomClasses = new Asterisk_Snomclasses();
				if($rows = $snomClasses->fetchAll(NULL, "$sort $order", $count, $offset)) {
					$result['results'] = $rows->toArray();
					$result['totalcount'] = $snomClasses->getTotalCount();
				}
				
				break;

			case 'phones':
				$snomPhones = new Asterisk_Snomphones();
				if($rows = $snomPhones->fetchAll(NULL, "$sort $order", $count, $offset)) {
					$result['results'] = $rows->toArray();
					$result['totalcount'] = $snomPhones->getTotalCount();
				}
				
				break;

			case 'software':
				$snomSoftware = new Asterisk_Snomsoftware();
				if($rows = $snomSoftware->fetchAll(NULL, "$sort $order", $count, $offset)) {
					$result['results'] = $rows->toArray();
					$result['totalcount'] = $snomSoftware->getTotalCount();
				}
				
				break;
		}
		
		return $result;
		echo Zend_Json::encode($result);
	}
	
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
				$nodes[] = array(
					'text'	=> $folderObject->getLocalName(), 
					'id'	=> $folderObject->getGlobalName(), 
					'leaf'	=> !$folderObject->hasChildren(),
					'cls'	=> 'file', 
					'contextMenuClass' =>'ctxMenuTreeFellow',
					'application' => 'Felamimail_Json');
				
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
		
		return $nodes;
		echo Zend_Json::encode($nodes);
	}

	public function getMainTree() 
	{
		$treeNode = new Tinebase_Ext_Treenode('Asterisk', 'overview', 'asterisk', 'Asterisk', FALSE);
		$treeNode->setIcon('apps/kcall.png');
		$treeNode->cls = 'treemain';

		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'phones', 'phones', 'Phones', TRUE);
		$treeNode->addChildren($childNode);

		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'lines', 'lines', 'Lines', TRUE);
		$treeNode->addChildren($childNode);
		
		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'classes', 'classes', 'Classes', TRUE);
		$treeNode->addChildren($childNode);
		
		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'config', 'config', 'Config', TRUE);
		$treeNode->addChildren($childNode);
		
		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'settings', 'settings', 'Settings', TRUE);
		$treeNode->addChildren($childNode);
		
		$childNode = new Tinebase_Ext_Treenode('Asterisk', 'software', 'software', 'Software', TRUE);
		$treeNode->addChildren($childNode);
		
		return $treeNode;
	}
}
?>