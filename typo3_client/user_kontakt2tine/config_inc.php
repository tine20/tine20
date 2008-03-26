<?php
$kontaktnumlinks = 5;

function user_kontakt2tine_linkliste() {
  global $kontaktnumlinks;
  $linkliste = '';
  for($i=0;$i<$kontaktnumlinks;$i++) {
    $linkliste .= '
	<auswahl_'.($i+1).'>
      <TCEforms>
        <label>Thema der Anfrage '.($i+1).'</label>
          <config>
            <type>input</type>
            <size>50</size>
          </config>
      </TCEforms>
    </auswahl_'.($i+1).'>	
	';
  }
  return $linkliste;
}
?>
