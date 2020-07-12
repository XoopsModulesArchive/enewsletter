<?php
// $Id: search.inc.php,v 1.1 2004/07/19 18:32:50 ackbarr Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

function even_search($queryarray, $andor, $limit, $offset, $userid){
	global $xoopsDB;
	$sql = "SELECT e.user_id, e.mess_id, e.time_sent, e.message, e.subject, u.uname FROM ".$xoopsDB->prefix("enewsletter_messages")." e,".$xoopsDB->prefix("users")." u WHERE (e.user_id=u.uid) ";
	if ( $userid != 0 ) {
		$sql .= " AND e.user_id=".$userid." ";
	}
	// because count() returns 1 even if a supplied variable
	// is not an array, we must check if $querryarray is really an array
	if ( is_array($queryarray) && $count = count($queryarray) ) {
		$sql .= " AND ((message LIKE '%$queryarray[0]%' OR subject LIKE '%$queryarray[0]%')";
		for($i=1;$i<$count;$i++){
			$sql .= " $andor ";
			$sql .= "(message LIKE '%$queryarray[$i]%' OR subject LIKE '%$queryarray[$i]%')";
		}
		$sql .= ") ";
	}
	$sql .= "ORDER BY time_sent DESC";
	$result = $xoopsDB->query($sql,$limit,$offset);
	$ret = array();
	$i = 0;
 	while($myrow = $xoopsDB->fetchArray($result)){
 		$ret[$i]['image'] = "images/enewsletter.gif";
		$ret[$i]['link'] = "message.php?action=view_mess&mess_id=".$myrow['mess_id']."";
		$ret[$i]['title'] = $myrow['subject'];
		$ret[$i]['time'] = $myrow['time_sent'];
		$ret[$i]['uid'] = $myrow['user_id'];
		$i++;
	}
	return $ret;
}
?>