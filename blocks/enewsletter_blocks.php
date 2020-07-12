<?php

function b_enewsletter_subscr($options)
{
	global $xoopsModule, $xoopsConfig;
    	$db =& Database::getInstance();
    	$myts =& MyTextSanitizer::getInstance();
    	$block = array();
	$block['lang_tooltip1']=_EN_BTOOLTIP1;
	$block['lang_tooltip2']=_EN_BTOOLTIP2;
	$block['subscr_url']= XOOPS_URL. '/modules/enewsletter/index.php?action=subscribe';
	$block['unsubscr_url']= XOOPS_URL. '/modules/enewsletter/index.php?action=unsubscribe';
	$block['news_images']=sprintf('%s/modules/enewsletter/language/%s/', XOOPS_URL,$xoopsConfig['language']);
	
	$query="SELECT count(user_id) as number FROM ".$db->prefix('enewsletter_members')." WHERE confirmed='1'";
    	if (!$result = $db->query($query)) {
	        return false;
    	}
    	$arr = $db->fetchArray($result);
    	$block['pepole_subscribed']=$myts->makeTboxData4Show(sprintf(_EN_SUBSCRIBED_PEOPLE,$arr['number']));
	return $block;	
}
?>