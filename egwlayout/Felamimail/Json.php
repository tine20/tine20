<?php
class Felamimail_Json
{
	function __construct() {
		$options = new Zend_Config_Ini('../../config.ini', 'database');
		$db = Zend_Db::factory('PDO_MYSQL', $options->toArray());
		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		
		$table = new Asterisk_Snomlines();
		
		if($rows = $table->fetchAll(NULL, 'user_realname1 DESC', 10, 10)) {
		
			foreach($rows as $row) {
				error_log($row->user_realname1);
			}

		}
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