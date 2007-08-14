<?php
class Egwbase_Ext_Treenode
{
	public $text;
	
	public $cls = 'file';
	
	public $allowDrag = FALSE;
	
	public $allowDrop = TRUE;
	
	public $id;
	
	public $icon = FALSE;
	
	public $application;
	
	public $datatype = 'overview';
	
	public $children;
	
	public $leaf;
	
	public $contextMenuClass;
	
	public function __construct($_application, $_datatype, $_id, $_text, $_isLeaf) 
	{
		$this->application = $_application;
		$this->datatype	= $_datatype;
		$this->text	= $_text;
		$this->id	= $_id;
		//$this->leaf	= $_isLeaf;
                if($_isLeaf) {
                $this->children = array();
                $this->expanded = TRUE;
                }
	}
	
	public function addChildren(Egwbase_Ext_Treenode $_children)
	{
		$this->children[] = $_children;
	}
	
	public function setIcon($_icon)
	{
		$this->icon = 'images/oxygen/16x16/'. $_icon;
	}
}
?>