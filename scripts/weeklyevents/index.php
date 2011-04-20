<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<?php
	/*
	 * Anzeige der Belegung des Konferenzraumes auf dem Bildschirm
	 * Benutzte Tabellen:
	 * 		tine20_cal_events
	 */


	// Sprache von PHP auf Deutsch stellen
	setlocale(LC_TIME, 'de_DE');

	// Includieren der Config-Datei
	require_once('conf.php');

	// Variablen initialisieren
	$htmlVar1 = '';
	$htmlVar2 = '';
    $thisweekevents = 0;
    $nextweekevents = 0;
    
    $thisweekzaehler = 0;
    $nextweekzaehler = 0;
    


	// Datenbankverbindung mit Pear DB Objekt herstellen
	$dbConnect = 'mysql://'.$dbUser.':'.$dbPass.'@'.$dbServer.'/'.$dbBase;
	$db = MDB2::connect($dbConnect);

	if(PEAR::isError ($db))
	{
		die("Datenbankverbindung kann nicht hergestellt werden:<br>\n".$db->getMessage()."\n");
	}


	// Berechnung von Wochenanfang und Wochenende der aktuellen Woche
	$now = time();

	if (($dow = date('w', $now)) == 0)
	{
		$dow = 7;
	}

	$before = $now - (86400 * ($dow - 1));
	$then   = $now + (86400 * (7 - $dow));

	$firstDoW = mktime(0, 0, 0, date('m', $before), date('d', $before), date('Y', $before));
	$lastDoW  = mktime(23, 59, 59, date('m', $then), date('d', $then), date('Y', $then));
 
    $ende_dieser_woche = $lastDo;
    $ende_naechster_woche = mktime(47, 59, 59, date('m', $then), date('d', $then), date('Y', $then));

// Berechnung von Wochenanfang und Wochenende der naechsten Woche
	for($i = 0; $i < 8; $i++)
	{
		if($i == 7)
		{
			$firstDoWnext = strtotime("+" . $i . " day", $firstDoW); // Es werden zu dem Wochenanfang der aktuellen Woche 7 Tage addiert um auf den Wochenanfang der naechsten Woche zu gelangen
		}
		if($i == 7)
		{
			$lastDoWnext = strtotime("+" . $i . " day", $lastDoW); // Es werden zu dem Wochenende der aktuellen Woche 7 Tage addiert um auf das Wochenende der naechsten Woche zu gelangen
		}
	}





//Datumsberechnung und Umwandlung

                   $nowdate = date('l Y-d-m h:m:s');
                   $firstDoWDate = date('l Y-d-m h:m:s',$firstDoW);
                   $lastDoWDate = date('l Y-d-m h:m:s',$lastDoW);


//Datumsberechnung und Umwandlung  ENDE



// Aktuelle und naechste KW bestimmen
	$kwNow = date('W', $now);
	$kwNext = date('W', $firstDoWnext);
 
 
 // Temporäre konfi_tmp Tabelle in der Datenbank leeren
 
     $sql1 = "DELETE FROM konfi_tmp WHERE id <= " . $kwNext . "";
     mysql_query($sql1);


 // Datenbankabfrage für Konfi
	$thisWeekQuery = $db->queryAll(
		"SELECT DISTINCT
			tine20_cal_events.summary,
			tine20_cal_events.dtstart,
			tine20_cal_events.dtend,
			tine20_cal_events.rrule,
            tine20_cal_events.is_deleted,
            tine20_cal_events.rrule_until
		FROM tine20_cal_events
		WHERE
			(tine20_cal_events.container_id  = ".$container_konfi.") AND
            (tine20_cal_events.is_deleted = 0)
		ORDER BY tine20_cal_events.dtstart ASC;");




// Zähler für die Datensätze, die in den Zeitraum der 1 Woche fallen

foreach($thisWeekQuery as $thisWeekContent)
		{
                    // Umwandlung des Datums:

                    //Datumsberechnung -umwandlung -darstellung
                    $startzeit = mktime($thisWeekContent[1]);
                    $endzeit = mktime($thisWeekContent[2]);
                    

                    // Datum aus db aufsplitten
                    $datum_array = str_split($thisWeekContent[1]);
                    // Aufsplitten des Datums in Jahr Tag Monat Stunde Minute
                    $str_startdatum = $thisWeekContent[1];
                    $startjahr = $str_startdatum[0] . $str_startdatum[1] . $str_startdatum[2]. $str_startdatum[3];
                    $startmonat = $str_startdatum[5]. $str_startdatum[6];
                    $starttag = $str_startdatum[8]. $str_startdatum[9];
                    $startstunde = $str_startdatum[11]. $str_startdatum[12];
                    //Korrektur um eine Stunde und Sommerzeit
                    $startstunde = $startstunde + 1 + $sommerzeit;
                    $startminute = $str_startdatum[14]. $str_startdatum[15];
                    $startdatumanzeige = $starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                    // Berechnung der unixzeit
                    $wochentag = date("l", mktime(0, 0, 0, $startmonat, $starttag, $startjahr));
                    $startdatumanzeige = $wochentag.' '.$starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                    // Aufspitten der Uhrzeit von Endpunkt
                    $str_enddatum = $thisWeekContent[2];
                    $endstunde = $str_enddatum[11]. $str_enddatum[12];
                    //Korrektur um eine Stunde
                    $endstunde = $endstunde +1 + $sommerzeit;
                    $endminute = $str_enddatum[14]. $str_enddatum[15];
                    $endzeitanzeige = $endstunde.':'.$endminute;
                    $unixzeit_start = mktime($startstunde, $startminute, 0, $startmonat, $starttag, $startjahr);
                    $unixzeit_end = mktime($endstunde, $endminute, 0, $startmonat, $starttag, $startjahr);
                    $unixzeit_start2 = mktime($startstunde, $startminute, 0, $startmonat, $starttag, $startjahr);
                    $unixzeit_end2 = mktime($endstunde, $endminute, 0, $startmonat, $starttag, $startjahr);



                    // Aufsplitten des Datums für die rule_until Spalte in Jahr Tag Monat Stunde Minute
                    $str_startdatum_ruleend = $thisWeekContent[5];
                    $startjahr_ruleend = $str_startdatum_ruleend[0] . $str_startdatum_ruleend[1] . $str_startdatum_ruleend[2]. $str_startdatum_ruleend[3];
                    $startmonat_ruleend = $str_startdatum_ruleend[5]. $str_startdatum_ruleend[6];
                    $starttag_ruleend = $str_startdatum_ruleend[8]. $str_startdatum_ruleend[9];
                    $unixzeit_rule_end = mktime(0, 0, 0, $startmonat_ruleend, $starttag_ruleend, $startjahr_ruleend);
                    
                    /*test
                    echo '$unixzeit_rule_end:';
                    echo $unixzeit_rule_end;
                    echo '  -  $now:';
                    echo $now;
                    echo '<br>';
                     */
                    
                    // Terminangleich bei wiederholende Termine:

                    // Überprüfung ob wöchentlich:
                    

                    
                    if (preg_match("/weekly/i", $thisWeekContent[3]))
                       {

                          // Berechnung der wiederholten Termine bis zum Enddatum der letzten KW-Woche

                          if (preg_match("/INTERVAL=1/i", $thisWeekContent[3]))
                          {

                           while  (($unixzeit_start < $now) /*AND ($unixzeit_start > $firstDoW)*/)
                                  {
                              $unixzeit_start = $unixzeit_start + (7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (7 * 24 * 60 * 60);

                              }
                           }
                          if (preg_match("/INTERVAL=2/i", $thisWeekContent[3]))
                          {

                           while  (($unixzeit_start < $now) /*AND ($unixzeit_start > $firstDoW)*/)
                                  {
                              $unixzeit_start = $unixzeit_start + (2*7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (2*7 * 24 * 60 * 60);



                              }
                           }
                           }



// Wieviele Termine in dieser Woche

            if(($unixzeit_end >= time()) AND ($unixzeit_start <= $lastDoW ))
		           {
                              $thisweekevents++;
                                                            }
            if(($unixzeit_start >= $firstDoWnext) AND ($unixzeit_start <= $lastDoWnext ))
		           {
                                $nextweekevents++;

                                }
             if(($unixzeit_start2 >= $firstDoWnext) AND ($unixzeit_start2 <= $lastDoWnext ))
		           {
                                $nextweekevents++;
                                }


	if(count($thisweekevents) < 1)
	{
		$htmlVar1 = '<td valign="middle" align="center" style="font-size: 30px;">Keine Buchung f&uuml;r diese Woche vorhanden.</td>';
	}
	else
	{

          $thisweekevents++;

  }

}



// TODO




	// Zaehlvariable fuer die Trennlinie am Ende einer Zeile
	$j = 0;
	$k = 0;



	// HTML-String wird fuer spaetere Ausgabe zusammengebastelt
	if(count($thisWeekQuery) < 1)
	{
		$htmlVar1 = '<td valign="middle" align="center" style="font-size: 30px;">Keine Buchung f&uuml;r diese Woche vorhanden.</td>';
	}
	else
	{
		foreach($thisWeekQuery as $thisWeekContent)
		{

                         // Umwandlung des Datums:

                         //Datumsberechnung -umwandlung -darstellung
                         $startzeit = mktime($thisWeekContent[1]);
                         $endzeit = mktime($thisWeekContent[2]);

                         // Datum aus db aufsplitten

                         $datum_array = str_split($thisWeekContent[1]);
                         // Aufsplitten des Datums in Jahr Tag Monat Stunde Minute
                         $str_startdatum = $thisWeekContent[1];
                         $startjahr = $str_startdatum[0] . $str_startdatum[1] . $str_startdatum[2]. $str_startdatum[3];
                         $startmonat = $str_startdatum[5]. $str_startdatum[6];
                         $starttag = $str_startdatum[8]. $str_startdatum[9];
                         $startstunde = $str_startdatum[11]. $str_startdatum[12];
                         //Korrektur um eine Stunde
                         $startstunde = $startstunde +1 + $sommerzeit;
                         $startminute = $str_startdatum[14]. $str_startdatum[15];
                         $startdatumanzeige = $starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                         // Berechnung der unixzeit
                         $wochentag = date("l", mktime(0, 0, 0, $startmonat, $starttag, $startjahr));
                         $startdatumanzeige = $wochentag.' '.$starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                         // Aufspitten der Uhrzeit von Endpunkt
                         $str_enddatum = $thisWeekContent[2];
                         $endstunde = $str_enddatum[11]. $str_enddatum[12];
                         //Korrektur um eine Stunde
                         $endstunde = $endstunde +1  + $sommerzeit;
                         $endminute = $str_enddatum[14]. $str_enddatum[15];
                         $endzeitanzeige = $endstunde.':'.$endminute;
                         $unixzeit_start = mktime($startstunde, $startminute, 0, $startmonat, $starttag, $startjahr);
                         $unixzeit_end = mktime($endstunde, $endminute, 0, $startmonat, $starttag, $startjahr);
                         
                    // Aufsplitten des Datums für die rule_until Spalte in Jahr Tag Monat Stunde Minute
                    $str_startdatum_ruleend = $thisWeekContent[5];
                    $startjahr_ruleend = $str_startdatum_ruleend[0] . $str_startdatum_ruleend[1] . $str_startdatum_ruleend[2]. $str_startdatum_ruleend[3];
                    $startmonat_ruleend = $str_startdatum_ruleend[5]. $str_startdatum_ruleend[6];
                    $starttag_ruleend = $str_startdatum_ruleend[8]. $str_startdatum_ruleend[9];
                    $unixzeit_rule_end = mktime(0, 0, 0, $startmonat_ruleend, $starttag_ruleend, $startjahr_ruleend);


                    if (preg_match("/weekly/i", $thisWeekContent[3]))
                       {

                          // Berechnung der wiederholten Termine bis zum Enddatum der letzten KW-Woche

                          if (preg_match("/INTERVAL=1/i", $thisWeekContent[3]))
                          {

                           while  (($unixzeit_start < $now) /*AND ($unixzeit_start > $firstDoW)*/)
                                  {
                              $unixzeit_start = $unixzeit_start + (7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (7 * 24 * 60 * 60);

                              }
                           }
                          if (preg_match("/INTERVAL=2/i", $thisWeekContent[3]))
                          {

                           while  (($unixzeit_start < $now) /*AND ($unixzeit_start > $firstDoW)*/)
                                  {
                              $unixzeit_start = $unixzeit_start + (2*7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (2*7 * 24 * 60 * 60);



                              }
                           }
                           }


			// Ueberpruefung ob am Ende der Seite ein Strich zur Abtrennung eingezeichnet werden soll, oder nicht
			if($j+1 == $thisweekzaehler)
			{
				$hr1 = '';
			}
			else
			{
				$hr1 = 'class = "borderBottom"';
			}

			if($unixzeit_start <= time() AND $unixzeit_end >= time())
			{
				$blink = '<img src="img/dot.gif" height="30" width="66" />&nbsp; ';
			}
			else
			{
				$blink = '';
			}


	// Überprüfung ob es in die Kalenderwoche fällt oder nicht
	if(($unixzeit_end >= time()) AND ($unixzeit_start <= $lastDoW ))
		  {
            $thisweekzaehler++;
            $html1array = array ( $thisweekzaehler => array ( 'start' => $unixzeit_start,
                                     'end' => $unixzeit_end,
                                     'titel' => $thisWeekContent[0] ));


     //Daten in konfi_tmp zwischenspeichern
     $sql2 = "INSERT INTO konfi_tmp (id, start, end, titel) VALUES(";
     $sql2 .="'" . $kwNow . "',";
     $sql2 .="'" . $unixzeit_start . "',";
     $sql2 .="'" . $unixzeit_end . "',";
     $sql2 .="'" . $thisWeekContent[0] . "') ";
     mysql_query($sql2);



			$htmlVar1a .= '<tr>
				<td align="center" '.$hr1.'>
                <span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $unixzeit_start).' - '.strftime("%H:%M", $unixzeit_end).' Uhr</span>
				<br /><span style="font-size: '.$fontSize.'px;">'.$thisWeekContent[0].'</span>
				<br />
			</tr>
			';


			// Zaehlvariable fuer Trennlinie um 1 erhoehen
			$j++;

		  }

		}
	}


 
 
	if($nextweekevents < 1)
	{
		$htmlVar2 .= '<td valign="middle" align="center" style="font-size: 30px;">Keine Buchung f&uuml;r diese Woche vorhanden.</td>';
	}
	else
	{
		foreach($thisWeekQuery as $thisWeekContent)
		{
            // Umwandlung des Datums:

                    //Datumsberechnung -umwandlung -darstellung
                    $startzeit = mktime($thisWeekContent[1]);
                    $endzeit = mktime($thisWeekContent[2]);


                    // Datum aus db aufsplitten

                    $datum_array = str_split($thisWeekContent[1]);
                    // Aufsplitten des Datums in Jahr Tag Monat Stunde Minute
                    $str_startdatum = $thisWeekContent[1];
                    $startjahr = $str_startdatum[0] . $str_startdatum[1] . $str_startdatum[2]. $str_startdatum[3];
                    $startmonat = $str_startdatum[5]. $str_startdatum[6];
                    $starttag = $str_startdatum[8]. $str_startdatum[9];
                    $startstunde = $str_startdatum[11]. $str_startdatum[12];
                    //Korrektur um eine Stunde
                                $startstunde = $startstunde +1 + $sommerzeit;
                    $startminute = $str_startdatum[14]. $str_startdatum[15];
                    $startdatumanzeige = $starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                    // Berechnung der unixzeit
                    $wochentag = date("l", mktime(0, 0, 0, $startmonat, $starttag, $startjahr));
                    $startdatumanzeige = $wochentag.' '.$starttag.'.'.$startmonat.'.'.$startjahr.' '.$startstunde.':'.$startminute;
                    // Aufspitten der Uhrzeit von Endpunkt
                    $str_enddatum = $thisWeekContent[2];
                    $endstunde = $str_enddatum[11]. $str_enddatum[12];
                    //Korrektur um eine Stunde
                                $endstunde = $endstunde +1  + $sommerzeit;
                    $endminute = $str_enddatum[14]. $str_enddatum[15];
                    $endzeitanzeige = $endstunde.':'.$endminute;
                    $unixzeit_start = mktime($startstunde, $startminute, 0, $startmonat, $starttag, $startjahr);
                    $unixzeit_end = mktime($endstunde, $endminute, 0, $startmonat, $starttag, $startjahr);
                    
                    // Aufsplitten des Datums für die rule_until Spalte in Jahr Tag Monat Stunde Minute
                    $str_startdatum_ruleend = $thisWeekContent[5];
                    $startjahr_ruleend = $str_startdatum_ruleend[0] . $str_startdatum_ruleend[1] . $str_startdatum_ruleend[2]. $str_startdatum_ruleend[3];
                    $startmonat_ruleend = $str_startdatum_ruleend[5]. $str_startdatum_ruleend[6];
                    $starttag_ruleend = $str_startdatum_ruleend[8]. $str_startdatum_ruleend[9];
                    $unixzeit_rule_end = mktime(0, 0, 0, $startmonat_ruleend, $starttag_ruleend, $startjahr_ruleend);

                    // Terminangleich bei wiederholende Termine:

                    // Überprüfung ob wöchentlich:

                    if (preg_match("/weekly/i", $thisWeekContent[3]))
                       {
                          // Berechnung der wiederholten Termine bis zum Enddatum der letzten KW-Woche
                          
                          if (preg_match("/INTERVAL=1/i", $thisWeekContent[3]))
                           {
                           while  (($unixzeit_start <= $lastDoW))
                           {
                              $unixzeit_start = $unixzeit_start + (7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (7 * 24 * 60 * 60);
                              // echo '<span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $unixzeit_start).' - '.strftime("%H:%M", $unixzeit_end).' Uhr</span>';
                           }
                           }
                          if (preg_match("/INTERVAL=2/i", $thisWeekContent[3]))
                           {
                           while  (($unixzeit_start <= $lastDoW))
                           {
                              $unixzeit_start = $unixzeit_start + (2*7 * 24 * 60 * 60);
                              $unixzeit_end = $unixzeit_end + (2*7 * 24 * 60 * 60);
                              // echo '<span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $unixzeit_start).' - '.strftime("%H:%M", $unixzeit_end).' Uhr</span>';
                           }
                           }
                           }



			// Ueberpruefung ob am Ende der Seite ein Strich zur Abtrennung eingezeichnet werden soll, oder nicht
			if($k+1 == count($thisWeekQuery))
			{
				$hr2 = '';
			}
			else
			{
				$hr2 = 'class = "borderBottom"';
			}

	if(($unixzeit_start >= $firstDoWnext) AND ($unixzeit_start <= $lastDoWnext ))
		  {
            $nextweekzaehler++;


            
            
            $nextweekzaehlerarray = $nextweekzaehler;
            $html2array = array ( $nextweekzaehlerarray => array ( 'start' => $unixzeit_start,
                                     'end' => $unixzeit_end,
                                     'titel' => $thisWeekContent[0] ));
                                     
                                     
                 //Daten in konfi_tmp zwischenspeichern
                 $sql3 = "INSERT INTO konfi_tmp (id, start, end, titel) VALUES(";
                 $sql3 .="'" . $kwNext . "',";
                 $sql3 .="'" . $unixzeit_start . "',";
                 $sql3 .="'" . $unixzeit_end . "',";
                 $sql3 .="'" . $thisWeekContent[0] . "') ";
                 mysql_query($sql3);

                                     
			$htmlVar3 .= '<tr>
				<td align="center" '.$hr1.'>
				<span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $unixzeit_start).' - '.strftime("%H:%M", $unixzeit_end).' Uhr</span>
				<br />
				<span style="font-size: '.$fontSize.'px;">'.$thisWeekContent[0].'</span>
				</td>
			</tr>
			';


			// Zaehlvariable fuer Trennlinie um 1 erhoehen
			$k++;
		  }
    




		}
  

  
	}
 

	// Schriftgroesse anhand der Anzahl an Eintraegen festsetzen

 	if(($thisweekzaehler >= 1 AND $thisweekzaehler <= 5) OR ($nextweekzaehler >= 1 AND $nextweekzaehler <= 5))
	{
		$fontSize = '30';
	}

 	if(($thisweekzaehler > 5 AND $thisweekzaehler <= 7) OR ($nextweekzaehler > 5 AND $nextweekzaehler <= 7))
	{
		$fontSize = '25';
	}

  	if(($thisweekzaehler > 7 AND $thisweekzaehler <= 8) OR ($nextweekzaehler > 7 AND $nextweekzaehler <= 8))
	{
		$fontSize = '20';
	}

  	if(($thisweekzaehler > 8) OR ($nextweekzaehler > 8))
	if((count($thisweekzaehler) > 8) OR (count($thisweekzaehler) > 8))
	{
		$fontSize = '15';
	}


 /* Testeinbleindung
 echo '$thisweekzaehler: ';
 echo $thisweekzaehler;
 echo '  -  nextweekzaehler: ';
 echo $nextweekzaehler;
 echo '  -  $fontSize:';
 echo $fontSize;
 echo '<br>';
 */
 
 
 
 // Ausgabe des String vorbereiten
 
 
$resultthis = mysql_query("SELECT * FROM konfi_tmp where id = $kwNow ORDER BY konfi_tmp.start ASC");

while($row = mysql_fetch_array($resultthis))
  {
  $startzeit =  $row['start'];
  $endzeit =  $row['end'];
  $titel =  $row['titel'];

              $htmlVar1 .= '<tr>
				<td align="center" '.$hr1.'>
				<span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $startzeit).' - '.strftime("%H:%M", $endzeit).' Uhr</span>
				<br />
				<span style="font-size: '.$fontSize.'px;">'.$titel.'</span>
				</td>
			</tr>
			';
  }

$resultnext = mysql_query("SELECT * FROM konfi_tmp where id = $kwNext ORDER BY konfi_tmp.start ASC");

while($row = mysql_fetch_array($resultnext))
  {
  $startzeit =  $row['start'];
  $endzeit =  $row['end'];
  $titel =  $row['titel'];

              $htmlVar2 .= '<tr>
				<td align="center" '.$hr1.'>
				<span style="font-size: '.$fontSize.'px; font-weight: bold;">'.strftime("%A, %e. %B %G %H:%M", $startzeit).' - '.strftime("%H:%M", $endzeit).' Uhr</span>
				<br />
				<span style="font-size: '.$fontSize.'px;">'.$titel.'</span>
				</td>
			</tr>
			';
  }


 
 	// Verbindung zur Datenbank trennen
	$db->disconnect();
?>


<html>
<head>
<meta http-equiv="refresh" content="<?php echo $refreshTime*60; ?>;">
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<style type="text/css">
body, td {
	background-color: #FFFFFF;
	color: #000000;
	font-family: Arial;
}
h1, th {
	text-align: center;
	font-size: 30;
	font-weight: normal;
	letter-spacing: 3px;
	color: gray;
	font-family: Arial;
}
hr {
	margin-top: 10px;
}
.borderRight {
	border-width: thin;
	border-color: gray;
	border-right-style: solid;
	border-top-style: none;
	border-left-style: none;
	border-bottom-style: none;
}
.borderBottom {
	border-width: thin;
	border-color: gray;
	border-right-style: none;
	border-top-style: none;
	border-left-style: none;
	border-bottom-style: solid;
}
</style>
</head>
<body>
<table width="100%" height="1020px" border="0">
	<tr>
		<td colspan="2" height="80" align="center"><img src="img/logo_medgen.jpg" height="80" width="80" /><img src="img/logo_imgm.gif" height="80" width="155" /></td>
	</tr>
	<tr>
		<th width="50%" height="60" valign="bottom">Konferenzraumbelegung KW <?php echo $kwNow; ?></th>
		<th width="50%" height="60" valign="bottom">Konferenzraumbelegung KW <?php echo $kwNext; ?></th>
	</tr>
 	<tr>
		<td class="borderRight">
			<table height="100%" border="0" align="center"><?php echo $htmlVar1; ?></table>
		</td>
		<td>
			<table height="100%" border="0" align="center"><?php echo $htmlVar2; ?></table>
		</td>
	</tr>
</table>
</body>
</html>

