<?php
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
