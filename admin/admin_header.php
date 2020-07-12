<?php
include_once "../../../mainfile.php";
include_once '../../../include/cp_header.php';

include_once XOOPS_ROOT_PATH . "/class/xoopsmailer.php";

if ($xoopsUser)
{
    $xoopsModule = XoopsModule::getByDirname("enewsletter");
    if (!$xoopsUser->isAdmin($xoopsModule->mid()))
    {
        redirect_header(XOOPS_URL . "/", 3, _NOPERM);;
        exit();
    }
}
else
{
    redirect_header(XOOPS_URL . "/", 3, _NOPERM);
    exit();
}

define("DIR_NAME", $xoopsModule->dirname());
include_once XOOPS_ROOT_PATH . "/modules/" . DIR_NAME . "/include/functions.php" ;
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

$myts = &MyTextSanitizer::getInstance();

$adminURL = XOOPS_URL . '/modules/' . DIR_NAME . '/admin/index.php';
?>