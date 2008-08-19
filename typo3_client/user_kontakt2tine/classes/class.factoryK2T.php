<?php
/***************************************************************
*  Copyright notice
*
* @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'user_tine2typo' for the 'user_tine2typo' extension.
 *
 * @author  Matthias Greiling <typo3@metaways.de>
 * @comment this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */
 
class FactoryK2T
{
	public static function createDB( $type = 'PDO' )
	{
		include( PATH_site . 'typo3conf/localconf.php' );

		if( !isset( $typo_db_adapter ) ) { $typo_db_adapter = 'mysql'; }

		switch( $typo_db_adapter )
		{
			case 'PDO':
				
				try{
					$conf = array(
						'dsn' => 'mysql:host=' . $typo_db_host. ';dbname=' . $typo_db,
						'user' => $typo_db_username,
						'pass' => $typo_db_password,
						'attr' => array(
							PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
// 						PDO::ATTR_PERSISTENT => true,
						),
					);

					$dbh = new PDO( $conf['dsn'], $conf['user'], $conf['pass'], $conf['attr'] );
					$dbh->exec( "SET NAMES 'utf8'" );

					return $dbh;
				}
				catch (Exception $e)
				{
				print_r($e->getMessage());
				}
				break;
			case 'mysql':{
			
				
			}	
				
			default:
				
				throw new Exception( 'Unknown database type: ' . $type );
		}
	}
}
