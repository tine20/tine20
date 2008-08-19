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
 

class user_tine2typo_pi1_wizicon {


	function includeLocalLangTINE() {
        $typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);

        if ($typoVersion >= 3008000) {
            $LOCAL_LANG = $GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('user_tine2typo').'locallang_db.xml',FALSE);
        } else {
            include(t3lib_extMgm::extPath('user_tine2typo').'locallang_db.xml');
        }
        return $LOCAL_LANG;
    }


	function proc($wizardItems)	{
		global $LANG;

		$LL = $this->includeLocalLangTINE();

		$wizardItems['plugins_user_tine2typo'] = array(
			'icon'=>t3lib_extMgm::extRelPath('user_tine2typo').'pi1/ce_wiz.gif',
			'title'=>$LANG->getLLL('pi1_title',$LL),
			'description'=>$LANG->getLLL('pi1_plus_wiz_description',$LL),
			'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=user_tine2typo'
		);

		return $wizardItems;
	}
	function includeLocalLang()	{
//		include(t3lib_extMgm::extPath('user_tine2typo').'locallang_.php');
		include(t3lib_extMgm::extPath('user_tine2typo').'locallang_db.xml');
		return $LOCAL_LANG;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_tine2typo/pi1/class.user_tine2typo_pi1_wizicon.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_tine2typo/pi1/class.user_tine2typo_pi1_wizicon.php']);
}

?>