<?php
include "admin_header.php";

if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
    $action = $_GET['action'];
else
    $action = '';

switch ($action)
{
    case 'reactivate':
        if ($_POST['activate'] == 1)
        {
            reactivate();
        }
        else
        {
            redirect_header(xoops_getenv('PHP_SELF'), 2, "User Activation status unchanged");
        }
        exit();
        break;

    case 'import_users':
        global $xoopsDB, $xoopsModule;
        include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
        xoops_cp_header();
        evadminmenu(_ADM_EVENNEWS_ADMINMENU);
        $sform = new XoopsThemeForm(_ADM_EVENNEWS_IMPORTUSER, "importform", xoops_getenv('PHP_SELF'));
        $sform->addElement(new XoopsFormSelectUser(_ADM_EVENNEWS_MSGIMPORTUSER, 'userslist', false, '', 10, true), true);
        $sform->addElement(new XoopsFormHidden('action', 'launch_import'), false);
		
		$nletterid_arrs=array();
    	$arruser=array();
    	$arruser=selectNewsletters();
        while ($arr = $xoopsDB->fetchArray($arruser))    
        {
		$bln_subscribed=0;
        	$subscribe_select=array();
	        $subscribe_select[$arr['nletter_id']] = new XoopsFormRadioYN("Subscribe to ".$arr['nletter_name'], 'newsletter_'.$arr['nletter_id'], $bln_subscribed, ' ' . _YES . '', ' ' . _NO . '');
	        $sform->addElement($subscribe_select[$arr['nletter_id']]);

        }
            
       
        
                
        $button_tray = new XoopsFormElementTray('' , '');
        $submit_btn = new XoopsFormButton('', 'submit', _ADM_EVENNEWS_BNTIMPORTUSEROK, 'submit');
        $button_tray->addElement($submit_btn);
        $cancel_btn = new XoopsFormButton('', 'reset', _ADM_EVENNEWS_BNTIMPORTUSERCANCEL, 'reset');
        $button_tray->addElement($cancel_btn);
        $sform->addElement($button_tray);
        $sform->display();
        xoops_cp_footer();
        break;

    case 'rem_user_conf':
        removeUser();
        break;

    case 'add_user':
        global $xoopsDB;
		$user_id = 0;
        $user_name = '';
        $user_nick = '';
        $user_email = '';
        $user_format = '';
		$user_confirmed = 1;
        $user_activated = 0;
        $arruser = array();

		$nletter_id=intval($_GET['nletter_id']);


        if (isset($_GET['subscriber_id']))
        {
        	
        	$arruser=getNewslettersByNletterId($_GET['subscriber_id']);
        	
            $subscriber_id = $arruser['subscriber_id'];
            $user_id = $arruser['user_id'];
            $user_name = $arruser['user_name'];
            $user_nick = $arruser['user_nick'];
            $user_email = $arruser['user_email'];
            $user_format = $arruser['user_html'];
			$user_confirmed = $arruser['confirmed'];
            $user_activated = $arruser['activated'];
			$user_host = $arruser['user_host'];
        }

        $addUserHeader = (!isset($_GET['subscriber_id'])) ? _ADM_EVENNEWS_ADDUSER : _ADM_EVENNEWS_MODIFYUSER . ": " . $user_nick;

        global $adminURL, $xoopsUser;
        xoops_cp_header();

        evadminmenu(_ADM_EVENNEWS_ADMINMENU);

        include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
        $sform = new XoopsThemeForm($addUserHeader, "op", xoops_getenv('PHP_SELF'));
        
		if (isset($_GET['subscriber_id'])) {
		 	$sform -> addElement(new XoopsFormLabel(_ADM_EVENNEWS_IP, $user_host));   
		}
		$sform->addElement(new XoopsFormText(_ADM_EVENNEWS_NAME, 'user_name', 50, 255, $user_name), false);
        $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_NICKNAME, 'user_nick', 50, 255, $user_nick), true);
        $sform->addElement(new XoopsFormText(_ADM_EVENNEWS_EMAIL, 'user_mail', 50, 255, $user_email), true);
        $mailformat_select = new XoopsFormRadioYN(_ADM_MAILFORMAT, 'user_format', $user_format, ' ' . _ADM_TEXTFORMAT . '', ' ' . _ADM_HTMLFORMAT . '');
        $sform->addElement($mailformat_select);

    	$sform->insertBreak("", "even");

		$nletterid_arrs=array();
		if (isset($_GET['subscriber_id'])) {
			//echo "-3-".$_GET['subscriber_id']."<P>";
			$nletterid_arrs=getNewslettersBySubscriberId($_GET['subscriber_id']);
		}
		//echo "<P>SQL:".$sql."<P>";
		//print_r($nletterid_arrs);
    	$arruser=array();
    	$arruser=selectNewsletters();
        while ($arr = $xoopsDB->fetchArray($arruser))    
        {
        	//echo "-2-<P>";
        	// print_r($arr);echo "<P>";
        	if (in_array($arr['nletter_id'],$nletterid_arrs)){
        		$bln_subscribed=1;
        	}else{
        		$bln_subscribed=0;
        	}        	
        	$subscribe_select=array();
	        $subscribe_select[$arr['nletter_id']] = new XoopsFormRadioYN("Subscribe to ".$arr['nletter_name'], 'newsletter_'.$arr['nletter_id'], $bln_subscribed, ' ' . _YES . '', ' ' . _NO . '');
	        $sform->addElement($subscribe_select[$arr['nletter_id']]);

        }
            
        $sform->insertBreak("", "even");
                
        $confirm_select = new XoopsFormRadioYN(_ADM_USERCONFIRMED, 'confirmed', $user_confirmed, ' ' . _YES . '', ' ' . _NO . '');
        $sform->addElement($confirm_select);

        if (isset($_GET['subscriber_id']))
        {
            $activate_select = new XoopsFormRadioYN(_ADM_USERACTIVATED, 'activated', $user_activated, ' ' . _YES . '', ' ' . _NO . '');
            $sform->addElement($activate_select);
        }
        else
        {
            $sform->addElement(new XoopsFormHidden('activated', 0));
        }

        $user = "localhost:" . $xoopsUser->getVar('uname');

        $sform->addElement(new XoopsFormHidden('user_host', $user));
		$sform->addElement(new XoopsFormHidden('user_host', $user));
        $button_tray = new XoopsFormElementTray('', '');

		if ($nletter_id>0){
        	$hidden3 = new XoopsFormHidden('nletter_id', $nletter_id);
        	$button_tray->addElement($hidden3);
		}
                
        $hidden2 = new XoopsFormHidden('subscriber_id', $subscriber_id);
        $button_tray->addElement($hidden2);
        $hidden = new XoopsFormHidden('action', 'add_user_conf');
        $button_tray->addElement($hidden);
        $button_tray->addElement(new XoopsFormButton('', 'submit', _ADM_EVENNEWS_SUBMIT, 'submit'));
        $button_tray->addElement(new XoopsFormButton('', 'reset', _ADM_EVENNEWS_RESETFORMBTN, 'reset'));
        $sform->addElement($button_tray);
        $sform->display();
        unset($hidden);
        xoops_cp_footer();
        break;

    case 'add_user_conf':
        saveUser();
        break;

    case 'launch_import':
        launchimport();
        break;

    case 'clean_unconf':
        delUnconf();
        break;

    case 'list':
    default:

        include XOOPS_ROOT_PATH . '/class/pagenav.php';

        global $xoopsDB, $adminURL;

        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $showuser = isset($_GET['showuser']) ? intval($_GET['showuser']) : 0;
		$showorder = isset($_GET['showorder']) ? intval($_GET['showorder']): 0;
        $showtype = isset($_GET['showtype']) ? intval($_GET['showtype']) : 0;

        $showuser_data = array("", "WHERE confirmed = '1'", "WHERE activated = '0'");
        $order_data = array("ASC", "DESC");
		$listtype = array("user_name", "user_name", "user_nick", "user_email", "user_time");

        $query = "select * from " . $xoopsDB->prefix('enewsletter_members') . " ";
        $query .= "" . $showuser_data[$showuser] . " ";
        $query .= "ORDER BY subscriber_id DESC";
        echo "QER:".$query."<P>";
		$result = $xoopsDB->query($query, 10, $start);
        $list = $xoopsDB->getRowsNum($result);

        $result2 = $xoopsDB->query("SELECT COUNT(*) FROM " . $xoopsDB->prefix("enewsletter_members") . " ");
        list($numrows) = $xoopsDB->fetchRow($result2);

        xoops_cp_header();

        evadminmenu(_ADM_EVENNEWS_ADMINMENU );

        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _ADM_EVENNEWS_USERLIST . "</legend>";
		echo "<div style=\"padding: 8px;\"><b>Show: </b>";
        echo showuser($showuser);
        //echo " By ";
        //echo showtype($showtype);

       // echo " in ";
        //echo showlist($showorder);
        //echo " order.</div><br />";
		echo "</div>";
        echo "<table width='100%' cellpadding='2' cellspacing='1' class = \"outer\">\n";
        echo "<th align = \"center\">" . _ADM_EVENNEWS_ID . "</th>";
        echo "<th align = \"center\" nowrap>" . _ADM_EVENNEWS_CONFIRMED . "</th>";
        echo "<th align = \"center\" nowrap>" . _ADM_EVENNEWS_ACTIVATED . "</th>"; 
        // echo "<th>" . _ADM_EVENNEWS_USERNAME . "</th>";
        echo "<th>" . _ADM_EVENNEWS_NICKNAME . "</th>";
        echo "<th>" . _ADM_EVENNEWS_EMAIL . "</th>"; 
        echo "<th>" . _ADM_EVENNEWS_HOST . "</th>";
        echo "<th align = \"center\">" . _ADM_EVENNEWS_SUBSCRIBE . "</th>";
        echo "	<th align = \"center\">" . _ADM_EVENNEWS_ACTION . "</th>\n";
        if (!$list)
        {
            echo "<tr>\n";
            echo "<td colspan =\"9\" class = \"head\" align = \"center\">" . _ADM_EVENNEWS_NOTHINGINDB . "</td>\n";
            echo "</tr>\n";
        }
        else
        {
            while ($arr = $xoopsDB->fetchArray($result))
            {
                $mail = $arr['user_email'];
                if (!$mail)
                {
                    $mail = "&nbsp;";
                }
                $conf = "";

                $conf = ($arr['confirmed'] == '1') ? _ADM_EVENNEWS_YES : _ADM_EVENNEWS_NO;
                $actvate = ($arr['activated'] == '1') ? _ADM_EVENNEWS_YES : _ADM_EVENNEWS_NO;

                echo "<tr>";
                echo "<td nowrap class = \"head\" align = \"center\">" . $arr['subscriber_id'] . "</td>";
                echo "<td nowrap class = \"even\" align = \"center\">$conf</td>";
                echo "<td nowrap class = \"even\" align = \"center\">$actvate</td>"; 
                // echo "<td nowrap class = \"even\">" . $arr['user_name'] . "</td>";
                echo "<td nowrap class = \"even\">" . $arr['user_nick'] . "</td>";
                echo "<td nowrap class = \"even\">" . $mail . "</td>"; 
                echo "<td nowrap class = \"even\">" . $arr['user_host'] . "</td>";
                echo "<td class = \"even\" align = \"center\">" . formatTimestamp($arr['user_time'], "d-M-Y") . "</td>\n";
                echo "<td class = \"even\" align =\"center\" nowrap>
				<a href='" . xoops_getenv('PHP_SELF') . "?action=add_user&amp;subscriber_id=" . $arr['subscriber_id'] . "'>$editimg</a>
				<a href='" . xoops_getenv('PHP_SELF') . "?action=rem_user_conf&amp;subscriber_id=" . $arr['subscriber_id'] . "'>$approve</a>
				<a href='" . xoops_getenv('PHP_SELF') . "?action=clean_unconf&amp;subscriber_id=" . $arr['subscriber_id'] . "'>$deleteimg</a></td>";
            }
        }
        echo "</tr></table>\n";
        $pagenav = new XoopsPageNav($numrows, 10, $start, 'start');
        echo '<div text-align="right" style="padding: 8px;">' . $pagenav->renderNav() . '</div>';
		echo "</fieldset>";
        
		if ($list)
        {
            echo "<form action='" . xoops_getenv('PHP_SELF') . "' method='post'>\n";
			echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _ADM_MAINTAINACE . "</legend>";
            echo "<input type='hidden' name='action' value='clean_unconf'>\n";
            echo "<div align = \"left\" style=\"padding: 8px;\"><input type='submit' name='clean_unconf' value='" . _ADM_EVENNEWS_CLEARUNCONFIRMED . "'>";
			echo "</fieldset>";
			echo "</form>\n";
            //echo "<form action='" . xoops_getenv('PHP_SELF') . "' method='post'>\n";
            //echo "<input type='hidden' name='action' value='clean_nonactive'>\n";
            //echo "<input type='submit' name='clean_unconf' value='" . _ADM_EVENNEWS_CLEARNONACTIVE . "'></div>\n";
            //echo "</form>";
            //echo "<form action='" . xoops_getenv('PHP_SELF') . "' method='post'>\n";
            //echo "<input type='submit' name='clean_unconf' value='" . _ADM_EVENNEWS_CLEARNONACTIVE . "'>\n";
            //select_date();
            //echo "</form>\n";
            
        }

        xoops_cp_footer();
        break;
}

?>
