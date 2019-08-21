<?php

require_once('weixin.class.php');
$weixin = new class_weixin();
var_dump($weixin);
$openid = "";
if (!isset($_GET["code"])){
	$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$jumpurl = $weixin->oauth2_authorize($redirect_url, "snsapi_base", "123");
	Header("Location: $jumpurl");
}else{
	$access_token = $weixin->oauth2_access_token($_GET["code"]);
	$openid = $access_token['openid'];
}
var_dump($openid);
$userinfo = $weixin->get_user_info($openid);
var_dump($userinfo);

?>
