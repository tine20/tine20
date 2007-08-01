<?php

class Asterisk_Snom
{
	const SORTDESC = 'DESC';
	const SORTASC = 'ASC';

	public function getPhones($_quickFilter = NULL, $_order = NULL, $_sort = self::SORTASC, $_count = NULL, $_offset = NULL) {
		$table = new Asterisk_Snomphones();
		
		if($rows = $table->fetchAll(NULL, $_order.' '.$_sort, $_count, $_offset)) {
			foreach($rows as $row) {
				echo "{$row->description}<br>";
			}
		}
	}
}

?>
        
        