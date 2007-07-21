<?php
class Asterisk_Json
{
	const SORTDESC = 'DESC';
	const SORTASC = 'ASC';
	                
	function getData() 
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
		
		echo Zend_Json::encode($result);
	}
	
	function getTree() 
	{
		$nodes = array();
		$nodeID = $_REQUEST['node'];
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
		
		echo Zend_Json::encode($nodes);
	}
}
?>