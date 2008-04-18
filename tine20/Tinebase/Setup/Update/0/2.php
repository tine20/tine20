<?php


class Tinebase_Setup_Update_0_2 extends Setup_Update_Common
{
	
	
	public function updateFrom1to2()
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
				break;
			}
		}
	}
} 