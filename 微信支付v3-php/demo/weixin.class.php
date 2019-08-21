<?php


// define('APPID',         "wx1b7559b818e3c23e");  //appid
// define('APPSECRET',     "a98b3b9e53c93c5899cb091ccd236b45");

include_once("../WxPayPubHelper/WxPayPubHelper.php");

class class_weixin
{
	var $appid = WxPayConf_pub::APPID;
	var $appsecret = WxPayConf_pub::APPSECRET;

    //构造函数，获取Access Token
	public function __construct($appid = NULL, $appsecret = NULL)
	{
        if($appid && $appsecret){
            $this->appid = $appid;
			$this->appsecret = $appsecret;
        }

		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
		$res = $this->http_request($url);
		$result = json_decode($res, true);
		// var_dump($result);
		$this->access_token = $result["access_token"];
		$this->expires_time = time();
	}

    //生成OAuth2的URL
	public function oauth2_authorize($redirect_url, $scope, $state = NULL)
    {
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        return $url;
	}
    //生成OAuth2的Access Token
	public function oauth2_access_token($code)
    {
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->http_request($url);
        return json_decode($res, true);
	}
	
	//获取用户基本信息
	public function get_user_info($openid)
    {
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
		$res = $this->http_request($url);
        return json_decode($res, true);
	}

    //https请求（支持GET和POST）
    protected function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
		if($errno = curl_errno($curl)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}";
		}
        curl_close($curl);
        return $output;
    }
}
