<?php
include_once('admin_header.php');

if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
    $action = $_GET['action'];
else
    $action = '';

$spaw_root = XOOPS_ROOT_PATH . '/modules/spaw/';
include_once $spaw_root . 'spaw_control.class.php';

switch ($action)
{
    case 'delete_temp':
        deleteTemplate();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'view_archives':
        default:
		showArchive();
        break;
    case 'view_temp':
        viewTemplate($_POST['mess_id']);
        break;
    case 'send':
        messageForm($_GET['mess_id']);
        break;

    case 'default':
    
        xoops_cp_header();
        evadminmenu(_ADM_EVENNEWS_ADMINMENU);
        xoops_cp_footer();
        break;
}
// Ajout Hervé
function deleteTemplate()
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
        redirect_header("templates.php?action=view_archives", 1, _MD_MSGDELETED);
        exit();
    }
    else
    {
        xoops_cp_header();
        echo "<h4>" . _ADM_CONFDELETE . "</h4>";
        xoops_confirm(array('action' => 'delete_temp', 'mess_id' => $mess_id, 'ok' => 1), 'templates.php', _MD_WARNING);
        xoops_cp_footer();
    }
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
    if (!$amount)
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
        echo "<td>" . _ADM_EVENNEWS_MESSAGE . " :</td><td>" . nl2br($arr[message]) . "</td>\n";
        echo "</tr>\n";
    }
    else
    {
        echo "<tr>\n";
        echo "<td colspan =\"8\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>";
    xoops_cp_footer();
}

function showArchive()
{
    global $xoopsDB, $adminURL, $editimg, $deleteimg, $viewimg;

    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " ORDER BY time_sent DESC" ;
    $result = $xoopsDB->query($sql);
    $list = $xoopsDB->getRowsNum($result);

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
    echo "<th align = \"center\">" . _ADM_EVENNEWS_ID . ".</th>
		<th align = \"center\">" . _ADM_EVENNEWS_SENTTO . "</th>
		<th align = \"center\">" . _ADM_EVENNEWS_FAILED . "</th>
		<th align = \"center\">" . _ADM_EVENNEWS_DATESENT . "</th>
		<th>" . _ADM_EVENNEWS_SUBJECT . "</th>
		<th align = \"center\">" . _ADM_EVENNEWS_ACTION . "</th>
	";
    if ($result)
    {
        while ($arr = $xoopsDB->fetchArray($result))
        {
            echo "<tr>";
            echo "<td class = \"head\" align = \"center\">" . $arr['mess_id'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['sent_to'] . "</td>";
            echo "<td class = \"even\" align = \"center\">" . $arr['fail_to'] . "</td>";
            list($year, $month, $day, $hour, $min, $sec) = explode(":", eregi_replace("[' '|-]", ":", $arr['time_sent']));
            echo "<td  class = \"even\" align = \"center\" nowrap>" . formatTimestamp(mktime($hour, $min, $sec, $month, $day, $year)) . "</td>\n"; 
            // echo "<td>".$arr['time_sent']."</td>\n";
            echo "<td class = \"even\">" . $arr['subject'] . "</td>";
            echo "<td nowrap class = \"even\" align =\"center\">
				<a href='templates.php?action=send&amp;mess_id=" . $arr['mess_id'] . "'>$editimg</a> 
				<a href='templates.php?action=view_temp&amp;mess_id=" . $arr['mess_id'] . "'>$viewimg</a>
				<a href='templates.php?action=delete_temp&amp;mess_id=" . $arr['mess_id'] . "'>$deleteimg</a>
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

    xoops_cp_footer();
}

function sendMessage()
{
    global $xoopsDB, $xoopsUser, $xoopsMailer, $xoopsConfig, $myts, $xoopsConfigUser;

    $myts = &MyTextSanitizer::getInstance();

    $list_members = ($_POST['list_members']) ? 1 : 0;

    $mail_from = $myts->oopsStripSlashesGPC($_POST['mail_from']);
    $mail_subj = $myts->oopsStripSlashesGPC($_POST['mail_subj']);
    $mail_mess = $myts->displayTarea($_POST['mail_mess'], 1, 1, 1, 1, 0);
    $mail_mess = $myts->oopsStripSlashesGPC($mail_mess);

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU, _ADM_EVENNEWS_SENDING);
    $confirmedtype = (isset($_POST['mail_message_emailuncomfirm'])) ? 0 : 1;
    $query = $xoopsDB->query("select * from " . $xoopsDB->prefix("enewsletter_members") . " where confirmed='$confirmedtype'");
    $list = $xoopsDB->getRowsNum($query);

    if ($list == 0 && $list_members)
    {
        echo "<b>Notice: No subscribed users to email.</b>";
        xoops_cp_footer();
        exit();
    }

    $sent_good = 0;
    $sent_bad = 0;

    $xoopsMailer = &getMailer();
    $member_handler = &xoops_gethandler('member');
    $tblUsers = Array();

    $mail_type_message = (isset($_POST['test_email'])) ? 1 : $_POST['mail_message_type'];

    if ($_POST['list_members'] == 1 && $mail_type_message == 0)
    {
        echo "<h5>Cannot send a PM to Mailing list members, this option is not available at the moment.</h5>";
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
        // $xoopsMailer->assign("SITENAME", $xoopsConfig['sitename']);
        // $xoopsMailer->assign("ADMINMAIL", $xoopsConfig['adminmail']);
        // $xoopsMailer->assign("SITEURL", XOOPS_URL . "/");
        $xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
        $xoopsMailer->setFromName($xoopsConfig['sitename']);
        $xoopsMailer->setBody($mail_mess);
        $xoopsMailer->setSubject($mail_subj);

        echo $_POST['mail_format']; 
        // if ($_POST['mail_format'] == 'html')
        // {
        $xoopsMailer->multimailer->IsHTML(true); 
        // }
        // else
        // {
        // $xoopsMailer->multimailer->IsHTML(false);
        // }
        $xoopsMailer->send();
        $sent_good = count($xoopsMailer->getSuccess());
        echo $xoopsMailer->getSuccess();
        $sent_bad = count($xoopsMailer->getErrors());
        echo $xoopsMailer->getErrors();

        $user_id = $xoopsUser->getVar('uid');
        $query = "INSERT INTO " . $xoopsDB->prefix('enewsletter_messages') . " (user_id, sent_to, fail_to, time_sent, message, subject, mess_from) ";
        $query .= "VALUES ($user_id, $sent_good, $sent_bad, NOW(), '$mail_mess', '$mail_subj', '$mail_from')"; 
        // Modif Hervé
        // echo "query = '$query'.<BR>\n";
        $result = $xoopsDB->queryF($query);
        echo _ADM_EVENNEWS_FROM . " : '$mail_from'.<BR>";
        echo _ADM_EVENNEWS_SUBJECT . " : '$mail_subj'.<BR>";
        echo _ADM_EVENNEWS_MESSAGE . ": '$mail_mess'.<BR>";
        echo _ADM_EVENNEWS_MSGARCHIVED . ". ($result)<BR>\n";
    }

    xoops_cp_footer();
}

function messageForm($messID)
{
    global $xoopsDB, $xoopsUser, $xoopsConfig, $adminURL;

    $arr = array();

    $messID = intval($messID);

    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE mess_id=$messID";
    $arr = $xoopsDB->fetchArray($xoopsDB->query($sql));

    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

    $addUserHeader = (!isset($messID)) ? _ADM_EVENNEWS_CREATE : _ADM_EVENNEWS_MODIFY;
	
    $sform = new XoopsThemeForm($addUserHeader, "op", xoops_getenv('PHP_SELF'));
    $sform->addElement(new XoopsFormSelectGroup(_ADM_EVENNEWS_GROUPS . "<br />", "mail_group", false, true, 5, true));

    $listmembers_select = new XoopsFormRadioYN(_ADM_EVENNEWS_LISTMEMBERS, 'list_members', '1', ' ' . _YES . '', ' ' . _NO . ' (Selecting \'Yes\' will override sending mail to groups members.)');
    $sform->addElement($listmembers_select);
    $sform->insertBreak("", "even");

    $mailtemplate = (isset($arr['mess_from'])) ? $arr['mess_from']: $xoopsUser->getVar('email');
    $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_FROM, 'mail_from', 50, 255, $xoopsUser->getVar('email')), true);
    $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_SUBJECT, 'mail_subj', 50, 255, $arr['subject']), true);
    $sform->insertBreak("", "even");
    
	$mailtemplate = (isset($arr['list_template'])) ? $arr['list_template']: '';
    $sql = "SELECT subject  FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE is_template = '1' ";
    $graph_array = $xoopsDB->fetchArray($xoopsDB->query($sql));

    $linkpage_select = new XoopsFormSelect('', 'list_template', $mailtemplate , 1, 0, 1);
    $linkpage_select->addOptionArray($graph_array);
    $linkpage_tray = new XoopsFormElementTray(_ADM_EVENNEWS_TEMPLATE, '&nbsp;');
    $linkpage_tray->addElement($linkpage_select);
	$sform->addElement($linkpage_tray); 

    $connect_checkbox = new XoopsFormCheckBox(_ADM_TESTEMAIL, "connect_email", 0);
    $connect_checkbox->addOption(1, " Connect this template.");
    $sform->addElement($connect_checkbox);

    $sform->insertBreak("", "even");	
	// $sform->addElement(new XoopsFormDhtmlTextArea(_ADM_EVENNEWS_MESSAGE, 'mail_mess', '', 10, 60), TRUE);
    $mainemailbodytext = ($arr['message']) ? $arr['message'] : '';
    ob_start();
    $sw = new SPAW_Wysiwyg('mail_mess', $mainemailbodytext, 'en', 'full', 'default', '95%', '600px');
    $sw->show();
    $sform->addElement(new XoopsFormLabel(_ADM_EVENNEWS_MESSAGE , ob_get_contents(), 1));
    ob_end_clean();

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

function viewTemplate($messID)
{
    global $xoopsDB;
    $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " WHERE mess_id=" . intval($messID);
    $result = $xoopsDB->query($sql);
    $arr = $xoopsDB->fetchArray($result);
    $list = $xoopsDB->getRowsNum($result);

    xoops_cp_header();
    evadminmenu(_ADM_EVENNEWS_ADMINMENU);

    echo "<table width='100%' cellpadding='2' cellspacing='1' class = \"outer\">\n";
    echo "<th colspan = \"2\">" . _ADM_EVENNEWS_USERID . ":</th>\n";
    echo "<tr>\n";
    if ($list)
    {
        echo "<td class = \"head\">" . _ADM_EVENNEWS_USERID . ":</td><td class = \"even\">" . $arr['user_id'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_SENTTO . " :</td><td class = \"even\">" . $arr['sent_to'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_FAILED . " :</td><td class = \"even\">" . $arr['fail_to'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_MSGID . " :</td><td class = \"even\">" . $arr['mess_id'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_TIMESENT . " :</td><td class = \"even\">" . $arr['time_sent'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_FROM . " :</th><td class = \"even\">" . $arr['mess_from'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_SUBJECT . " :</th><td class = \"even\">" . $arr['subject'] . "</td>\n";
        echo "</tr><tr>\n";
        echo "<td class = \"head\">" . _ADM_EVENNEWS_MESSAGE . " :</th><td class = \"even\">" . nl2br($arr['message']) . "</td>\n";
        echo "</tr>\n";
    }
    else
    {
        echo "<tr>\n";
        echo "<td colspan =\"7\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    xoops_cp_footer();
}

?>
