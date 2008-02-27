<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Ext
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Tinebase_Ext_Treenode
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
	
	public function addChildren(Tinebase_Ext_Treenode $_children)
	{
		$this->children[] = $_children;
	}
	
	public function setIcon($_icon)
	{
		$this->icon = 'images/oxygen/16x16/'. $_icon;
	}
}
?>