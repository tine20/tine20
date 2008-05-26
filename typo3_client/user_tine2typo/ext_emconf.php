<?php

########################################################################
# Extension Manager/Repository config file for ext: "user_tine2typo"
#
# Auto generated 19-05-2008 18:02
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tine 2.0 Adressbuch auslesen',
	'description' => 'Making TINE 2.0 accessible',
	'category' => 'plugin',
	'author' => 'Metaways',
	'author_email' => 'typo3@metaways.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'_md5_values_when_last_written' => 'a:74:{s:9:"ChangeLog";s:4:"d540";s:17:"constants_inc.php";s:4:"5184";s:12:"ext_icon.gif";s:4:"1cfe";s:17:"ext_localconf.php";s:4:"b45f";s:14:"ext_tables.php";s:4:"7d5e";s:16:"locallang_db.xml";s:4:"44df";s:25:"classes/class.factory.php";s:4:"09eb";s:42:"classes/class.tx_DynamicFlexFormFields.php";s:4:"4d2c";s:19:"doc/wizard_form.dat";s:4:"f023";s:20:"doc/wizard_form.html";s:4:"de6a";s:14:"pi1/ce_wiz.gif";s:4:"d8b2";s:28:"pi1/class.user_tine2typo.php";s:4:"017f";s:40:"pi1/class.user_tine2typo_pi1_wizicon.php";s:4:"8f09";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/template.html";s:4:"a590";s:27:"pi1/Addressbook/Service.php";s:4:"fdff";s:33:"pi1/Addressbook/Model/Contact.php";s:4:"0bfd";s:32:"pi1/Tinebase/Record/Abstract.php";s:4:"54b6";s:33:"pi1/Tinebase/Record/Interface.php";s:4:"aced";s:42:"pi1/Tinebase/Record/PersistentObserver.php";s:4:"87db";s:33:"pi1/Tinebase/Record/RecordSet.php";s:4:"15b1";s:32:"pi1/Tinebase/Record/Relation.php";s:4:"7a9a";s:38:"pi1/Tinebase/Record/Event/Abstract.php";s:4:"dce6";s:36:"pi1/Tinebase/Record/Event/Delete.php";s:4:"4b1c";s:39:"pi1/Tinebase/Record/Event/Predelete.php";s:4:"5a7b";s:39:"pi1/Tinebase/Record/Event/Preupdate.php";s:4:"a28c";s:44:"pi1/Tinebase/Record/Event/Relationchange.php";s:4:"a182";s:36:"pi1/Tinebase/Record/Event/Update.php";s:4:"6223";s:51:"pi1/Tinebase/Record/Exception/DefinitionFailure.php";s:4:"ca0a";s:44:"pi1/Tinebase/Record/Exception/NotAllowed.php";s:4:"7c73";s:44:"pi1/Tinebase/Record/Exception/NotDefined.php";s:4:"e4f5";s:44:"pi1/Tinebase/Record/Exception/Validation.php";s:4:"fed4";s:29:"pi1/TineClient/Connection.php";s:4:"a5b9";s:35:"pi1/TineClient/Service/Abstract.php";s:4:"ddab";s:22:"pi1/Zend/Exception.php";s:4:"6f30";s:19:"pi1/Zend/Filter.php";s:4:"e62d";s:17:"pi1/Zend/Json.php";s:4:"c9ef";s:19:"pi1/Zend/Loader.php";s:4:"1b07";s:21:"pi1/Zend/Registry.php";s:4:"338e";s:16:"pi1/Zend/Uri.php";s:4:"f0ff";s:21:"pi1/Zend/Validate.php";s:4:"fbaa";s:26:"pi1/Zend/Filter/Digits.php";s:4:"7054";s:29:"pi1/Zend/Filter/Exception.php";s:4:"7291";s:25:"pi1/Zend/Filter/Input.php";s:4:"1670";s:29:"pi1/Zend/Filter/Interface.php";s:4:"379a";s:33:"pi1/Zend/Filter/StringToLower.php";s:4:"43ee";s:33:"pi1/Zend/Filter/StringToUpper.php";s:4:"860f";s:30:"pi1/Zend/Filter/StringTrim.php";s:4:"c457";s:24:"pi1/Zend/Http/Client.php";s:4:"93f9";s:24:"pi1/Zend/Http/Cookie.php";s:4:"0ecd";s:27:"pi1/Zend/Http/CookieJar.php";s:4:"cbf1";s:27:"pi1/Zend/Http/Exception.php";s:4:"83b0";s:26:"pi1/Zend/Http/Response.php";s:4:"48bb";s:34:"pi1/Zend/Http/Client/Exception.php";s:4:"fddd";s:42:"pi1/Zend/Http/Client/Adapter/Exception.php";s:4:"004f";s:42:"pi1/Zend/Http/Client/Adapter/Interface.php";s:4:"5578";s:38:"pi1/Zend/Http/Client/Adapter/Proxy.php";s:4:"48e1";s:39:"pi1/Zend/Http/Client/Adapter/Socket.php";s:4:"e029";s:37:"pi1/Zend/Http/Client/Adapter/Test.php";s:4:"e555";s:25:"pi1/Zend/Json/Decoder.php";s:4:"8681";s:25:"pi1/Zend/Json/Encoder.php";s:4:"39a6";s:27:"pi1/Zend/Json/Exception.php";s:4:"9ad4";s:29:"pi1/Zend/Loader/Exception.php";s:4:"045c";s:32:"pi1/Zend/Loader/PluginLoader.php";s:4:"4671";s:42:"pi1/Zend/Loader/PluginLoader/Exception.php";s:4:"c7d8";s:42:"pi1/Zend/Loader/PluginLoader/Interface.php";s:4:"f92e";s:26:"pi1/Zend/Uri/Exception.php";s:4:"59b0";s:21:"pi1/Zend/Uri/Http.php";s:4:"89dc";s:30:"pi1/Zend/Validate/Abstract.php";s:4:"a663";s:28:"pi1/Zend/Validate/Digits.php";s:4:"cada";s:33:"pi1/Zend/Validate/GreaterThan.php";s:4:"6fc4";s:30:"pi1/Zend/Validate/Hostname.php";s:4:"9335";s:31:"pi1/Zend/Validate/Interface.php";s:4:"1e1b";s:24:"pi1/Zend/Validate/Ip.php";s:4:"3b80";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>