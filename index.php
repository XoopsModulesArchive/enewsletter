<?php
include_once('header.php');
$myts = &MyTextSanitizer::getInstance();
$dirname = $xoopsModule->dirname();
include_once XOOPS_ROOT_PATH . "/modules/" . $dirname . "/include/functions.php" ;
if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']))
    $action = $_GET['action'];
else
    $action = '';
// determine proper smarty template for page

//echo "ACTION1:".$action."<P>";
switch ($action)
{
    case 'subscribe_conf':
    case 'subscribe':
    case 'search_subscriber':

        $xoopsOption['template_main'] = 'enewsletter_subscr.html';
        break;

    default:
        $xoopsOption['template_main'] = 'enewsletter_index.html';
        break;
} 

include_once(XOOPS_ROOT_PATH . '/header.php'); // Include the page header

$xoopsTpl->assign('lang_status', _EN_STATUS);
// Fill smarty variables for each page
switch ($action)
{
    case 'subscribe_conf':
        if ($_POST['user_mail'] == '')
        {
            $xoopsTpl->assign('en_message', _EN_GENERROR);
            break;
        } 

        $ret = addUser(); // Try to add user                
        // Display Appropriate Response to addUser Return value
        $xoopsTpl->assign('en_message', $ret);
        

    case 'subscribe':case 'search_subscriber':

        global $xoopsModuleConfig;

        $xoopsTpl->assign('en_title', sprintf(_EN_SUBTITLE, $xoopsConfig['sitename']));
        $xoopsTpl->assign('en_form_action', XOOPS_URL . '/modules/' . $dirname . '/index.php');
        $xoopsTpl->assign('EN_DISCLAIMER', sprintf($xoopsModuleConfig['join_text_disclaimer'], $xoopsConfig['sitename'], $xoopsConfig['sitename']));
        $xoopsTpl->assign('en_remote_host', isset($_SERVER['REMOTE_ADDR']));
        $xoopsTpl->assign('lang_emailadress', _EN_EMAIL_ADRESS);
        $xoopsTpl->assign('lang_denote', _EN_DENOTE);
        $xoopsTpl->assign('lang_nickname', _EN_NICKNAME);
        $xoopsTpl->assign('lang_name', _EN_NAME);
        $xoopsTpl->assign('lang_email', _EN_EMAIL_ADRESS);

        $xoopsTpl->assign('lang_submit_button', _EN_SUBMITBTN);
        $xoopsTpl->assign('lang_enter_name', _EN_JS_ERROR1);
        $xoopsTpl->assign('lang_enter_surname', _EN_JS_ERROR2);
        $xoopsTpl->assign('lang_enter_email', _EN_JS_ERROR3);

        $xoopsTpl->assign('lang_emailtype', _EN_EMAIL_TYPE);
        $xoopsTpl->assign('lang_emailtxt', _EN_EMAIL_TEXT);
        $xoopsTpl->assign('lang_emailhtml', _EN_EMAIL_HTML);
        $xoopsTpl->assign('lang_new_user', _EN_NEWUSER);
        $xoopsTpl->assign('lang_email_new_user', _EN_EMAIL_NEWUSER);
        $xoopsTpl->assign('lang_email_confirm', _EN_EMAIL_CONFIRM);

		$blnFound=false;
        if ($xoopsUser)
        {
            $xoopsTpl->assign('en_realname', $xoopsUser->getVar('name'));
            $xoopsTpl->assign('en_username', $xoopsUser->getVar('uname'));
            $xoopsTpl->assign('en_email', $xoopsUser->getVar('email'));
            $emailcheck=$xoopsUser->getVar('email');
            $blnFound=true;
        }elseif(isset($_POST['user_mail'])){
            $xoopsTpl->assign('en_realname', $_POST['user_name']);
            $xoopsTpl->assign('en_username', $_POST['user_nick']);
            $xoopsTpl->assign('en_email', $_POST['user_mail']);
            $emailcheck=$_POST['user_mail'];   
            $blnFound=true;         
        }elseif(isset($_POST['search_subscriber_mail'])){
		    $query = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_email='" . $myts->makeTboxData4Save($_POST['search_subscriber_mail']) . "' ";
		    $result = $xoopsDB->query($query);
		    $myarray = $xoopsDB->fetchArray($result);               	
            $xoopsTpl->assign('en_realname', $myarray['user_name'] );
            $xoopsTpl->assign('en_username', $myarray['user_nick']);
            $xoopsTpl->assign('en_email', $myarray['user_email']);
            
            print_r($myarray);
            $emailcheck = $myarray['user_email'];
            $blnFound=true;
}        
        if ($blnFound==false)
        {
            $xoopsTpl->assign('en_realname', _EN_JS_ERROR1);
            $xoopsTpl->assign('en_username', _EN_JS_ERROR5);
            $xoopsTpl->assign('en_email', _EN_JS_ERROR3);
            $emailcheck="";
        } 
            
		$nletterid_arrs=array();
        if ($emailcheck!="")
        {		
			$subscriber_id=intval(getSubscriberId(0,$emailcheck));
			//echo "SUBID1:".$subscriber_id."<P>";
		    if ($subscriber_id>0)
		    {			
				//echo "SUBID2:".$subscriber_id."<P>";
				$nletterid_arrs=getNewslettersBySubscriberId($subscriber_id);	
				
		    }
		    //echo "SUBID3:".$subscriber_id."<P>";
        }
	    $xoopsTpl->assign('val_subscriber_id', intval($subscriber_id));
		//print_r($nletterid_arrs);echo "<P>";
	            
    	$arruser=array();
    	$arruser=selectNewsletters();
    	$subscribe_arr=array();
    	while ($arr = $xoopsDB->fetchArray($arruser))    
        {
	        if ($subscriber_id>0)
	        {	        	
	        	//echo "-2-<P>";
	        	// print_r($arr);echo "<P>";
	        	if (in_array($arr['nletter_id'],$nletterid_arrs)){
	        		$sub_bln_yes="checked";
	        		$sub_bln_no="";
	        	}else{
	        		$sub_bln_yes="";
	        		$sub_bln_no="CHECKED";
	        	}   
	        }else{
	        		$sub_bln_yes="";
	        		$sub_bln_no="CHECKED";		        	
	        }     	
        	
        	$subscribe_arr[]=array('nletter_id'=>$arr['nletter_id'], 'nletter_name'=>$arr['nletter_name'], 'nletter_description'=>$arr['nletter_description'], 'sub_bln_yes'=>$sub_bln_yes, 'sub_bln_no'=>$sub_bln_no);

        }
        //print_r($subscribe_arr);
        $xoopsTpl->assign('sub_options', $subscribe_arr);
        // Newsletter select END

        
        break;
 
    default:

        global $xoopsUser, $xoopsDB, $xoopsModuleConfig;

        $messages = array();
        $limit_number = $xoopsModuleConfig['num_messages'];

        $sql = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . "" ;
        $list = $xoopsDB->getRowsNum($xoopsDB->query($sql));

        $sql2 = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_messages') . " ORDER BY time_sent DESC LIMIT $limit_number " ;
        $result = $xoopsDB->query($sql2);
        while ($myarray = $xoopsDB->fetchArray($result))
        {
            $messages['mess_id'] = $myts->stripSlashesGPC($myarray['mess_id']);
            $messages['user_id'] = xoops_getLinkedUnameFromId($myarray['user_id']);
            $user = new XoopsUser($myarray['user_id']);
            $messages['user_email'] = $user->email();
            $messages['user_email'] = checkEmail($messages['user_email'], true);
            $messages['time_sent'] = formatTimestamp($myarray['time_sent'], "D d-M-Y");
            $messages['subject'] = $myarray['subject'];
            $messages['message'] = strip_tags(trim($myarray['message']));

            $xoopsTpl->append('messages', $messages);
        } 

        if ($list < $limit_number)
        {
            $limit = $list;
        } 
        else
        {
            $limit = $limit_number;
        } 
        // $xoopsTpl->assign('lang_heading', "Newsletter");
        $xoopsTpl->assign('lang_description_heading', "Description");
        $xoopsTpl->assign('lang_description', $xoopsModuleConfig['description']);
        $xoopsTpl->assign('lang_most_recent_messages', "<b>Most Recent Messages</b> (Showing $limit of $list)");
        $xoopsTpl->assign('lang_message_num', "Msg#");
        $xoopsTpl->assign('lang_total_messages', "Total Messages in Archive: $list");
        $xoopsTpl->assign('lang_view_archive', "View All Messages");
        $xoopsTpl->assign('lang_join_newsletter', "Newsletter Membership");

        $xoopsTpl->assign('lang_to_join', $xoopsModuleConfig['join_text']);
        $xoopsTpl->assign('lang_to_leave', $xoopsModuleConfig['leave_text']);

        $xoopsTpl->assign('lang_tooltip1', _EN_TOOLTIP1);
        $xoopsTpl->assign('lang_tooltip2', _EN_TOOLTIP2);
        $xoopsTpl->assign('lang_heading', $xoopsModule->getVar('name'));
        $xoopsTpl->assign('subscr_url', XOOPS_URL . '/modules/enewsletter/index.php?action=subscribe');
        $xoopsTpl->assign('unsubscr_url', XOOPS_URL . '/modules/enewsletter/index.php?action=unsubscribe');
        $xoopsTpl->assign('news_images', sprintf('%s/modules/%s/language/%s/', XOOPS_URL, $dirname, $xoopsConfig['language']));
        unset($messages);
        break;
} 
// Include the page footer
include_once(XOOPS_ROOT_PATH . '/footer.php');

/**
 * ------------------------------------------------------------
 * function delUser() - Removes a user from the newsletter by marking
 * them unconfirmed.
 * -------------------------------------------------------------
 */
function delUser()
{
    global $xoopsDB, $myts, $xoopsConfig, $xoopsModule;

    $query = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_email='" . $myts->makeTboxData4Save($_POST['user_mail']) . "' ";
    $result = $xoopsDB->query($query);
    $myarray = $xoopsDB->fetchArray($result);

    $mymail = $myts->makeTboxData4Save($_POST['user_mail']);
    if ($myarray)
    {
        if ($myarray['confirmed'] == '0')
            return -1;

        $query = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET confirmed='0' WHERE user_email='$mymail'";
        $result = $xoopsDB->queryF($query);
        return 1;
    } 
    else
    {
        return -2;
    } 
} 

/**
 * ------------------------------------------------------------
 * function addUser() - Adds a user to db and sends confirm email
 * -------------------------------------------------------------
 */
function addUser()
{
    global $xoopsDB, $myts, $xoopsConfig, $xoopsModule, $dirname;
	$statusMessages=array();
	
    $user_name = $myts->makeTboxData4Save($_POST['user_name']);
    $user_nick = $myts->makeTboxData4Save($_POST['user_nick']);
    $user_mail = $myts->makeTboxData4Save($_POST['user_mail']);
    $user_format = ($_POST['user_format'] == 1) ? 1 : 0;
    $user_host = $myts->makeTboxData4Save($_SERVER['REMOTE_ADDR']);


	$query = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_email='$user_mail' ";
    $myarray = $xoopsDB->fetchArray($xoopsDB->query($query));

    $xoopsMailer = &getMailer();
    $xoopsMailer->useMail(); 
    // Hervé
    $xoopsMailer->setTemplateDir(XOOPS_ROOT_PATH . '/modules/' . $dirname . '/language/' . $xoopsConfig['language'] . '/mail_template');
    $xoopsMailer->setTemplate("confirm_email.tpl");

	$updateType= "";
	$outputMessage="";


	// Check if User email exists already, if so then go to update mode
    if ($myarray['user_email'] == $user_mail)
    {
    	$updateType="Update"; 
    }
        	
	// User is trying to add a new subscriber but lets also check that the email does not already exist (see $updateType!="Update")
    if (($_POST['user_confirm'] == 0 and $updateType!="Update") or (!$myarray))
    {
    	// Ok, we are safe to add a new subscriber so lets go to insert mode
    	$updateType="Insert";
        // If the user nickname is the same as existing user but the user email is different then report
        // error, declaring that the same nickname can not be used.
        /* We are not that concerned if a newsletter member has the same username because they do not use the username to login or anything  
        if (($myarray_name['user_nick'] == $user_nick) and ($myarray['user_email'] != $user_mail))
        {
            return -3;
        } 
        */   	
    	// Check if User email exists already
    	/*
        if ($myarray['user_email'] == $user_mail)
        {
        	$updateType="Update";     	
            //return 1;
            $outputMessage=sprintf(_EN_CONFIRM, $_POST['user_mail']);
        }
        */

    } elseif ($_POST['user_confirm'] != 0 and $updateType=="Update"){
    // This is an existing user and the the user wants a confirmation email 
        if ($myarray['user_email'] == $user_mail)
        {
            $confirm_url = XOOPS_URL . '/modules/' . $xoopsModule->dirname() . '/confirm.php?id=' . $myarray['user_conf'];
            $xoopsMailer->setToEmails($myarray['user_email']);
            $xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
            $xoopsMailer->setFromName($xoopsConfig['sitename']);
            $xoopsMailer->setSubject(_EN_CONFIRM_SUBJECT);
            $xoopsMailer->assign('X_UNAME', $user_name);
            $xoopsMailer->assign('X_CONTACT_NAME', $xoopsConfig['adminmail']);
            $xoopsMailer->assign('VALIDATION_URL', $confirm_url);
            if ($xoopsMailer->send())
            {
                //return 2;
                $statusMessages[] = sprintf(_EN_RESENDCONFIRM, $myarray['user_email']);
            } 
            else
            {
                //return -2;
                 $statusMessages[] =sprintf(_EN_CONFIRM, _EN_EMAILERROR);
            } 
        } 
        else
        {
	        //return -5;
	        $statusMessages[] =sprintf(_EN_CONFIRM, _EN_NOUSERTOCONFIRM);
		} 
    } 

	// If a user with the same email does not exist then we can insert a new user
    //if (!$myarray){$updateType="Insert";}	
    
    if ($updateType!="")
    {
        $time = time();
        $better_token = md5(uniqid(rand(), 1));

		if ($updateType=="Insert"){
	        $query = "INSERT INTO " . $xoopsDB->prefix('enewsletter_members') . " (user_id, user_name, user_nick, user_email, user_host, user_conf, confirmed, activated, user_time, user_html, user_lists  ) ";
	        $query .= "VALUES (0, '" . $user_name . "', '" . $user_nick . "', '" . $user_mail . "',
				'" . $user_host . "', '$better_token', '0', '0', '$time', '$user_format', '0')";
			$result = $xoopsDB->queryF($query);
			$subscriber_id=$xoopsDB->getInsertId();
			if ($result){
				$statusMessages[] = "Subscription details have been updated successfully!";
			}else{
				$statusMessages[] = "Unable to update subscription details successfully.  Please contact administrator.";
			}			
		}else{
			$subscriber_id=getSubscriberId(0,$user_mail);
			$query = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET user_name = '" . $user_name . "', user_nick ='" . $user_nick . "', user_html = '$user_format' WHERE subscriber_id =" . $subscriber_id . "";
			$result = $xoopsDB->queryF($query);
			if ($result){
				$statusMessages[] = "Subscription details have been updated successfully!";
			}else{
				$statusMessages[] = "Unable to update subscription details successfully.  Please contact administrator.";
			}
		}

        
        $error = "Could not update user information: <br /><br />";
        $error .= $query;
        if (!$result)
        {
            trigger_error($error, E_USER_ERROR);
        } else{
        	
			$nletterid_arrs=array();
			if (isset($subscriber_id)) {
				$arruser=array();
	    		$arruser=selectNewsletters();
	    		$nletterids_arrs=array();
	        	while ($arr = $xoopsDB->fetchArray($arruser))    
	        	{    	
	        		//$arr['nletter_id']
	        		$post_nletter_name='newsletter_'.$arr['nletter_id'];
	        		if ($_POST[$post_nletter_name]==1){
	        			$post_nletter_name='newsletter_'.$arr['nletter_id'];
	        			//echo "PNAME:".$post_nletter_name."<P>";
						$nletterids_arrs[] = $arr['nletter_id'];
	        		}
	        		
	        	}		
	        	deleteNewslettersBySubscriberId($subscriber_id);
	        	insertNlettersBySubscriberId($subscriber_id,$nletterids_arrs);
			}		        	
        	
        	
        }
        if ($updateType=="Insert"){
	        $confirm_url = XOOPS_URL . '/modules/' . $xoopsModule->dirname() . '/confirm.php?id=' . $better_token;
	        $xoopsMailer->setToEmails($_POST['user_mail']);
	        $xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
	        $xoopsMailer->setFromName($xoopsConfig['sitename']);
	        $xoopsMailer->setSubject(_EN_CONFIRM_SUBJECT);
	        $xoopsMailer->assign('X_UNAME', $user_name);
	        $xoopsMailer->assign('X_CONTACT_NAME', $xoopsConfig['adminmail']);
	        $xoopsMailer->assign('VALIDATION_URL', $confirm_url);
	        if ($xoopsMailer->send())
	        {
	            //return 1;
	            $statusMessages[] =sprintf(sprintf(_EN_CONFIRM, $_POST['user_mail']));
	        } 
	        else
	        {
	            //return -2;
	            $statusMessages[]=sprintf(_EN_CONFIRM, "<br/>"._EN_EMAILERROR);
	        } 
        }
    } 
    else
    {
        //return -6;
        $statusMessages[]=sprintf(_EN_CONFIRM, "<br/>"._EN_UNKNOWNERROR);
    } 
    return $statusMessages;
} 

?>
