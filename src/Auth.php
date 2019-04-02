<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2017/11/13
 * Time: 23:10
 */

namespace Pywechat;

class Auth extends WeChat {

    //生成code获取的url
    public function codeUrl($redirectUri,$scope="snsapi_base",$state="STATE")
    {
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?";

        $data=array(
            "appid"         =>  $this->appId,
            "redirect_uri"  =>  $redirectUri,
            "response_type" =>  "code",
            "scope"         =>  $scope,
            "state"         =>  $state,
        );

        $url.=http_build_query($data)."#wechat_redirect";

        return $url;
    }

    //通过code获取snsapi_base的基本信息
    public function getBaseInfo($code,$returnJson=false)
    {
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?";

        $data=array(
            "appid"     =>  $this->appId,
            "secret"    =>  $this->appSecret,
            "code"      =>  $code,
            "grant_type"=>  "authorization_code"
        );

        $url.=http_build_query($data);
        $info=$this->http_request($url);

        $ok=array_key_exists("errcode",json_decode($info,true));

        return $ok?false:json_decode($info,$returnJson);
    }

    //获取snsapi_userinfo用户详细信息
    public function getUserInfo($accessToken,$openid,$returnJson=false)
    {
        $url="https://api.weixin.qq.com/sns/userinfo?";

        $data=array(
            "access_token"  =>  $accessToken,
            "openid"        =>  $openid,
            "lang"          =>  "zh_CN"
        );

        $url.=http_build_query($data);
        $res=$this->http_request($url);

        $ok=array_key_exists("errcode",json_decode($res,true));

        return $ok?false:json_decode($res,$returnJson);
    }

    //刷新access_token  7200s
    public function refreshToken($refreshToken,$returnJson=false)
    {
        $url="https://api.weixin.qq.com/sns/oauth2/refresh_token?";

        $data=array(
            "appid"         =>  $this->appId,
            "grant_type"    =>  "refresh_token",
            "refresh_token" =>  $refreshToken
        );

        $url.=http_build_query($data);
        $res=$this->http_request($url);

        $ok=array_key_exists("errcode",json_decode($res,true));

        return $ok?false:json_decode($res,$returnJson);
    }

    //验证access_token是否有效
    public function checkToken($accessToken,$openid)
    {
        $url="https://api.weixin.qq.com/sns/auth?access_token=".$accessToken."&openid=".$openid;

        $res=$this->http_request($url);

        return json_decode($res)->errcode==0?true:false;
    }




}
