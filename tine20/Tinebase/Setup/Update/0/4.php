<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Setup_Update
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Abstract.php 1013 2008-03-11 21:45:31Z nelius_weiss $
 */


class Tinebase_Setup_Update_0_4 extends Setup_Update_Common
{
	
	public function updateFrom1to2()
	{
		
	}
	
	public function updateFrom2to3()
	{
		
	}
	
	public function updateFrom3to4()
	{
		
	}
	
	public function __construct($_backend)
	{
		$this->backend = $_backend;
	}

	public function make()
	{ 
		// check present installed application
		$presentVersion = $this->getApplicationVersion('Tinebase');
		
		switch($presentVersion)
		{
			case('0.1'):
			{
				$this->updateFrom1to2();
				//NO BREAK!
			}
			case('0.2'):
			{
				$this->updateFrom2to3();
				//NO BREAK!
			}
			case('0.3'):
			{
				$this->updateFrom3to4();
				//NO BREAK!
			}
		}
	}
} 