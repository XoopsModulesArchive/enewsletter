<?php
include_once('admin_header.php');

if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
    $action = $_GET['action'];
else
    $action = '';

$module_handler = &xoops_gethandler('module');
$module = &$module_handler->getByDirname('spaw');

$start = isset($HTTP_GET_VARS['start']) ? intval($HTTP_GET_VARS['start']) : 0;

if (is_object($module) && $module->getVar('isactive'))
{
    include_once XOOPS_ROOT_PATH . "/modules/spaw/spaw_control.class.php";
} 

switch ($action)
{
    case 'delete_mess':
        deleteMessage();
        break;

    case 'send_message':
        sendMessage();
        break;

    case 'optimize':
        optimizeTable();
        break;

    case 'view_archives':
        showArchive($start);
        break;

    case 'view_mess':
        showMessage($_GET['mess_id']);
        break;

    case 'send':
        messageForm($_GET['nletter_id']);
        break;
    case 'setup':
        nletterSetup($_GET['nletter_id']);
        break;
    case 'save_newsletter':
        save_newsletter();
        break;
    case 'newsletters':
        newsletters();
        break;
    case 'delete_newsletter':
        deleteNewsletter();
        break;               
    case 'subscribers_newsletters':
        subscribersByNewsletterForm();
        break;  
    case 'unsubscribeFromNewsletter':
        deleteNewslettersBySubscriberIdAndNletterID($_GET['subscriber_id'], $_GET['nletter_id']);
        redirect_header("index.php?action=subscribers_newsletters&nletter_id=".$_GET['nletter_id'], 1, _MD_MSGDELETED);
        break;                 
               
    case 'default':
    default:
        xoops_cp_header();
        evadminmenu(_ADM_EVENNEWS_ADMINMENU);
        xoops_cp_footer();
        break;
} 
// Ajout Hervé
function deleteMessage()
{
    global $xoopsDB, $HTTP_GET_VARS, $HTTP_POST_VARS;

    $mess_id = isset($HTTP_POST_VARS['mess_id']) ? intval($HTTP_POST_VARS['mess_id']) : intval($HTTP_GET_VARS['mess_id']);
    $ok = isset($HTTP_POST_VARS['ok']) ? intval($HTTP_POST_VARS['ok']) : 0;
    if ($ok == 1)
    {
        $sql = sprintf("DELETE FROM %s WHERE mess_id = %d", $xoopsDB->prefix("enewsletter_messages"), $mess_id);
        $result = $xoopsDB->query($sql);
        $error = "Error while deleting Email Message Data: <br /><br />" . $sql;
        if (!$result)
        {
            trigger_error($error, E_USER_ERROR);
        } 
        redirect_header("index.php?action=view_archives", 1, _MD_MSGDELETED);
        exit();
    } 
    else
    {
        xoops_cp_header();
        echo "<h4>" . _ADM_CONFDELETE . "</h4>";
        xoops_confirm(array('action' => 'delete_mess', 'mess_id' => $mess_id, 'ok' => 1), 'index.php', _MD_WARNING);
        xoops_cp_footer();
    } 
} 


function deleteNewsletter()
{
    global $xoopsDB, $HTTP_GET_VARS, $HTTP_POST_VARS;

   // $nletter_id = isset($_POST['nletter_id']) ? intval($_POST['nletter_id']) : intval($_POST['nletter_id']);
   // echo "N1:".$nletter_id."<P>";
 //  $nletter_id = isset($HTTP_POST_VARS['nletter_id']) ? intval($HTTP_POST_VARS['nletter_id']) : 0;
  // echo "N2:".$nletter_id."<P>";
    $nletter_id = isset($_GET['nletter_id']) ? intval($_GET['nletter_id']) : intval($_GET['nletter_id']);
    if ($nletter_id == 0){$nletter_id = isset($_POST['nletter_id']) ? intval($_POST['nletter_id']) : intval($_POST['nletter_id']);}
    //echo "N3:".$nletter_id."<P>";   
    $ok = isset($_POST['ok']) ? intval($_POST['ok']) : 0;
    //echo "O2:".$ok."<P>";
    if ($ok == 1)
    {
        $sql = sprintf("DELETE FROM %s WHERE nletter_id = %d", $xoopsDB->prefix("enewsletter_nletters"), $nletter_id);
        $result = $xoopsDB->query($sql);
        $error = "Error while deleting Email Message Data: <br /><br />" . $sql;
        if (!$result)
        {
            trigger_error($error, E_USER_ERROR);
        } 
        deleteNewslettersByNletter_Id($nletter_id);
        redirect_header("index.php?action=newsletters", 1, _MD_MSGDELETED);
        exit();
    } 
    else
    {
        xoops_cp_header();
        echo "<h4>" . _ADM_CONFDELETE . "</h4>";
        xoops_confirm(array('action' => 'delete_newsletter', 'nletter_id' => $nletter_id, 'ok' => 1), 'index.php', _MD_WARNING);
        xoops_cp_footer();
    } 
} 



function optimizeTable()
{
    global $xoopsDB;

    xoops_cp_header();
    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    $sql = "OPTIMIZE TABLE ".$xoopsDB->prefix('enewsletter_members')."";
    $result = $xoopsDB->queryF($sql);
    $error = "" . _ADM_EVENNEWS_DBERROR . ": <br /><br />" . $sql;
    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    } else {
    	echo "<div>";
    	printf(_ADM_EVENNEWS_TABLEOPT, "<b>" . $xoopsDB->prefix('enewsletter_members') . "</b>");
		echo "</div>";	
	}

    $sql = "OPTIMIZE TABLE ".$xoopsDB->prefix('enewsletter_messages')."";
    $result = $xoopsDB->queryF($sql);
    $error = "" . _ADM_EVENNEWS_DBERROR . ": <br /><br />" . $sql;
    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    } else {
    	echo "<div>";
    	printf(_ADM_EVENNEWS_TABLEOPT, "<b>" . $xoopsDB->prefix('enewsletter_messages') . "</b>");
		echo "</div>";		
	}
    xoops_cp_footer();
} 

function viewMessage($messID)
{
    global $xoopsDB;
    $messID = intval($messID); //Make sure supplied messID is a number
    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE mess_id=$messID";
    $arr = $xoopsDB->fetchArray($xoopsDB->query($sql));
    $amount = $xoopsDB->getRowsNum($sql);
	$error = "Could not retrive message data: <br /><br />" . $sql;
    if (!$arr)
    {
        trigger_error($error, E_USER_ERROR);
    } 

    xoops_cp_header();
    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_VIEWMSG . " " . $messID);
    echo "<table width='100%' cellpadding='2' cellspacing='0' class = \"outer\">\n";
    echo "<th>" . _ADM_EVENNEWS_FIELD . "</th><th>" . _ADM_EVENNEWS_VALUE . "</th>\n";
    if ($amount)
    {
        echo "<tr>\n";
        echo "<td>" . _ADM_EVENNEWS_USERID . ":</td><td>$arr[user_id]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_SENTTO . " :</td><td>$arr[sent_to]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_FAILED . " :</td><td>$arr[fail_to]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_MSGID . " :</td><td>$arr[mess_id]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_SENTTO . " :</td><td>$arr[time_sent]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_FROM . " :</td><td>$arr[mess_from]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_SUBJECT . " :</td><td>$arr[subject]</td>\n";
        echo "</tr><tr>\n";
        echo "<td>" . _ADM_EVENNEWS_MESSAGE . " :</td><td>$arr[message]</td>\n";
        echo "</tr>\n";
    } 
    else
    {
        echo "<tr>\n";
        echo "<td colspan =\"7\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
        echo "</tr>\n";
    } 
    echo "</table>";
    xoops_cp_footer();
} 

function showArchive($start)
{
    global $xoopsDB, $adminURL, $editimg, $deleteimg, $viewimg;
	
	include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
	
    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " ORDER BY time_sent" ;
    $result = $xoopsDB->query($sql, 5, $start);
	$result2 = $xoopsDB->query($sql);
    $list = $xoopsDB->getRowsNum($result2);

    $error = "<a href='javascript:history.go(-1)'>Return to where you last where</a><br /><br />";
    $error .= "Could not retrive message Archive data: <br /><br />";
    $error .= $sql;

    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    } 

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_MSGARCHIVE);
    echo "<table width=\"100%\" cellpadding=\"2\" cellspacing=\"1\" class = \"outer\">\n";
    echo "<th align = \"center\" width=\"5%\">" . _ADM_EVENNEWS_ID . ".</th>
		<th align = \"center\" width=\"15%\">" . _ADM_EVENNEWS_SENTTO . "</th>
		<th align = \"center\" width=\"15%\">" . _ADM_EVENNEWS_FAILED . "</th>
		<th align = \"center\" width=\"15%\">" . _ADM_EVENNEWS_DATESENT . "</th>
		<th width=\"35%\">" . _ADM_EVENNEWS_SUBJECT . "</th>
		<th align = \"center\" width=\"5%\">" . _ADM_EVENNEWS_ACTION . "</th>
	";
    if ($result)
    {
        while ($arr = $xoopsDB->fetchArray($result))
        {
            echo "<tr>";
            echo "<td class = \"head\" align = \"center\">" . $arr['mess_id'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['sent_to'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['fail_to'] . "</td>";
            echo "<td  class = \"even\" align = \"center\" nowrap>" . formatTimestamp($arr['time_sent'], "d-M-Y") . "</td>\n";
            echo "<td class = \"even\">" . $arr['subject'] . "</td>";
            echo "<td nowrap class = \"even\" align =\"center\">
				<a href='index.php?action=send&amp;mess_id=" . $arr['mess_id'] . "'>$editimg</a> 
				<a href='index.php?action=view_mess&amp;mess_id=" . $arr['mess_id'] . "'>$viewimg</a>
				<a href='index.php?action=delete_mess&amp;mess_id=" . $arr['mess_id'] . "'>$deleteimg</a>
			</td>";
            echo "</tr>";
        } 
    } 
    else
    {
        echo "<tr>\n";
        echo "<td colspan =\"7\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
        echo "</tr>\n";
    } 
    echo "</table>";
    $pagenav = new XoopsPageNav($list, 5, $start, 'start', 'action=view_archives', 1);
    echo '<div text-align="right">' . $pagenav->renderNav() . '</div>';
    xoops_cp_footer();
} 









function newsletters($start)
{
    global $xoopsDB, $adminURL, $editimg, $deleteimg, $viewimg;
	
	include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
	
    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_nletters') . " ORDER BY nletter_date" ;
    $result = $xoopsDB->query($sql, 5, $start);
	$result2 = $xoopsDB->query($sql);
    $list = $xoopsDB->getRowsNum($result2);

    $error = "<a href='javascript:history.go(-1)'>Return to where you last where</a><br /><br />";
    $error .= "Could not retrive message Archive data: <br /><br />";
    $error .= $sql;

    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    } 

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_AM_NEWSLETTERS);
    echo "<table width=\"100%\" cellpadding=\"2\" cellspacing=\"1\" class = \"outer\">\n";
    echo "<th align = \"center\" width=\"5%\">" . _ADM_EVENNEWS_AM_NEWSLETTERS_ID . ".</th>
		<th align = \"center\" width=\"15%\">" . _ADM_EVENNEWS_AM_NEWSLETTERS_NAME . "</th>
		<th align = \"center\" width=\"15%\">" . _ADM_EVENNEWS_AM_NEWSLETTERS_DESCRIPTION . "</th>
		<th align = \"center\" width=\"5%\">" . _ADM_EVENNEWS_ACTION . "</th>
	";
    if ($result)
    {
        while ($arr = $xoopsDB->fetchArray($result))
        {
            echo "<tr>";
            echo "<td class = \"head\" align = \"center\">" . $arr['nletter_id'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['nletter_name'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['nletter_description'] . "</td>";
            echo "<td nowrap class = \"even\" align =\"center\">
				<a href='index.php?action=setup&amp;nletter_id=" . $arr['nletter_id'] . "'>Edit</a> 
				<a href='index.php?action=subscribers_newsletters&amp;nletter_id=" . $arr['nletter_id'] . "'>Subscribers</a>
				<a href='index.php?action=delete_newsletter&amp;nletter_id=" . $arr['nletter_id'] . "'>Delete</a> 
				<a href='index.php?action=send&amp;nletter_id=" . $arr['nletter_id'] . "'>Send</a>
			</td>";
            echo "</tr>";
            //				<a href='index.php?action=view_newsletter&amp;nletter_id=" . $arr['nletter_id'] . "'>View</a>
        } 
    } 
    else
    {
        echo "<tr>\n";
        echo "<td colspan =\"7\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
        echo "</tr>\n";
    } 
    echo "</table>";
    $pagenav = new XoopsPageNav($list, 5, $start, 'start', 'action=view_archives', 1);
    echo '<div text-align="right">' . $pagenav->renderNav() . '</div>';
    xoops_cp_footer();
} 



function sendMessage()
{
//add $xoopsModuleConfig global 
    global $xoopsDB, $xoopsUser, $xoopsMailer, $xoopsConfig, $myts, $xoopsConfigUser, $xoopsModuleConfig;

    $myts = &MyTextSanitizer::getInstance();

    $list_members = ($_POST['list_members']) ? 1 : 0;

    $mail_from = $myts->oopsStripSlashesGPC($_POST['mail_from']);
    $mail_subj = $myts->oopsStripSlashesGPC($_POST['mail_subj']);
    $email_type = ($_POST['mail_format'] == 0) ? 1 : 0;
    $break_type = ($_POST['mail_format'] == 0) ? 0 : 1;

    if ($email_type == 1)
    {
        $mail_mess = $myts->displayTarea(trim($_POST['mail_mess']), 1, 1, 1, 1, 0);
    } 
    else
    {
        include ("../class/html2text.inc");
// commented out Catzwolf's change and change back to Harvet version --tl
     //   $htmlToText = new Html2Text ($mail_mess, 150);
     //   $mail_mess = $htmlToText->convert();
     //   $page = $myts->displayTarea(trim($mail_mess), 1, 1, 1, 1, 1);
     //   $mail_mess = strip_tags($mail_mess); 
      $mail_mess = $myts->displayTarea(trim(trim($_POST['mail_mess'])), 1, 1, 1, 1, 1);
      $htmlToText = new Html2Text ($mail_mess, 150);
      $mail_mess = $htmlToText->convert();
    } 

    $mail_mess = $myts->stripSlashesGPC($mail_mess);

//allow saving contents with apostrophes into mysql -- tl 
    $mail_mess1 =  addslashes($mail_mess);
//end of hack

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_SENDING);
    $confirmedtype = (isset($_POST['mail_message_emailuncomfirm'])) ? 0 : 1;
    $query = $xoopsDB->query("select * from " . $xoopsDB->prefix("enewsletter_members") . " where confirmed='$confirmedtype'");
    $list = $xoopsDB->getRowsNum($query);

    if (!isset($_POST['test_email'])) {
		if ($list == 0 && $list_members)
    	{
        	echo "<b>Notice: No subscribed users to email.</b>";
        	xoops_cp_footer();
        	exit();
    	} 
    }
    $sent_good = 0;
    $sent_bad = 0;

    $xoopsMailer = &getMailer();
    $member_handler = &xoops_gethandler('member');
    $tblUsers = Array();

    $mail_type_message = (isset($_POST['test_email'])) ? 1 : $_POST['mail_message_type'];

    if ($_POST['list_members'] == 1 && $mail_type_message == 0)
    {
        echo "<h5>Cannot send a PM to Mailing list members, this option is not available to Mailing list Members.</h5>";
    } 
    else
    {
        if (!isset($_POST['test_email']))
        {
            if ($list_members == 0)
            {
                /**
                 * if $_POST['list_members'] == 0;
                 * Uses Xoops Groups to determine who recieves email.
                 */
                $message_type = ($_POST['mail_message_type']) ? "email" : "uid";
                $message_strict = (isset($_POST['mail_message_ignorestrict'])) ? 0 : 1;
                $mail_group = (isset($_POST['test_email'])) ? 1 : $_POST['mail_group'];
                $member_handler = &xoops_gethandler('member');
                $user_list = array();
                foreach ($mail_group as $groupid)
                {
                    $members = &$member_handler->getUsersByGroup($groupid, true);
                    foreach ($members as $member)
                    {
                        if (!in_array($member->getVar($message_type), $tblUsers))
                        {
                            if ($member->getVar($message_type))
                            {
                                if (isset($_POST['mail_message_ignorestrict']))
                                {
                                    $tblUsers[] = $member->getVar($message_type);
                                } 
                                else
                                {
                                    if ($member->getVar("user_mailok") == 1)
                                    {
                                        $tblUsers[] = $member->getVar($message_type);
                                    } 
                                } 
                            } 
                        } 
                    } 
                } 
            } 
            else
            {
                /**
                 * if $_POST['list_members'] == 1;
                 * Selects current subscribed user of mailing list.
                 */
                $query = "select * from " . $xoopsDB->prefix('enewsletter_members') . " where confirmed='1'";
                
			$query="SELECT ".$xoopsDB->prefix('enewsletter_members').".* FROM ".$xoopsDB->prefix('enewsletter_members')." INNER JOIN ";
			$query.=$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_members').".subscriber_id = ";
			$query.= $xoopsDB->prefix('enewsletter_nletter_user_link').".subscriber_id  WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
			$query.=".nletter_id=".$_POST['nletter_id']." AND confirmed='1'";   
			
			//echo "QUERY:".$query."<P>";  break;           
                $result = $xoopsDB->query($query);
                while ($arr = $xoopsDB->fetchArray($result))
                {
                    if ($arr['user_email'] != '')
                    {
                        $tblUsers[] = $arr['user_email'];
                    } 
                } 
            } 
        } 
        /**
         * Determines which type of message a user will recieve
         */
        if ($_POST['mail_message_type'])
        { 
            // Sends mail to mailing list users and user choosen by group
            $xoopsMailer->useMail();
        } 
        else
        {
            /**
             * Only send PM to users selected by groups, will have to add users xoops ID to the database to use PM
             */
            $xoopsMailer->usePM();
        } 

        /**
         * loops through user id's and adds them to array for xoopsMailer to use
         * for only PM and users choosen by group
         */
        if (isset($_POST['test_email']))
        {
            $xoopsMailer->setToGroups($member_handler->getGroup(1));
        } 
        else
        {
            for ($i = 0; $i < count($tblUsers);$i++)
            {
                if (!$_POST['list_members'] && !$_POST['mail_message_type'])
                {
                    $xoopsMailer->setToUsers(new XoopsUser($tblUsers[$i]));
                } 
                else
                {
                    $xoopsMailer->setToEmails($tblUsers[$i]);
                } 
            } 
        } 
        if (!empty($_POST['list_template']) && $_POST['list_template'] != 'blank.tpl')
        {
            global $xoopsConfig;
            $template_dir = XOOPS_ROOT_PATH . "/modules/enewsletter/language/" . $xoopsConfig['language'] . "/mail_template";
            $xoopsMailer->setTemplateDir($template_dir);
            $xoopsMailer->setTemplate($_POST['list_template']);
        } 
// Add specified systmemail, instead of adminmail -- tl
      if ( trim($xoopsModuleConfig['systememail']) != "") {
        $xoopsMailer->setFromEmail($xoopsModuleConfig['systememail']);
       } else {
       $xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
    } 
// end of email hack

        $xoopsMailer->setFromName($xoopsConfig['sitename']);
        $xoopsMailer->setBody($mail_mess);
        $xoopsMailer->setSubject($mail_subj);

        if ($_POST['mail_format'] == 0)
        {
            $xoopsMailer->multimailer->IsHTML(true);
        } 
        else
        {
            $xoopsMailer->multimailer->IsHTML(false);
        } 

        $xoopsMailer->send(true);
        if ($xoopsMailer->getSuccess())
        {
            $sent_good = $xoopsMailer->getSuccess();
            $sent_good_text = str_replace("Could not instantiate mail()", '', $sent_good);
            $sent_good_text = str_replace("<br /><br />", '<br />', $sent_good_text);
            $sent_good = explode ('mail()', $sent_good);
            $sent_good = count($sent_good)-1;
            echo $sent_good_text;
        } 
        if ($xoopsMailer->getErrors())
        {
            $sent_bad = $xoopsMailer->getErrors();
            $sent_bad_text = str_replace("Could not instantiate mail()", '', $sent_bad);
            $sent_bad_text = str_replace("<br /><br />", '<br />', $sent_bad_text);
            $sent_bad = explode ('mail()', $sent_bad);
            $sent_bad = count($sent_bad)-1;
            echo $sent_bad_text;
        } 
        $time = time();

        $user_id = $xoopsUser->getVar('uid');
        
        if (!isset($_POST['test_email']))
        { 
       	$query = "INSERT INTO " . $xoopsDB->prefix('enewsletter_messages') . " (user_id, sent_to, fail_to, time_sent, message, subject, mess_from) ";
        	$query .= "VALUES ($user_id, $sent_good, $sent_bad, '$time', '$mail_mess1', '$mail_subj', '$mail_from')";
		}
        $result = $xoopsDB->queryF($query);
        echo "<br/><b>" . _ADM_EVENNEWS_MSGARCHIVED . ". ($result)</b><br /><br />";
        echo "<b>" . _ADM_EVENNEWS_FROM . "</b> : '$mail_from'.<br />";
        echo "<b>" . _ADM_EVENNEWS_SUBJECT . "</b> : '$mail_subj'.<br />";
        echo "<b>" . _ADM_EVENNEWS_MESSAGE . "</b> :<br /><div style=\"padding: 8px;\">$mail_mess</div>";
    } 

    xoops_cp_footer();
} 









function messageForm($nletter_id)
{
    global $xoopsDB, $xoopsUser, $xoopsConfig, $adminURL, $xoopsModuleConfig;

    $arr = array();

    $nletter_id = intval($nletter_id);

    //$sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE mess_id=$nletter_id";
    //$arr = $xoopsDB->fetchArray($xoopsDB->query($sql));

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

	$arruser=getSingleNewslettersByNletterId($nletter_id);
    $nletter_name = $arruser['nletter_name'];
    $nletter_template = $arruser['nletter_template'];
    $addUserHeader = (!isset($nletter_id)) ? _ADM_EVENNEWS_CREATE : _ADM_EVENNEWS_SENDNEWSLETTER." : ".$nletter_name;


            
    $sform = new XoopsThemeForm($addUserHeader, "op", xoops_getenv('PHP_SELF'));
    $listmembers_select = new XoopsFormRadioYN(_ADM_EVENNEWS_LISTMEMBERS, 'list_members', '1', ' ' . _YES . '', ' ' . _NO . ' (Selecting \'Yes\' will override sending mail to groups members.)');
    $sform->addElement($listmembers_select);
    $sform->addElement(new XoopsFormSelectGroup(_ADM_EVENNEWS_GROUPS . "<br />", "mail_group", false, false, 5, true));

    $sform->insertBreak("", "even");

    $mailtemplate = (isset($arr['mess_from'])) ? $arr['mess_from']: $xoopsUser->getVar('email');

//rework of Catzwolf's codes, $xoosModuleConfig should be used -- tl
     if ( trim($xoopsModuleConfig['systememail']) == "") {
        $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_FROM, 'mail_from', 50, 255, $xoopsConfig['adminmail']), true);
        } else {       
    $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_FROM, 'mail_from', 50, 255, $xoopsModuleConfig['systememail']), true);
        } 
// end of hack

  //  $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_FROM, 'mail_from', 50, 255, $xoopsUser->getVar('email')), true);
    $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_SUBJECT, 'mail_subj', 50, 255, $arr['subject']), true);
    $sform->insertBreak("", "even"); 
    // $mailtemplate = (isset($arr['list_template'])) ? $arr['list_template']: '';
    // $sql = "SELECT subject  FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE is_template = '1' ";
    // $graph_array = $xoopsDB->fetchArray($xoopsDB->query($sql));
    // $linkpage_select = new XoopsFormSelect('', 'list_template', $mailtemplate , 1, 0, 1);
    // $linkpage_select->addOptionArray($graph_array);
    // $linkpage_tray = new XoopsFormElementTray(_ADM_EVENNEWS_TEMPLATE, '&nbsp;');
    // $linkpage_tray->addElement($linkpage_select);
    // $sform->addElement($linkpage_tray);
    // $connect_checkbox = new XoopsFormCheckBox(_ADM_TESTEMAIL, "connect_email", 0);
    // $connect_checkbox->addOption(1, " Connect this template.");
    // $sform->addElement($connect_checkbox);
    // $sform->insertBreak("", "even");
    $mainemailbodytext = ($arr['message']) ? $arr['message'] : '';

    if (!$xoopsModuleConfig['usespaw'] || !strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
    {
        $sform->addElement(new XoopsFormDhtmlTextArea(_ADM_EVENNEWS_MESSAGE, 'mail_mess', $nletter_template, 10, 60), true);
    } 
    else
    {
        ob_start();
        $sw = new SPAW_Wysiwyg('mail_mess', $mainemailbodytext, 'en', 'full', 'default', '95%', '600px');
        $sw->show();
        $sform->addElement(new XoopsFormLabel(_ADM_EVENNEWS_MESSAGE , ob_get_contents(), 1));
        ob_end_clean();
    } 

    $mailformat = ($arr['mail_format']) ? $arr['mail_format'] : 0;
    $mailformat_select = new XoopsFormRadioYN(_ADM_MAILFORMAT, 'mail_format', $mailformat, ' ' . _ADM_TEXTFORMAT . '&nbsp;&nbsp;', ' ' . _ADM_HTMLFORMAT . '');
    $sform->addElement($mailformat_select);

    $message_type = (isset($arr['mail_type'])) ? $arr['mail_type'] : 1;
    $mailsendas_select = new XoopsFormRadioYN(_ADM_SENDAS, 'mail_message_type', $message_type, ' ' . _ADM_EMAIL . '&nbsp;', ' ' . _ADM_PM . '');
    $sform->addElement($mailsendas_select);

    $sform->insertBreak("", "even");

    $strictemail_checkbox = new XoopsFormCheckBox(_ADM_IGNORESTRICT, "mail_message_ignorestrict", 0);
    $strictemail_checkbox->addOption(1, " <b>WARNING:</b> This will message all Members, even those who have asked not to be. (Applies to non Mailing list members)");
    $sform->addElement($strictemail_checkbox);

    $emailuncomfirm_checkbox = new XoopsFormCheckBox(_ADM_UNCONIFMIRMED, "mail_message_emailuncomfirm", 0);
    $emailuncomfirm_checkbox->addOption(1, " This option will email uncomfirmed mailing members. (Applies to Mailing list members only)");
    $sform->addElement($emailuncomfirm_checkbox);

    $testemail_checkbox = new XoopsFormCheckBox(_ADM_TESTEMAIL, "test_email", 0);
    $testemail_checkbox->addOption(1, " Email will be only sent to the webmasters group.");
    $sform->addElement($testemail_checkbox);

	$sform->addElement(new XoopsFormHidden('nletter_id', $nletter_id));
	        
    $button_tray = new XoopsFormElementTray('', '');
    $hidden = new XoopsFormHidden('action', 'send_message');
    $button_tray->addElement($hidden);
    $button_tray->addElement(new XoopsFormButton('', 'submit', _ADM_EVENNEWS_SENDMESSAGEBTN, 'submit'));
    $button_tray->addElement(new XoopsFormButton('', 'reset', _ADM_EVENNEWS_RESETFORMBTN, 'reset'));
    $sform->addElement($button_tray);
    $sform->display();
    unset($hidden);
    xoops_cp_footer();
} 

 


function nletterSetup($nletter_id)
{
    global $xoopsDB, $xoopsUser, $xoopsConfig, $adminURL, $xoopsModuleConfig;

    $arr = array();

    $nletter_id = intval($nletter_id);

    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_nletters') . " WHERE nletter_id=$nletter_id";
    $arr = $xoopsDB->fetchArray($xoopsDB->query($sql));

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

    $addUserHeader = (!isset($nletter_id)) ? _ADM_EVENNEWS_SETUP : _ADM_EVENNEWS_SETUP;

    $sform = new XoopsThemeForm("Save_newsletter", "op", xoops_getenv('PHP_SELF'));

//rework of Catzwolf's codes, $xoosModuleConfig should be used -- tl
     if ( trim($xoopsModuleConfig['systememail']) == "") {
        $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_NAME, 'nletter_name', 50, 255, $arr['nletter_name']), true);
        } else {       
    $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_NAME, 'nletter_name', 50, 255, $arr['nletter_name']), true);
        } 
// end of hack

  //  $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_FROM, 'mail_from', 50, 255, $xoopsUser->getVar('email')), true);
    $sform->addElement(new XoopsFormTextArea(_ADM_EVENNEWS_DESCRIPTION, 'nletter_description',$arr['nletter_description']), 5, 50, true); 
    $sform->insertBreak("", "even"); 
    // $mailtemplate = (isset($arr['list_template'])) ? $arr['list_template']: '';
    // $sql = "SELECT subject  FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE is_template = '1' ";
    // $graph_array = $xoopsDB->fetchArray($xoopsDB->query($sql));
    // $linkpage_select = new XoopsFormSelect('', 'list_template', $mailtemplate , 1, 0, 1);
    // $linkpage_select->addOptionArray($graph_array);
    // $linkpage_tray = new XoopsFormElementTray(_ADM_EVENNEWS_TEMPLATE, '&nbsp;');
    // $linkpage_tray->addElement($linkpage_select);
    // $sform->addElement($linkpage_tray);
    // $connect_checkbox = new XoopsFormCheckBox(_ADM_TESTEMAIL, "connect_email", 0);
    // $connect_checkbox->addOption(1, " Connect this template.");
    // $sform->addElement($connect_checkbox);
    // $sform->insertBreak("", "even");
    $mainemailbodytext = ($arr['message']) ? $arr['message'] : '';

    if (!$xoopsModuleConfig['usespaw'] || !strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
    {
        $sform->addElement(new XoopsFormDhtmlTextArea(_ADM_EVENNEWS_TEMPLATE, 'nletter_template', $arr['nletter_template'], 30, 60), true);
    } 
    else
    {
        ob_start();
        $sw = new SPAW_Wysiwyg('nletter_template', $mainemailbodytext, 'en', 'full', 'default', '95%', '600px');
        $sw->show();
        $sform->addElement(new XoopsFormLabel(_ADM_EVENNEWS_TEMPLATE , ob_get_contents(), 1));
        ob_end_clean();
    } 
		$sform->addElement(new XoopsFormHidden('nletter_id', $arr['nletter_id']), false);


    $button_tray = new XoopsFormElementTray('', '');
    $hidden = new XoopsFormHidden('action', 'save_newsletter');
    $button_tray->addElement($hidden);
    $button_tray->addElement(new XoopsFormButton('', 'submit', _ADM_EVENNEWS_SAVE, 'submit'));
    $sform->addElement($button_tray);
    $sform->display();
    unset($hidden);
    xoops_cp_footer();
} 



function save_newsletter()
{

    global $xoopsDB, $myts;
   
    
    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_SENDING);

		$nletter_name=$_POST['nletter_name'];
		$nletter_description=$_POST['nletter_description'];		
		$nletter_template=$_POST['nletter_template'];		
		$nletter_id=intval($_POST['nletter_id']);
		
		//include (XOOPS_ROOT_PATH."/modules/enewsletter/class/html2text.inc");
		//echo "<P>".XOOPS_ROOT_PATH."/modules/enewsletter/class/html2text.inc<P>";
		// $myts = &MyTextSanitizer::getInstance();
		//$htmlToText = new Html2Text ($nletter_template, 150);
		//$nletter_template = $htmlToText->convert();
		//$nletter_template = $myts->stripSlashesGPC($nletter_template);
		$nletter_template =  addslashes($nletter_template);
		
        if ($nletter_id<1)
        { 
       		$query = "INSERT INTO " . $xoopsDB->prefix('enewsletter_nletters') . " (nletter_id, nletter_name, nletter_description, nletter_template) ";
        	$query .= "VALUES ($nletter_id, '$nletter_name', '$nletter_description', '$nletter_template')";
		}else{
       		$query = "UPDATE " . $xoopsDB->prefix('enewsletter_nletters') . " SET nletter_name='".$nletter_name."', nletter_description='".$nletter_description."', nletter_template='".$nletter_template."' ";
        	$query .= "WHERE (nletter_id=".$nletter_id.")";			
		}
		//echo "SQL:".$query."<P>";
        $result = $xoopsDB->queryF($query);
        echo "<br/><b>" . _ADM_EVENNEWS_NEWSLETTERSAVED . ". ($result)</b><br /><br />";

    xoops_cp_footer();
} 



function showMessage($messID)
{
    global $xoopsDB, $xoopsUser, $myts;
    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE mess_id=" . intval($messID);
    $result = $xoopsDB->queryF($sql);
    $arr = $xoopsDB->fetchArray($result);
    $list = $xoopsDB->getRowsNum($result);

    xoops_cp_header();
    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    echo "<h4>" . _ADM_VIEWING_MESSAGE . "</h4>";
    if ($list)
    {
	echo "<div><b>"._ADM_EVENNEWS_MSGID.": No:</b> ".$arr['mess_id']."</div><br />";
	echo "<div><b>"._ADM_EVENNEWS_MSGINFO."</b></div>";	
	$sentfrom = xoops_getLinkedUnameFromId(intval($arr['user_id']));

	echo "<div><b>"._ADM_EVENNEWS_USERID.":</b> " . $sentfrom . "</div>";
	echo "<div><b>"._ADM_EVENNEWS_FROM.":</b> " . $arr['mess_from'] . "</div>";
	echo "<div><b>"._ADM_EVENNEWS_TIMESENT.":</b> " . formatTimestamp($arr['time_sent'], "d-M-Y") . "</div><br />";
	echo "<div><b>"._ADM_EVENNEWS_SENTTO.":</b> " . $arr['sent_to'] . "</div>";
	echo "<div><b>"._ADM_EVENNEWS_FAILED.":</b> " . $arr['fail_to'] . "</div><br />";
	echo "<div><b>"._ADM_EVENNEWS_SUBJECT.":</b> " . $arr['subject'] . "</div>";

// correction (typo) to show archived messages in admin -- tl
	echo "<div><b>"._ADM_EVENNEWS_MESSAGE.":</b><br /> " . $myts->displayTarea(trim($arr['message']), 1, 1, 1, 1, 0) . "</div>";

    } else{
        echo "<div align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</div>\n";

    } 
    echo "</table>\n";
    xoops_cp_footer();
} 

?>