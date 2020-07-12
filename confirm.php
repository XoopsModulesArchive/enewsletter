<?php
require('header.php');
$xoopsOption['template_main'] = 'enewsletter_notice.html';
include(XOOPS_ROOT_PATH . '/header.php'); // Include the page header

global $xoopsModuleConfig;

$xoopsTpl->assign('lang_status', _EN_STATUS);

$conf_id = $_GET['id'];
$query = "select * from " . $xoopsDB->prefix('enewsletter_members') . " where user_conf='$conf_id'";
$result = $xoopsDB->query($query);
$arr = $xoopsDB->fetchArray($result);

if (!is_array($arr)) {
     $xoopsTpl->assign('en_message', "There was an error dealing with your Subscription, please contact the webmaster to have this corrected.");
}

if ($arr['confirmed'] == '1')
{
    $xoopsTpl->assign('en_message', $arr['user_nick'].", "._EN_PREVCONFIRM);
}
else
{
    $confirmed = 1;
	$query = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET confirmed = '$confirmed'";
    if ($xoopsModuleConfig['autoapprove'])
    {
        $query .= ", activated = '$confirmed' ";
    }
    $query .= " WHERE user_id=".$arr['user_id']."";
    $result = $xoopsDB->queryF($query);
    $error = "There was an error updating our database.  Please contact the webmaster and report this error:<br /><br />" . $query;
    if (!$result)
    {
    	trigger_error($error, E_USER_ERROR);
    } 
	$xoopsTpl->assign('en_message', $arr['user_nick'].", ". _EN_CONFIRMSUCCESS . _EN_CONFIRMATION_NUMBER . $conf_id);
} 
// Include the page footer
include(XOOPS_ROOT_PATH . '/footer.php');

?>

