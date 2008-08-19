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
$kontaktnumlinks = 5;

function user_kontakt2tine_linkliste() {
  global $kontaktnumlinks;
  $linkliste = '';
  for ($i = 1; $i < ($kontaktnumlinks + 1); $i++) {
    $linkliste .= '
	<auswahl_'. $i . '>
      <TCEforms>
        <label>Thema der Anfrage '.$i.'</label>
          <config>
            <type>input</type>
            <size>50</size>
          </config>
      </TCEforms>
    </auswahl_' . $i . '>	
	';
  }
  return $linkliste;
}
?>
