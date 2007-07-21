<?
$dir = $_REQUEST['lib'] == 'yui' ? '../../../' : '../../';
$node = $_REQUEST['node'];
if(strpos($node, '..') !== false){
	die('Nice try buddy.');
}
$nodes = array();
#$d = dir($dir.$node);
#while($f = $d->read()){
#    if($f == '.' || $f == '..' || substr($f, 0, 1) == '.')continue;
#    $lastmod = date('M j, Y, g:i a',filemtime($dir.$node.'/'.$f));
#    if(is_dir($dir.$node.'/'.$f)){
#    	$qtip = 'Type: Folder<br />Last Modified: '.$lastmod;
#    	$nodes[] = array('text'=>$f, id=>$node.'/'.$f/*, qtip=>$qtip*/, cls=>'folder');
#    }else{
#    	$size = formatBytes(filesize($dir.$node.'/'.$f), 2);
#    	$qtip = 'Type: JavaScript File<br />Last Modified: '.$lastmod.'<br />Size: '.$size;
#    	$nodes[] = array('text'=>$f, id=>$node.'/'.$f, leaf=>true/*, qtip=>$qtip, qtipTitle=>$f */, cls=>'file');
#    }
#}
#$d->close();
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
?>