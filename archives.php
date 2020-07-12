<?php
include('header.php');
include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
$dirname = $xoopsModule->dirname();

if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
    $action = $_GET['action'];
else
    $action = '';

$search_type = isset($_POST['search_type']) ? $_POST['search_type'] : '';
$body_text = isset($_POST['body_text']) ? $_POST['body_text'] : '';
$from_text = isset($_POST['from_text']) ? $_POST['from_text'] : '';
$subject_text = isset($_POST['subject_text']) ? $_POST['subject_text'] : '';
$date_month = isset($_POST['date_month']) ? $_POST['date_month'] : '';
$date_day = isset($_POST['date_day']) ? $_POST['date_day'] : '';
$date_year = isset($_POST['date_year']) ? $_POST['date_year'] : '';
$excluded = isset($_POST['excluded']) ? $_POST['excluded'] : '';

$start = isset($_GET['start']) ? intval($_GET['start']) : 0;

$sql = "SELECT * FROM " . $xoopsDB->prefix("enewsletter_messages") . "";
$actiontitle = '';

switch ($action)
{
    case 'search':
        switch ($search_type)
        {
            case _EN_SEARCH_FROM:
                $actiontitle = _EN_ARCHIVE_TITLE . _EN_RESULTS_FROM;
                if ($excluded)
                    $sql .= " WHERE mess_from NOT LIKE '%$from_text%'";
                else
                    $sql .= " WHERE mess_from LIKE '%$from_text%'";
                break;
            case _EN_SEARCH_SUBJECT:
                $actiontitle = _EN_ARCHIVE_TITLE . _EN_RESULTS_SUBJECT;
                if ($excluded)
                    $sql .= " WHERE subject NOT LIKE '%$subject_text%'";
                else
                    $sql .= " WHERE subject LIKE '%$subject_text%'";
                break;
            case _EN_SEARCH_BODY:
                $actiontitle = _EN_ARCHIVE_TITLE . _EN_RESULTS_BODY;
                if ($excluded)
                    $sql .= " WHERE message NOT LIKE '%$body_text%'";
                else
                    $sql .= " WHERE message LIKE '%$body_text%'";
                break;
            case _EN_SEARCH_DATE:
                $actiontitle = _EN_ARCHIVE_TITLE . _EN_RESULTS_DATE;
                $next_day = $date_day + 1;
                if ($excluded)
                    $sql .= " WHERE time_sent IS BETWEEN '$date_year-$date_month-$date_day' AND '$date_year-$date_month-$next_day'";
                else
                    $sql .= " WHERE time_sent IS NOT BETWEEN '$date_year-$date_month-$date_day' AND '$date_year-$date_month-$next_day'";
                break;
        }
        break;

    case 'sort_ascend':
        $actiontitle = _EN_ARCHIVE_TITLE . _EN_SORT_ASC;
        $sql .= " ORDER BY mess_id";
        break;
    case 'sort_descend':
        $actiontitle = _EN_ARCHIVE_TITLE . _EN_SORT_DESC;
        $sql .= " ORDER BY mess_id DESC";
        break;
    case 'sort_subject_des':
        $actiontitle = _EN_ARCHIVE_TITLE . _EN_SUB_SORT_DES;
        $sql .= " ORDER BY subject DESC";
        break;
    case 'sort_subject_asc':
        $actiontitle = _EN_ARCHIVE_TITLE . _EN_SUB_SORT_ASC;
        $sql .= " ORDER BY subject";
        break;
    default:
        $actiontitle = _EN_ARCHIVE_TITLE;
        $sql .= " ORDER BY mess_id DESC";
		break;
}
include(XOOPS_ROOT_PATH . '/header.php'); // Include the page header

global $myts, $xoopsUser;

$myts = &MyTextSanitizer::getInstance();

//$sql .= " ORDERY BY " . $orderby . "";
$result = $xoopsDB->query($sql, 10, $start);
$totalcols = $xoopsDB->getRowsNum($result);

//Get total messages
$sql2 = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . "" ;
$list = $xoopsDB->getRowsNum($xoopsDB->query($sql2));

$records = array();
// Add each record to $records[]
$a = 0;
while ($myarray = $xoopsDB->fetchArray($result))
{

	$records['mess_id'] = $myts->stripSlashesGPC($myarray['mess_id']);
    $records['mess_from'] = xoops_getLinkedUnameFromId($myarray['user_id']);
    $user = new XoopsUser($myarray['user_id']);
	$records['user_email'] = $user->email();
    $records['user_email'] = checkEmail($records['user_email'], TRUE);
    $records['time_sent'] = formatTimestamp($myarray['time_sent'], "D d-M-Y");
    $records['subject'] = $myarray['subject'];
	$a++;
    $xoopsTpl->append('en_messages', $records);
}
// Pagenav
$pagenavs['total'] = $totalcols;
$pagenav = new XoopsPageNav($pagenavs['total'], 10, $start, 'start', '');
$pagenavs['navbar'] = $pagenav->renderNav();
$xoopsTpl->assign('en_pagenav', $pagenavs);

$mess_end = $start + $a;
$mess_start = ($mess_end > 0) ? $start + 1 : 0;
$message_total = $list;

$years = array();
for ($i = date('Y') - 2; $i < date('Y') + 3; $i++)
{
    $years[] = $i;
}

$xoopsOption['template_main'] = 'enewsletter_archive.html';

$xoopsTpl->assign('lang_sort_by', "Sort By: ");
$xoopsTpl->assign('lang_msgid', "Message ID");
if ($message_total > 0)
{
 $xoopsTpl->assign('lang_search_archives', _EN_SEARCH_ARCHIVES);   
}

$xoopsTpl->assign('lang_asc_sort', _EN_ASC_SORT);
$xoopsTpl->assign('lang_des_sort', _EN_DES_SORT);
$xoopsTpl->assign('lang_asc_subject_sort', _EN_ASC_SUBJECT_SORT);
$xoopsTpl->assign('lang_des_subject_sort', _EN_DES_SUBJECT_SORT);
$xoopsTpl->assign('lang_msg', "Msg#");
$xoopsTpl->assign('lang_time', _EN_DATE);
$xoopsTpl->assign('lang_from', _EN_FROM);
$xoopsTpl->assign('lang_subject', _EN_SUBJECT);
$xoopsTpl->assign('lang_view', _EN_VIEW);
$xoopsTpl->assign('lang_mess_from_to', "Now showing $mess_start - $mess_end of $message_total");
$xoopsTpl->assign('lang_search_option', _EN_SEARCH_OPTION);

$xoopsTpl->assign('lang_search_from_field', _EN_SEARCH_FROM_FIELD);
$xoopsTpl->assign('lang_except1', _EN_EXCEPT1);
$xoopsTpl->assign('lang_search_body', _EN_SEARCH_BODY);
$xoopsTpl->assign('lang_escept2', _EN_EXCEPT1);
$xoopsTpl->assign('lang_search_subject', _EN_SEARCH_SUBJECT);

$xoopsTpl->assign('lang_except3', _EN_EXCEPT3);
$xoopsTpl->assign('lang_search_date', _EN_SEARCH_DATE);
$xoopsTpl->assign('lang_search_on_date', _EN_SEARCH_DATE_ON);
$xoopsTpl->assign('lang_search_note', _EN_SEARCH_NOTE);

$xoopsTpl->assign('en_showsearch', $action == 'search');
$xoopsTpl->assign('en_action', $actiontitle);
$xoopsTpl->assign('en_title', _EN_ARCHIVETITLE);
$xoopsTpl->assign('en_menu', _EN_MENU);
$xoopsTpl->assign('en_archive_url', XOOPS_URL . '/modules/' . $dirname . '/archives.php');
$xoopsTpl->assign('en_message_url', XOOPS_URL . '/modules/' . $dirname . '/message.php');
$xoopsTpl->assign('en_from_text', $from_text);
$xoopsTpl->assign('en_exclude_from', ($excluded && $from_text)?'checked="checked"':'');
$xoopsTpl->assign('en_body_text', $body_text);
$xoopsTpl->assign('en_exclude_body', ($body_text && $excluded)?'checked="checked"':'');
$xoopsTpl->assign('en_subject_text', $subject_text);
$xoopsTpl->assign('en_exclude_subject', ($excluded && $subject_text)?'checked="checked"':'');
$xoopsTpl->assign('en_month_ids', array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12));
$xoopsTpl->assign('en_month_names', array(_EN_MONTH_NONE, _EN_MONTH_JAN, _EN_MONTH_FEB, _EN_MONTH_MAR, _EN_MONTH_APR, _EN_MONTH_MAY, _EN_MONTH_JUN, _EN_MONTH_JUL, _EN_MONTH_AUG, _EN_MONTH_SEP, _EN_MONTH_OCT, _EN_MONTH_NOV, _EN_MONTH_DEC));
$xoopsTpl->assign('en_selected_month', intval($date_month));
$xoopsTpl->assign('en_day_values', array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31));
$xoopsTpl->assign('en_selected_day', intval($date_day));
$xoopsTpl->assign('en_year_list', $years);
$xoopsTpl->assign('en_selected_year', $date_year);
$xoopsTpl->assign('en_search_subject', _EN_SEARCH_SUBJECT);
$xoopsTpl->assign('en_search_body', _EN_SEARCH_BODY);
$xoopsTpl->assign('en_search_date', _EN_SEARCH_DATE);
$xoopsTpl->assign('en_search_from', _EN_SEARCH_FROM);
// Include the page footer
include(XOOPS_ROOT_PATH . '/footer.php');

?>
