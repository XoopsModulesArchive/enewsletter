<?php

/**
 * Start of User Functions here
 */
if (isset($_POST['action']))
    $action = $_POST['action'];
else if (isset($_GET['action']) && !isset($_POST['action']))
    $action = $_GET['action'];
else
    $action = '';

function is_error($message)
{
    echo "ERROR:";
	$error = "Could not retrive information from the database: <br /><br />" . $message;
	trigger_error($error, E_USER_ERROR);
}


/**
 * Type: Private
 * 
 * Import Xoops Users into mailing list database.
 */
function launchimport()
{
    global $xoopsDB, $xoopsUser;
    xoops_cp_header();

    evadminmenu(_ADM_EVENNEWS_ADMINMENU);
    $imported = 0;
    while (list($null, $userid) = each($_POST["userslist"]))
    {
        $sql = "SELECT count(user_id) as cpt from " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_id=$userid";
        $arr = $xoopsDB->fetchArray($xoopsDB->query($sql));
        if ($arr['cpt'] == 0) // The user is not in the table
            {
                // Search user
                $date = time();
				$sqluser = "SELECT name, uname, user_regdate, email, user_mailok FROM " . $xoopsDB->prefix("users") . " WHERE uid= $userid";
            	$arruser = $xoopsDB->fetchArray($xoopsDB->queryF($sqluser));
            	if (trim($arruser['email'] != ''))
            	{
                	if ($arruser['user_mailok'] == 1) // User accepts emails
                    {
                        $date = time();
						$better_token = md5(uniqid(rand(), 1));
                    	$sqlinsert = sprintf("INSERT INTO %s (subscriber_id,user_id, user_name, user_nick, user_email, user_host, user_conf, confirmed, activated, user_time) VALUES (%u ,%u ,'%s' ,'%s', '%s', '%s', '%s', '1', '1' , '%s')", $xoopsDB->prefix('enewsletter_members'), 0, $userid, $arruser['name'], $arruser['uname'], $arruser['email'], '', $better_token, $date);
                    	if (!$resultinsert = $xoopsDB->queryF($sqlinsert))
                    	{
                        	printf(_ADM_EVENNEWS_USERSMSG5, $xoopsUser->getUnameFromId($userid));
                    	}
                    	else // User inserted successfully
                        {
                        	$subscriber_id=getSubscriberId($userid);
							deleteNewslettersBySubscriberId($subscriber_id);
							if ($userid) {						
								$arruser=array();
					    		$arruser=selectNewsletters();
					    		$nletterids_arrs=array();
					        	while ($arr = $xoopsDB->fetchArray($arruser))    
					        	{    					        		
					        		//$arr['nletter_id']
					        		$post_nletter_name='newsletter_'.$arr['nletter_id'];
					        		if ($_POST[$post_nletter_name]==1){		        			
					        			//echo "PNAME:".$post_nletter_name."<P>";
										$nletterids_arrs[] = $arr['nletter_id'];
					        		}
					        		
					        	}		
					        	insertNlettersBySubscriberId($subscriber_id,$nletterids_arrs);
							}		                        	
                            printf(_ADM_EVENNEWS_USERSMSG4, $xoopsUser->getUnameFromId($userid));
                    	}
                }
                else
                {
                    printf(_ADM_EVENNEWS_USERSMSG3, $xoopsUser->getUnameFromId($userid));
                }
            }
            else // Empty email adress
                {
                    printf(_ADM_EVENNEWS_USERSMSG2, $xoopsUser->getUnameFromId($userid));
            }
        }
        else // User already present in the table
            {
                printf(_ADM_EVENNEWS_USERSMSG1, $xoopsUser->getUnameFromId($userid));
        }
    }

    xoops_cp_footer();
}

/**
 * Type: Private
 * 
 * Removes/Deletes a user.
 */
function removeUser()
{
    global $xoopsDB, $adminURL;

    $sqluser = "SELECT * from " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_id =" . $_GET['user_id'] . "";
    $arruser = $xoopsDB->fetchArray($xoopsDB->queryF($sqluser));

    $arruser['user_name'] = (!empty($arruser['user_name'])) ? $arruser['user_name'] : $arruser['user_nick'];

    if ($arruser['activated'] == 0)
    {
        define("_SAVE", "Save");
        xoops_cp_header();
        evadminmenu(_ADM_EVENNEWS_ADMINMENU);
        echo "<form action='" . xoops_getenv('PHP_SELF') . "' method='post'>\n";
		echo "<fieldset><legend style='font-weight: bold; color: #900;'>Notice:</legend>";
        echo "<div align = \"cente\" style=\"padding: 8px;\"><b>User <b>" . $arruser['user_name'] . "</b> has already been unsubscribed for the mailing list.</b></div>";
        
        echo "<center><table cellpadding='2' cellspacing='1' class = \"outer\">\n";
        echo "<tr>
				<td class = \"head\">Do you wish to re-activate user <b>" . $arruser['user_name'] . "?</b> </td>
				<td class = \"even\"> <input type='radio' name='activate' value='1'> " . _YES . " &nbsp;
					<input type='radio' name='activate' value='0' checked> " . _NO . "
				</td>
			</tr>\n";
        echo "<input type='hidden' name='action' value='reactivate'>\n";
        echo "<input type='hidden' name='user_id' value='" . $arruser['user_id'] . "'>\n";
        echo "<tr>
				<td class = \"even\">&nbsp;</td>\n";
        echo "<td colspan = \"2\" class = \"even\"><input type='submit' name='Submit' value='" . _SAVE . "'></td></tr>\n";
        echo "</table></center><br />";
        echo "</fieldset>";
        echo "</form>\n";
        xoops_cp_footer();
        exit();
    } 
    // }
    $sql = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET activated ='0' WHERE user_id =" . $_GET['user_id'] . "";
    $result = $xoopsDB->queryF($sql);
    $error = "" . _ADM_EVENNEWS_DBERROR . ": <br /><br />" . $sql;

    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    }
    redirect_header(xoops_getenv('PHP_SELF'), 2, sprintf(_ADM_EVENNEWS_USERREMOVED, $arruser['user_name']));
}

function delUnconf()
{ 
    global $xoopsDB;

    $subscriber_id = (isset($_POST['subscriber_id'])) ? $_POST['subscriber_id']: $_GET['subscriber_id'];

    if ($subscriber_id != 0)
    {
        if (!isset($_POST['confirm']))
        {
            xoops_cp_header();
            echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _ADM_CONFDELETE . "</legend><br />"; 
            // echo "<h4>" . _ADM_CONFDELETE . "</h4>";
            xoops_confirm(array('action' => 'clean_unconf', 'subscriber_id' => $subscriber_id, 'confirm' => 1), 'user.php?action=clean_unconf', _ADM_EVENNEWS_WARNING);
            echo "<br /></fieldset>";
            xoops_cp_footer();
            exit();
        }
        else
        {
            $sql = "DELETE FROM " . $xoopsDB->prefix('enewsletter_members') . " WHERE subscriber_id='" . $subscriber_id . "'";
            $result = $xoopsDB->query($sql);
            deleteNewslettersBySubscriberId($subscriber_id);
            $error = "Error while deleting user Data: <br /><br />" . $sql;
            $message = "This member has been deleted";
        }
    }
    else
    {
        $sql = "DELETE FROM " . $xoopsDB->prefix('enewsletter_members') . " WHERE confirmed ='0' and activated ='0'";
        $result = $xoopsDB->queryF($sql);
        $list = $xoopsDB->getRowsNum($sql);
        $error = "Error while deleting unconfirmed user Data: <br /><br />" . $sql;
        $message = sprintf(_ADM_EVENNEWS_DELETED, $list);
    }

    if (!$result)
    {
        if (!$error)
        {
            $message = sprintf(_ADM_EVENNEWS_DELETED, "No");
        }
        else
        {
            trigger_error($error, E_USER_ERROR);
        }
    }
    redirect_header(xoops_getenv('PHP_SELF'), 1, $message);
}

function selectNewsletters(){
	global $xoopsDB;
	    $sqluser = "SELECT * FROM " . $xoopsDB->prefix('enewsletter_nletters') ;
        $arruser = $xoopsDB->queryF($sqluser);
        return $arruser;
}

function getSubscriberId($user_id,$email){
	global $xoopsDB;
	    $sqluser = "SELECT subscriber_id FROM " . $xoopsDB->prefix('enewsletter_members')." WHERE " ;
	    if (intval($user_id)>0){
	    		 $sqluser.="user_id=".$user_id;
	    }elseif($email!=""){
	    		 $sqluser.="user_email='".$email."'";
	    }	    
	   //echo $sqluser."<P>";
        $result = $xoopsDB->query($sqluser);
        //print_r($result);echo "<P>";
        list($susbcriber_id) = $xoopsDB->fetchRow($result);
        //echo "SD:".$susbcriber_id."<P>";
        return $susbcriber_id;
}


function getNewslettersBySubscriberId($subscriber_id){
	global $xoopsDB;
	
		$nletterid_arrs=array();
		if (isset($subscriber_id)) {
			$sql="SELECT ".$xoopsDB->prefix('enewsletter_nletters').".nletter_id FROM ".$xoopsDB->prefix('enewsletter_nletters')." LEFT JOIN ";
			$sql.=$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_nletters').".nletter_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
			$sql.=".subscriber_id=".$subscriber_id;	
			
			//echo "SQL:".$sql."<P>";
		    $result = $xoopsDB->queryF($sql);
			while ( $myrow = $xoopsDB->fetchArray($result) ) {
				$nletterid_arrs[] = $myrow['nletter_id'];
			}
		}
		return $nletterid_arrs;
}

function getNewslettersBySubscriberEmail($email=""){
	global $xoopsDB;
	
		$nletterid_arrs=array();
/*
			$sql="SELECT ".$xoopsDB->prefix('enewsletter_nletters').".* FROM ".$xoopsDB->prefix('enewsletter_nletters');
			$sql.=" LEFT JOIN ".$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_nletters').".nletter_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id ";
			$sql.=" LEFT JOIN ".$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_nletters').".nletter_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id ";			
			$sql.="WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link').".subscriber_id=".$email;	
*/
			//$sql="SELECT ".$xoopsDB->prefix('enewsletter_nletters').".* FROM ".$xoopsDB->prefix('enewsletter_nletters').", ".$xoopsDB->prefix('enewsletter_nletters')." INNER JOIN ";
			$sql="SELECT ".$xoopsDB->prefix('enewsletter_nletters').".* FROM ".$xoopsDB->prefix('enewsletter_nletters')." INNER JOIN ";			
			$sql.=$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_members').".subscriber_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletter_user_link').".subscriber_id  WHERE ".$xoopsDB->prefix('enewsletter_members');
			$sql.=".user_email='".$email."'";  
					
			//echo "SQL:".$sql."<P>";
		    //$result = $xoopsDB->queryF($sql);
		    $result = $xoopsDB->fetchArray($xoopsDB->queryF($sql));
			//while ( $myrow = $xoopsDB->fetchArray($result) ) {
			//	$nletterid_arrs[] = $myrow['nletter_id'];
			//}

		return $result;
}


function getNewslettersByNletterId($subscriber_id){
	global $xoopsDB;
	
        $sqluser = "SELECT * from " . $xoopsDB->prefix('enewsletter_members') . " WHERE subscriber_id =" . $_GET['subscriber_id'] . "";
        $arruser = $xoopsDB->fetchArray($xoopsDB->queryF($sqluser));
		//echo "SEW:".$sqluser."<P>";
		return $arruser;
}



function getSingleNewslettersByNletterId($nletter_id){
	global $xoopsDB;
	
        $sqluser = "SELECT * from " . $xoopsDB->prefix('enewsletter_nletters') . " WHERE nletter_id =" . $nletter_id . "";
        //echo "SEW:".$sqluser."<P>";
        $arruser = $xoopsDB->fetchArray($xoopsDB->queryF($sqluser));

		return $arruser;
}


function deleteNewslettersBySubscriberId($subscriber_id){
	global $xoopsDB;
		$nletterid_arrs=array();
			$sql="DELETE ".$xoopsDB->prefix('enewsletter_nletter_user_link')." FROM ".$xoopsDB->prefix('enewsletter_nletter_user_link')." LEFT JOIN ";
			$sql.=$xoopsDB->prefix('enewsletter_nletters')." on ".$xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletters').".nletter_id WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
			$sql.=".subscriber_id=".$subscriber_id;
			//echo $sql."<P>";
		    $result = $xoopsDB->queryF($sql);

		return $result;

}

function deleteNewslettersBySubscriberIdAndNletterID($subscriber_id,$nletter_id){
	global $xoopsDB;
		$nletterid_arrs=array();
			$sql="DELETE ".$xoopsDB->prefix('enewsletter_nletter_user_link')." FROM ".$xoopsDB->prefix('enewsletter_nletter_user_link')." LEFT JOIN ";
			$sql.=$xoopsDB->prefix('enewsletter_nletters')." on ".$xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id = ";
			$sql.= $xoopsDB->prefix('enewsletter_nletters').".nletter_id WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
			$sql.=".subscriber_id=".$subscriber_id;
			$sql.=" AND ".$xoopsDB->prefix('enewsletter_nletter_user_link').".nletter_id=".$nletter_id;

			//echo $sql."<P>";
		    $result = $xoopsDB->queryF($sql);

		return $result;

}

function deleteNewslettersByNletter_Id($nletter_id){
	global $xoopsDB;

			$sql="DELETE ".$xoopsDB->prefix('enewsletter_nletter_user_link')." FROM ".$xoopsDB->prefix('enewsletter_nletter_user_link')." ";
			$sql.="WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
			$sql.=".nletter_id=".$nletter_id;
			//echo $sql."<P>";
		    $result = $xoopsDB->queryF($sql);

		return $result;

}

function insertNlettersBySubscriberId($subscriber_id,$nletter_ids){
	global $xoopsDB;

	$sql = "INSERT INTO ".$xoopsDB->prefix('enewsletter_nletter_user_link');
	$sql .=" (nletter_linkid,nletter_id,subscriber_id) VALUES ";
	$cnt=0;
	foreach ($nletter_ids as $nletter_id) {
		$cnt++;
	    $sql .="(";
			$sql .="0, ".$nletter_id.", ".$subscriber_id;
	    $sql .=")";
	   // echo "CNT:".$cnt."-".count($nletter_ids)."<P>";
	    if ($cnt!=count($nletter_ids)){
	    	$sql .=", ";
	    }	    
	}

	//echo $sql."-<P>+";
	$result = $xoopsDB->queryF($sql);
	//echo "<P>+DONE<P>+";
	return $result;

}

function subscribersByNewsletterForm() {

        include XOOPS_ROOT_PATH . '/class/pagenav.php';

        global $xoopsDB, $adminURL;

        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $showuser = isset($_GET['showuser']) ? intval($_GET['showuser']) : 0;
		$showorder = isset($_GET['showorder']) ? intval($_GET['showorder']): 0;
        $showtype = isset($_GET['showtype']) ? intval($_GET['showtype']) : 0;
		$nletter_id = isset($_GET['nletter_id']) ? intval($_GET['nletter_id']) : 0;

        $showuser_data = array("", "AND confirmed = '1'", "WHERE activated = '0'");
        $order_data = array("ASC", "DESC");
		$listtype = array("user_name", "user_name", "user_nick", "user_email", "user_time");


		$query="SELECT ".$xoopsDB->prefix('enewsletter_members').".* FROM ".$xoopsDB->prefix('enewsletter_members')." INNER JOIN ";
		$query.=$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_members').".subscriber_id = ";
		$query.= $xoopsDB->prefix('enewsletter_nletter_user_link').".subscriber_id  WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
		$query.=".nletter_id=".$nletter_id;  
        //$query = "select * from " . $xoopsDB->prefix('enewsletter_members') . " ";
        $query .= "" . $showuser_data[$showuser] . " ";
        $query .= "ORDER BY " . $listtype[$showtype] . " " . $order_data[$showorder] . "";
		$result = $xoopsDB->query($query, 10, $start);
        $list = $xoopsDB->getRowsNum($result);

		$query="SELECT COUNT(*) FROM ".$xoopsDB->prefix('enewsletter_members')." INNER JOIN ";
		$query.=$xoopsDB->prefix('enewsletter_nletter_user_link')." on ".$xoopsDB->prefix('enewsletter_members').".subscriber_id = ";
		$query.= $xoopsDB->prefix('enewsletter_nletter_user_link').".subscriber_id  WHERE ".$xoopsDB->prefix('enewsletter_nletter_user_link');
		$query.=".nletter_id=".$nletter_id; 
        $query .= "" . $showuser_data[$showuser] . " ";
        $query .= "ORDER BY " . $listtype[$showtype] . " " . $order_data[$showorder] . "";
		//"SELECT COUNT(*) FROM " . $xoopsDB->prefix("enewsletter_members") . " "
        $result2 = $xoopsDB->query($query . " ");
        list($numrows) = $xoopsDB->fetchRow($result2);

        xoops_cp_header();

        evadminmenu(_ADM_EVENNEWS_ADMINMENU );

	$arruser=getSingleNewslettersByNletterId($nletter_id);
    $nletter_name = $arruser['nletter_name'];
    
    
        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _ADM_EVENNEWS_USERLIST ." : ".$nletter_name. "</legend>";
		//echo "<div style=\"padding: 8px;\"><b>Show: </b>";
        //echo showuser($showuser);
        //echo " By ";
        //echo showtype($showtype);

       // echo " in ";
        //echo showlist($showorder);
        //echo " order.</div><br />";
		//echo "</div>";
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
				<a href='user.php?action=add_user&amp;subscriber_id=" . $arr['subscriber_id'] . "&nletter_id=".$nletter_id."'>Edit </a>
				<a href='" . xoops_getenv('PHP_SELF') . "?action=unsubscribeFromNewsletter&amp;nletter_id=" . $nletter_id . "&amp;subscriber_id=" . $arr['subscriber_id'] . "'>Remove</a></td>";
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

function saveUser()
{
    global $xoopsDB, $xoopsUser;
    if (checkEmail($_POST['user_mail']) == FALSE)
    {
        redirect_header("" . xoops_getenv('PHP_SELF') . "?action=add_user", 2, "Email Address entered is Invalid");
    }
	//echo "savePID:".$_POST['subscriber_id']."<P>";
    $better_token = md5(uniqid(rand(), 1));
    if ($_POST['subscriber_id'])
    {
    	$subscriber_id=$_POST['subscriber_id'];
		$user_format = ($_POST['user_format'] == 1) ? 1 : 0;
		$activated = ($_POST['activated'] == 1) ? 1 : 0;
		$confirmed = ($_POST['confirmed'] == 1) ? 1 : 0;

		//UpdateMembers($_POST['user_name'],$_POST['user_nick'],$_POST['user_mail'],$confirmed,$activated',$user_format', user_lists = '0' WHERE subscriber_id =" . $_POST['subscriber_id']);
		$query = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET user_name = '" . $_POST['user_name'] . "', user_nick ='" . $_POST['user_nick'] . "', user_email ='" . $_POST['user_mail'] . "', confirmed='$confirmed', activated='$activated', user_html = '$user_format', user_lists = '0' WHERE subscriber_id =" . $_POST['subscriber_id'] . "";
        $error = "Could not update user information: <br /><br />";
        $error .= $query;
        $saveinfo = _ADM_EVENNEWS_USERUPDATED;
        $result = $xoopsDB->queryF($query);
        //echo $query."<P>";
		deleteNewslettersBySubscriberId($_POST['subscriber_id']);
		//echo "-_POST1-<P>";//break;
    }
    else
    {
        $date = time();
		$activated = ($_POST['confirmed'] == 1) ? 1 : 0;
		$user_format = ($_POST['user_format'] == 1) ? 1 : 0;

		$query = "INSERT INTO " . $xoopsDB->prefix('enewsletter_members') . " (subscriber_id,user_id, user_name, user_nick, user_email, user_host, user_conf, confirmed, activated, user_time, user_html, user_lists  ) ";
        $query .= "VALUES (0,0, '" . $_POST['user_name'] . "', '" . $_POST['user_nick'] . "', '" . $_POST['user_mail']."',
			'" . $_POST['user_host'] . "', '$better_token', '1', '$activated', '$date', '$user_format', '')";
        $error = "Could not create user information: <br /><br />";
        $error .= $query;
        $saveinfo = _ADM_EVENNEWS_USERADDED;
        $result = $xoopsDB->queryF($query);
    	$subscriber_id=$xoopsDB->getInsertId();
    }

    
    
    if (!$result)
    {
		//trigger_error($error, E_USER_ERROR);
		$saveinfo="ERROR: Unable to add subscriber.  Please note email addresses must be unique.";
    }else{
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
        	insertNlettersBySubscriberId($subscriber_id,$nletterids_arrs);
		}		    	
    	
    }
    //echo "LID:".$_POST['nletter_id']."<P>";
    //break;
    if (intval($_POST['nletter_id'])>0){

    	redirect_header("index.php?action=subscribers_newsletters&nletter_id=".$_POST['nletter_id'], 1, $saveinfo);
    }else{
    	redirect_header("user.php", 1, $saveinfo);
    }
}

function reactivate()
{
    global $xoopsDB, $xoopsUser;

	$sqluser = "SELECT * from " . $xoopsDB->prefix('enewsletter_members') . " WHERE user_id =" . $_POST['user_id'] . "";
    $arruser = $xoopsDB->fetchArray($xoopsDB->queryF($sqluser));

    $date = time();
	$query = "UPDATE " . $xoopsDB->prefix('enewsletter_members') . " SET activated='1', user_time = '$date' WHERE user_id =" . $_POST['user_id'] . "";
    $error = "Could not update user information: <br /><br />";
    $error .= $query;
    $saveinfo = _ADM_EVENNEWS_USERADDED;

    $result = $xoopsDB->queryF($query);
    if (!$result)
    {
        trigger_error($error, E_USER_ERROR);
    }
    redirect_header("" . xoops_getenv('PHP_SELF') . "?action=rem_user", 2, sprintf("User %s has been re-activated", $arruser['user_name']));
}

/**
 * End of User functions here
 */

/**
 * adminmenu()
 * 
 * @param string $header optional : You can gice the menu a nice header
 * @param string $extra optional : You can gice the menu a nice footer
 * @param array $menu required : This is an array of links. U can
 * @param int $scount required : This will difine the amount of cells long the menu will have.  
 * NB: using a value of 3 at the moment will break the menu where the cell colours will be off display.
 * @return 
 */

function evadminmenu($header = '', $extra = '', $menu = '', $scount = 4)
{
    global $xoopsConfig, $xoopsModule, $adminURL;

    if (empty($menu))
    {
        /**
         * You can change this part to suit your own module. Defining this here will save you form having to do this each time.
         */ 
        // _AM_WFS_ADMENU1 => "" . XOOPS_URL . "/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod=" . $xoopsModule -> getVar('mid') . "",
        // _ADM_EVENNEWS_SENDMESSAGE => "index.php?action=send", 
        //   _ADM_EVENNEWS_VIEWARCHIV => "index.php?action=view_archives",
        $menu = array(
        	_ADM_EVENNEWS_NEWSLETTERSETUP => "index.php?action=setup",  
            _ADM_EVENNEWS_NEWSLETTERS => "index.php?action=newsletters",       	 	
            _ADM_EVENNEWS_MODULECONFIG => "" . XOOPS_URL . "/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod=" . $xoopsModule->getVar('mid') . "",
            _ADM_EVENNEWS_DEFAULTPAGE => "index.php",
            _ADM_EVENNEWS_LISTSUBSCR => "user.php",
            _ADM_EVENNEWS_ADDUSER => "user.php?action=add_user",
            _ADM_EVENNEWS_IMPORTUSER => "user.php?action=import_users",
            _ADM_EVENNEWS_OPTIMDATAB => "index.php?action=optimize",
            );
    }
    /**
     * the amount of cells per menu row
     */
    $count = 0;
    /**
     * Set up the first class
     */
    $class = "even";
    /**
     * Sets up the width of each menu cell
     */
    $width = 100 / $scount;

    /**
     * Menu table begin
     */
    //echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . $header . "</legend><br />";
    echo "<table width = '100%' cellpadding= '2' cellspacing= '1' class='outer'><tr>";

    /**
     * Check to see if $menu is and array
     */
    if (is_array($menu))
    {
        foreach ($menu as $menutitle => $menulink)
        {
            $count++;
            echo "<td class='$class' align='center' valign='middle' width= $width%>";
            echo "<a href='" . $menulink . "'>" . $menutitle . "</a></td>";

            /**
             * Break menu cells to start a new row if $count > $scount
             */
            if ($count == $scount)
            {
                /**
                 * If $class is the same for the end and start cells, invert $class
                 */
                $class = ($class == 'odd') ? "odd" : "even";
                echo "</tr>";
                $count = 0;
            }
            else
            {
                $class = ($class == 'even') ? "odd" : "even";
            }
        }
        /**
         * checks to see if there are enough cell to fill menu row, if not add empty cells
         */
        if ($count >= 1)
        {
            $counter = 0;
            while ($counter < $scount - $count)
            {
                echo '<td class="' . $class . '">&nbsp;</td>';
                $class = ($class == 'even') ? 'odd' : 'even';
                $counter++;
            }
        }
        echo "</table><br />";
        //echo "</fieldset>";
    }
    if ($extra)
    {
        echo "<div><h4>$extra</h4></div>";
    }
}

function select_date()
{
    echo "<div>";
    echo "" . _ADM_DAYC . " <select name='autoday'>";
    $autoday = date('d');
    for ($xday = 1; $xday < 32; $xday++)
    {
        $sel = ($xday == $autoday) ? 'selected="selected"' : '';
        echo "<option value='$xday' $sel>$xday</option>";
    }
    echo "</select>&nbsp;";

    echo _ADM_MONTH . " <select name='automonth'>";
    $automonth = date('m');
    for ($xmonth = 1; $xmonth < 13; $xmonth++)
    {
        $sel = ($xmonth == $automonth) ? 'selected="selected"' : '';
        echo "<option value='$xmonth' $sel>$xmonth</option>";
    }
    echo "</select>&nbsp;";

    echo _ADM_YEAR . " <select name='autoyear'>";
    $autoyear = date('Y');
    $cyear = date('Y');
    for ($xyear = ($autoyear-1); $xyear < ($cyear + 7); $xyear++)
    {
        $sel = ($xyear == $autoyear) ? 'selected="selected"' : '';
        echo "<option value='$xyear' $sel>$xyear</option>";
    }
    echo "</select>";
    echo "</div>";
}
/**
 * Image defines from here
 */
$editimg = "<img src=" . XOOPS_URL . "/modules/" . DIR_NAME . "/images/icon/edit.gif ALT=''>";
$deleteimg = "<img src=" . XOOPS_URL . "/modules/" . DIR_NAME . "/images/icon/delete.gif ALT=''>";
$approve = "<img src=" . XOOPS_URL . "/modules/" . DIR_NAME . "/images/icon/approve.gif ALT=''>";
$viewimg = "<img src=" . XOOPS_URL . "/modules/" . DIR_NAME . "/images/icon/view.gif ALT=''>";

function showuser($showuser)
{
    global $xoopsDB, $xoopsConfig;
    $ret = "<select size='1' name='showuser' onchange='location.href=\"user.php?showuser=\"+this.options[this.selectedIndex].value'>";

    $usertype = array("All Users" => 0 , "Confirmed Users" => 1, "Un-Activated Users" => 2);
    $user = 0;
    foreach ($usertype as $usertypes => $names)
    {
        if ($showuser == $user)
        {
            $opt_selected = "selected='selected'";
        }
        else
        {
            $opt_selected = "";
        }
        $ret .= "<option value='" . $user . "' $opt_selected>" . $usertypes . "</option>";
        $user++;
    }
    $ret .= "</select>";
    return $ret;
}

function showtype($showtype)
{
    global $xoopsDB, $xoopsConfig;
    $ret = "<select size='1' name='showtype' onchange='location.href=\"user.php?showtype=\"+this.options[this.selectedIndex].value'>";

    $listtype = array("Name" => 0, "Name" => 1, "Nick Name" => 2, "Nick Email" => 3, "Date Subscribed" => 4);

    $lists = 0;
    foreach ($listtype as $listtypes => $name)
    {
        if ($showtype == $lists)
        {
            $opt_selected = "selected='selected'";
        }
        else
        {
            $opt_selected = "";
        }
        $ret .= "<option value='" . $name . "' $opt_selected>" . $listtypes . "</option>";
        $lists++;
    }
    $ret .= "</select>";
    return $ret;
}

function showlist($showorder)
{
    global $xoopsDB, $xoopsConfig;
    $ret = "<select size='1' name='showorder' onchange='location.href=\"user.php?showorder=\"+this.options[this.selectedIndex].value'>";

    $listing = array("Accending" => 0, "Descending" => 1);

    $orders = 0;
    foreach ($listing as $listings => $ord)
    {
        if ($showorder == $orders)
        {
            $opt_selected = "selected='selected'";
        }
        else
        {
            $opt_selected = "";
        }
        $ret .= "<option value='" . $ord . "' $opt_selected>" . $listings . "</option>";
        $orders++;
    }
    $ret .= "</select>";
    return $ret;
}

?>
