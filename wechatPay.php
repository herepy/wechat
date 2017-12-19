<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2017/12/18
 * Time: 11:20
 */

require_once "./wechat.php";

class WechatPay extends WeChat{
    //商户号
    protected $mchId;
    //商户密钥
    protected $key;

    public function __construct($appId, $appSecret,$mchId,$key)
    {
        $this->appId=$appId;
        $this->appSecret=$appSecret;
        $this->mchId=$mchId;
        $this->key=$key;
    }

    //生成支付签名
    protected function paySign($parameter,$sign_type="MD5"){
        //字典排序
        ksort($parameter);
        //拼接键值对
        $str=$this->arrToUrl($parameter);
        $type="md5";
        if($sign_type!="MD5"){
            $type="sha256";
        }
        return strtoupper(hash($type,$str."&key=".$this->key));
    }

    //统一下单
    public function unifiedOrder($body,$out_trade_no,$total_fee,$notify_url,$trade_type,$openid=null){
        $url="https://api.mch.weixin.qq.com/pay/unifiedorder";
        $parameter=array();
        $parameter["appid"]=$this->appId;
        $parameter["mch_id"]=$this->mchId;
        $parameter["nonce_str"]=$this->randStr(32);
        $parameter["sign_type"]="MD5";
        $parameter["body"]=$body;
        $parameter["out_trade_no"]=$out_trade_no;
        $parameter["total_fee"]=$total_fee;
        $parameter["notify_url"]=$notify_url;
        $parameter["trade_type"]=$trade_type;

        if($openid!==null){
            $parameter["openid"]=$openid;
        }
        //签名
        $sign=$this->paySign($parameter);
        $parameter["sign"]=$sign;

        $xml=$this->arrToXml($parameter);

        $result=$this->http_request($url,$xml,true);

        if(!$result){
            return false;
        }
        $data=$this->xmlToArr($result);

        return $data;
    }

    //订单查询
    public function orderQuery($out_trade_no,$transaction_id=null){
        $url="https://api.mch.weixin.qq.com/pay/orderquery";
        $arr=array();
        $arr["appid"]=$this->appId;
        $arr["mch_id"]=$this->mchId;
        if($transaction_id!==null){  //有微信订单号优先使用
            $arr["transaction_id"]=$transaction_id;
        }else{   //使用商户订单号
            $arr["out_trade_no"]=$out_trade_no;
        }
        $arr["nonce_str"]=$this->randStr(32);
        $arr["sign_type"]="MD5";

        $sign=$this->paySign($arr);
        $arr["sign"]=$sign;

        $xml=$this->arrToXml($arr);
        $result=$this->http_request($url,$xml,true);
        if(!$result){
            return false;
        }
        $data=$this->xmlToArr($result);
        if($data["return_code"]=="FAIL" || $data["FAIL"]){
            return false;
        }

        return $data;
    }

    //关闭订单   新订单至少五分钟后才能关闭
    public function closeOrder($out_trade_no){
        $url="https://api.mch.weixin.qq.com/pay/closeorder";
        $arr=array();
        $arr["appid"]=$this->appId;
        $arr["mch_id"]=$this->mchId;
        $arr["out_trade_no"]=$out_trade_no;
        $arr["nonce_str"]=$this->randStr(32);
        $arr["sign_type"]="MD5";

        $sign=$this->paySign($arr);
        $arr["sign"]=$sign;

        $xml=$this->arrToXml($arr);
        $result=$this->http_request($url,$xml,true);

        if ($result==false){
            return false;
        }
        $data=$this->xmlToArr($result);
        if($data["return_code"]=="FAIL"){
            return false;
        }

        return $data;
    }

    //申请退款
    public function refund($certPath,$keyPath,$out_trade_no,$out_refund_no,$total_fee,$refund_fee,$refund_desc=null){
        $url="https://api.mch.weixin.qq.com/secapi/pay/refund";
        if(!file_exists($certPath) || !file_exists($keyPath)){
            return false;
        }
        $arr=array();
        $arr["appid"]=$this->appId;
        $arr["mch_id"]=$this->mchId;
        $arr["nonce_str"]=$this->randStr(32);
        $arr["sign_type"]="MD5";
        $arr["out_trade_no"]=$out_trade_no;
        $arr["out_refund_no"]=$out_refund_no;
        $arr["total_fee"]=$total_fee;
        $arr["refund_fee"]=$refund_fee;
        if($refund_desc){
            $arr["refund_desc"]=$refund_desc;
        }

        $sign=$this->paySign($arr);
        $arr["sign"]=$sign;

        $xml=$this->arrToXml($arr);
        $result=$this->http_request($url,$xml,true,$certPath,$keyPath);
        if($result==false){
            return false;
        }

        $data=$this->xmlToArr($result);
        if($data["return_code"]=="FAIL"){
            return false;
        }

        return $data;
    }

    //查询退款
    public function refundQuery($out_refund_no,$offset=null){
        $url="https://api.mch.weixin.qq.com/pay/refundquery";
        $arr=array();
        $arr["appid"]=$this->appId;
        $arr["mch_id"]=$this->mchId;
        $arr["nonce_str"]=$this->randStr(32);
        $arr["sign_type"]="MD5";
        $arr["out_refund_no"]=$out_refund_no;
        if($offset){
            $arr["offset"]=$offset;
        }

        $sign=$this->paySign($arr);
        $arr["sign"]=$sign;
        $xml=$this->arrToXml($arr);

        $result=$this->http_request($url,$xml,true);
        if($result==false){
            return false;
        }
        $data=$this->xmlToArr($result);
        if($data["return_code"]=="FAIL"){
            return false;
        }
        return $data;
    }

    //获取支付结果通知 并 验证
    public function getPayResult(){
        $result=file_get_contents("php://input");
        $data=$this->xmlToArr($result);
        if($data["return_code"]=="FAIL"){
            return false;
        }
        $sign=$data["sign"];
        $sign_type=$data["sign_type"];
        //验证签名
        $sign_tmp=$this->paySign($data,$sign_type);
        if($sign_tmp!=$sign){
            return false;
        }

        return $data;
    }

    //生成微信公众号网页支付config
    public function webPayConfig($body,$out_trade_no,$total_fee,$notify_url,$openid){
        $trade_type="JSAPI";
        //请求统一下单接口生成预支付标识  7200s
        $result=$this->unifiedOrder($body,$out_trade_no,$total_fee,$notify_url,$trade_type,$openid);
        if($result===false){
            return false;
        }
        if($result["return_code"]=="FAIL" || $result["result_code"]=="FAIL"){
            return false;
        }
        $prepay_id=$result["prepay_id"];

        $arr=array();
        $arr["appId"]=$this->appId;
        $arr["timestamp"]=time();
        $arr["nonceStr"]=$this->randStr(32);
        $arr["package"]="prepay_id=".$prepay_id;
        $arr["signType"]="MD5";

        //签名
        $sign=$this->paySign($arr);
        $arr["paySign"]=$sign;

        return $arr;
    }

    //生成app支付config
    public function appPayConfig($body,$out_trade_no,$total_fee,$notify_url){
        $trade_type="APP";
        //请求统一下单接口生成预支付标识  7200s
        $result=$this->unifiedOrder($body,$out_trade_no,$total_fee,$notify_url,$trade_type);
        if($result===false){
            return false;
        }
        if($result["return_code"]=="FAIL" || $result["result_code"]=="FAIL"){
            return false;
        }
        $prepay_id=$result["prepay_id"];

        $arr=array();
        $arr["appid"]=$this->appId;
        $arr["partnerid"]=$this->mchId;
        $arr["prepayid"]=$prepay_id;
        $arr["package"]="Sign=WXPay";
        $arr["noncestr"]=$this->randStr(32);
        $arr["timestamp"]=time();

        //签名
        $sign=$this->paySign($arr);
        $arr["sign"]=$sign;

        return $arr;
    }



}