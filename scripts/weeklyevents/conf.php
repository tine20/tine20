<?php
	/*
	 * Config-Datei fÃ¼r die Ausgabe der Konferenzraumbelegung
	 */

	// Pear DB Objekt wird geladen
	require_once('MDB2.php');
	
	
	// Variablen
	$dbUser = 'tine20_konfi';		// User der sich an der Datenbank anmeldet
	$dbPass= 'xxxxxxx';			// Passwort, welches der User zur Authentifizierung an der Datenbank verwendet
	$dbServer = 'localhost';		// Datenbankserver
	$dbBase = 'tine20'; 		// Datenbank die Verwendet wird
	
	$refreshTime = 5; // Zeit in Minuten in der sich die Anzeige aktualisiert
	$calOwner = 507; // UserID des Users welcher den Konferenzraum darstellt
	$container_konfi = 378;  // Container-ID in der Tabelle tine20_cal_events ist für den Konfi zuständig
	//$sommerzeit = date('I', $now); //Einstellung der Sommerzeit
	$sommerzeit = 1;
?>
