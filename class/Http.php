<?php
/**
 * @author 流浪
 * @contact 434684601
 * @date 2023-02-17
 */
include_once './config.php';
class Http{

    public static function geturl($url){
        $headerArray =array("Content-type:application/json;","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }

    //获取重定向后的地址
    public static function getRedirectedUrl($url) {
        $ch = curl_init($url);
        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // 获取响应头
        curl_setopt($ch, CURLOPT_NOBODY, true); // 只获取头部信息，不下载响应体
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟随重定向
        // 执行请求
        $response = curl_exec($ch);
        // 检查是否有错误
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        // 获取最终的 URL
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        // 关闭 cURL 会话
        curl_close($ch);
        return $finalUrl;
    }

    public static function posturl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }

    public static function fail(){
        header('HTTP/1.2 404 Not Found');
        die;
    }
}
//限制IP每分钟访问频率
$redis = new Redis();
$redis->connect( $config['redis']['host'], $config['redis']['port']);
if($config['redis']['password']){
    $redis->auth($config['redis']['password']);
}
//key记录该ip的访问次数
$key=get_real_ip();
//限制次数
$limit = $config['iptime'];
$check = $redis->exists($key);
if($check){
    $redis->incr($key);
    $count = $redis->get($key);
    if($count > $config['iptime']){
        header('Content-Type: text/html;charset=utf-8');
        exit('<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; color: greenyellow; background-color: black;"><h3>'."当前IP[60]秒内请求次数已达到限制的[$config[iptime]]次,请耐心等待下一个[60]秒".'</h3></body>');
    }
}else{
    $redis->incr($key);
    //限制时间为60秒
    $redis->expire($key,60);
}
$count = $redis->get($key);
//echo '第 '.$count.' 次请求';
//获取客户端真实ip地址
function get_real_ip(){
    static $realip;
    if(isset($_SERVER)){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $realip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else if(isset($_SERVER['HTTP_CLIENT_IP'])){
            $realip=$_SERVER['HTTP_CLIENT_IP'];
        }else{
            $realip=$_SERVER['REMOTE_ADDR'];
        }
    }else{
        if(getenv('HTTP_X_FORWARDED_FOR')){
            $realip=getenv('HTTP_X_FORWARDED_FOR');
        }else if(getenv('HTTP_CLIENT_IP')){
            $realip=getenv('HTTP_CLIENT_IP');
        }else{
            $realip=getenv('REMOTE_ADDR');
        }
    }
    return $realip;
}

function check_wap() {
    if (isset($_SERVER['HTTP_VIA'])) return true;
    if (isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])) return true;
    if (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) return true;
    if (strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"VND.WAP.WML") > 0) {
        // Check whether the browser/gateway says it accepts WML.
        $br = "WML";
    } else {
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
        if(empty($browser)) return true;
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ','okhttp');

        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile=checkSubstrs($mobile_os_list,$browser) ||
            checkSubstrs($mobile_token_list,$browser);
        if($found_mobile)
            $br ="WML";
        else $br = "WWW";
    }
    if($br == "WML") {
        return true;
    } else {
        return false;
    }
}

function checkSubstrs($list,$str){
    $flag = false;
    for($i=0;$i<count($list);$i++){
        if(strpos($str,$list[$i]) > 0){
            $flag = true;
            break;
        }
    }
    return $flag;
}