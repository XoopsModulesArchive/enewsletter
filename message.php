<?php
require('header.php');
$dirname = $xoopsModule->dirname();

if (isset($_POST['action']))
	$action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
	$action = $_GET['action'];
else
	$action = '';

$mess_id = isset($_POST['mess_id']) ? intval($_POST['mess_id']) : intval($HTTP_GET_VARS['mess_id']);

if ($action == 'view_mess') {
	$sql    = "SELECT * FROM " . $xoopsDB->prefix("enewsletter_messages") . " WHERE mess_id=$mess_id";
	$arr    = $xoopsDB->fetchArray($xoopsDB->query($sql));

	$xoopsOption['template_main'] = 'enewsletter_message.html';
	include(XOOPS_ROOT_PATH.'/header.php');	// Include the page header

	$xoopsTpl->assign('lang_date', _EN_TIME);
	$xoopsTpl->assign('lang_from', _EN_FROM);
	$xoopsTpl->assign('lang_subject', _EN_SUBJECT);
	$xoopsTpl->assign('lang_message', _EN_MESSAGE_BODY);

	$xoopsTpl->assign('en_title', sprintf(_EN_VIEW_MESS, $arr['mess_id']));
	$xoopsTpl->assign('en_messagetime', formatTimestamp($arr['time_sent'], "D d-M-Y"));
	$xoopsTpl->assign('en_messagefrom', $arr['mess_from']);
	$xoopsTpl->assign('en_subject', $arr['subject']);
	$xoopsTpl->assign('en_message', $arr['message']);

	// Include the page footer
	include(XOOPS_ROOT_PATH.'/footer.php');
} else {
	redirect_header('archives.php',1,"");
}
?>