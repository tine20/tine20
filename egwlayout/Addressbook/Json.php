<?php
class Addressbook_Json
{
	protected $userEditableFields = array(
		'n_prefix','n_given','n_middle','n_family','n_suffix','contact_title','contact_role','contact_room','contact_email','contact_email_home','contact_url','contact_url_home','org_name','org_unit','adr_one_street','adr_one_street2','adr_one_postalcode','adr_one_locality','adr_one_region','adr_one_countryname','adr_two_street','adr_two_street2','adr_two_postalcode','adr_two_locality','adr_two_region','adr_two_countryname','contact_bday','tel_work','tel_cell','tel_fax','tel_car','tel_pager','contact_assistent','tel_assistent','tel_home','tel_cell_private','tel_fax_home'
	);
	
	public function readAddress() 
	{
		$id = $_REQUEST['id'];
		$addresses = new Addressbook_Addresses();
		if($rows = $addresses->find($id)) {
			$result['results'] = $rows->toArray();
		}
		
		echo Zend_Json::encode($result);
	}
	
	public function saveAddress() 
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