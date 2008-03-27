<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.user_tine2typo = < plugin.user_tine2typo.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.user_tine2typo.php','','list_type',0);
?>
