<?php
class Addressbook_Json
{
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