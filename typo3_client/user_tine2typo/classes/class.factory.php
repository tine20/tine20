<?php


class Factory
{
	public static function createDB( $type = 'PDO' )
	{
		include( PATH_site . '/typo3conf/localconf.php' );

		if( !isset( $typo_db_adapter ) ) { $typo_db_adapter = 'mysql'; }

		switch( $typo_db_adapter )
		{
			case 'mysql':
				
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
				print_r($e);
				}
			default:
				
				throw new Exception( 'Unknown database type: ' . $type );
		}
	}
}
