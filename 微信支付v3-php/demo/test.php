<?php
    // error_reporting(E_ERROR | E_PARSE); 
	// include_once("weixin.class.php");
	// //使用jsapi接口
	// $jsApi = new JsApi_pub();
    // // var_dump($jsApi);
	// //=========步骤1：网页授权获取用户openid============
	// //通过code获得openid
	// if (!isset($_GET['code']))
	// {
		// //触发微信返回code码
		// $url = $jsApi->createOauthUrlForCode('http://info.doucube.com/demo/wxpay2/demo/js_api_call.php');
        // // var_dump($url);
		// Header("Location: $url"); 
        // // var_dump($url);
	// }else
	// {
		// //获取code码，以获取openid
        
	    // $code = $_GET['code'];
		// $jsApi->setCode($code);
		// $openid = $jsApi->getOpenId();
        // // var_dump($_GET);
        // var_dump($openid);
	// }

    
    require_once('weixin.class.php');
$weixin = new class_weixin();
// var_dump($weixin);
if (!isset($_GET["code"])){
	$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$jumpurl = $weixin->oauth2_authorize($redirect_url, "snsapi_userinfo", "123");
	Header("Location: $jumpurl");
    exit();
}else{
	// var_dump($_GET);
	$access_token_oauth2 = $weixin->oauth2_access_token($_GET["code"]);
	var_dump($access_token_oauth2);
	$userinfo = $weixin->get_user_info_oauth2($access_token_oauth2['access_token'], $access_token_oauth2['openid']);
	// $userinfo = $weixin->get_user_info($access_token_oauth2['openid']);
	// var_dump($userinfo);
}
?>