<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.user_kontakt2tine = < plugin.user_kontakt2tine.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.user_kontakt2tine.php','','list_type',0);
?>
