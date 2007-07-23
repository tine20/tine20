<?php
class Addressbook_Json
{
	protected $userEditableFields = array(
		'n_given','n_family','org_name','contact_email'
	);
	
	public function editAddress() 
	{
		$address = new Addressbook_Addresses();
		
		foreach($this->userEditableFields as $fieldName) {
			$data[$fieldName]	= $_REQUEST[$fieldName];
		}
		
		if(isset($_REQUEST['id'])) {
		} else {
			try {
				$address->insert($data);
				$result = array(
					'success'	=> true,
					'welcomeMessage' => 'Entry saved'
				);
			} catch (Exception $e) {
				$result = array(
					'success'	=> false,
					'errorMessage'	=> $e->getMessage()
				);
			}
		}
		
		
		echo Zend_Json::encode($result);
	}
	
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
			case 'address':
				$snomClasses = new Addressbook_Addresses();
				if($rows = $snomClasses->fetchAll(NULL, "$sort $order", $count, $offset)) {
					$result['results'] = $rows->toArray();
					$result['totalcount'] = $snomClasses->getTotalCount();
				}
				
				break;
		}
		
		echo Zend_Json::encode($result);
	}
	
	public function getMainTree() 
	{
		$treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'Addressbook', FALSE);
		$treeNode->setIcon('apps/kaddressbook.png');
		$treeNode->cls = 'treemain';

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'myaddresses', 'My Addresses', TRUE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'internaladdresses', 'My Fellows', TRUE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'fellowsaddresses', 'Fellows Addresses', FALSE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'sharedaddresses', 'Shared Addresses', FALSE);
		$treeNode->addChildren($childNode);

		return $treeNode;
	}
}
?>