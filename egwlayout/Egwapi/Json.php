<?php
class Egwapi_Json
{
	function getTree() 
	{
error_log('NODE: '. $_REQUEST['node']);
switch($_REQUEST['node']) {
	case 'fellowsaddressbooks':
	case 'fellowscalendar':
	case 'fellowsinbox':
	case 'fellowstasks':
		$nodes = array();
		$nodes[] = array('text'=>'Lars Kneschke', id=>'personal_lk', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeFellow');
		$nodes[] = array('text'=>'Thomas Wadewitz', id=>'personal_tw', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeFellow');
		$nodes[] = array('text'=>'Christof Mueller', id=>'personal_cm', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeFellow');
		
		break;

	case 'teamaddressbooks':
	case 'teamcalendar':
	case 'teamfolder':
	case 'teamtasks':
		$nodes = array();
		$nodes[] = array('text'=>'Sales Team', id=>'team_sales', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeTeam');
		$nodes[] = array('text'=>'Support Team', id=>'team_support', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeTeam');
		$nodes[] = array('text'=>'OfficeSpot Team', id=>'team_officespot', leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file', contextMenuClass=>'ctxMenuTreeTeam');
		
		break;

}
echo json_encode($nodes);
	}
}
?>