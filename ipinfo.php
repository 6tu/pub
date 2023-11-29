<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>It work!</title>
</head>
    
<body>
<?php
echo '<center><br>hello world!<br>';
echo 'LAN IP: '. $_SERVER['SERVER_ADDR'].'<br>';
$url = 'http://ipip.info/';
$res_array =url_get_contents($url);
//$res_array = get_url_contents($url);
echo 'WAN IP: '. $res_array['body'].'<br>';
echo 'Your IP: '. $_SERVER['REMOTE_ADDR'].'</center>';

#/-------------- 函数部分 --------------/#

function url_get_contents($url){
    # 该链接的来源处/引用处
    $url_array = parse_url($url);
    $refer = $url_array['scheme'] . '://' . $url_array['host'] . '/';
    # 浏览器语言
    if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
        $lang = 'zh-CN,zh; q=0.9';
    }else{
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
    # 浏览器标识
    if(empty($_SERVER['HTTP_USER_AGENT'])){
        $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.64 Safari/537.36';
    }else{
        $useragent = $_SERVER['HTTP_USER_AGENT'];
    }
    $cookie_jar = __DIR__ . '/tmp/' . md5($url_array['host']) . '.cookie';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL              , $url);
    curl_setopt($ch, CURLOPT_USERAGENT        , $useragent);
    curl_setopt($ch, CURLOPT_REFERER          , $refer);
    curl_setopt($ch, CURLOPT_HTTPHEADER       , ["Accept-Language: $lang"]);
    curl_setopt($ch, CURLOPT_HEADER           , true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER   , true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS , false);    #不验证证书状态
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER   , false);    #禁止验证对等证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST   , false);    #不检查证书公用名的存在和与主机名匹配
    curl_setopt($ch, CURLOPT_SSL_ENABLE_ALPN  , false);    #禁用用于协商到http2的ALPN,
    curl_setopt($ch, CURLOPT_SSL_ENABLE_NPN   , false);    #禁用用于协商到http2的NPN
    curl_setopt($ch, CURLOPT_TIMEOUT          , 60);       #响应超时
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT   , 10);       #链接超时

    $result = curl_exec($ch);
    if(curl_exec($ch) === false){
        $curl_errno = curl_errno($ch); # 返回最后一次的错误代码
        $curl_error = curl_error($ch); # 返回当前会话最后一次错误的字符串
        $result = '[' . trim($curl_errno) . '] ' . "\r\n\r\n" . trim($curl_error);
    }else{
        $curl_errno = '';
        $curl_error = '';
    }
    curl_close($ch);

    $res_array = explode("\r\n\r\n", $result, 2);
    $res_array = array(
        'header' => $res_array[0],
        'body' => $res_array[1],
        'error' => '[' . $curl_errno . '] ' . $curl_error,
    );
    return $res_array;
}

# 获取url内容,返回字符串
function get_url_contents($url){
    $method = 'GET';
    $stringData = '';
    $method_post = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded;charset=UTF-8',
            'content' => $stringData   # 需要获取的内容
            ),
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
            )
        );

    $method_get = array(
        'http' => array(
            'method' => 'GET',
            'header' => "Accept-language: en\r\n" .
                        "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.82 Safari/537.36\r\n"
            ),
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
            )
        );

    if($method === 'GET') $options = $method_get;
    else $options = $options_post;
    $context = stream_context_create($options);

    set_error_handler(function($err_severity, $err_msg, $err_file, $err_line, array$err_context){
        throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
    }, E_WARNING);
    try{
        $body = file_get_contents($url, false, $context);
        $header = '';
        foreach($http_response_header as $value){
            $header .= $value . "\r\n";
        }
        $header = trim($header);
    }
    catch(Exception $e){
        $error = $e -> getMessage();
    }
    # restore the previous error handler 还原以前的错误处理程序
    restore_error_handler();
    if(empty($error)){
        $error = '';
        $error_str = "";
    }else $error_str = "failed to open stream: HTTP request failed";
    $res_array = array(
        'header' => $header,
        'body'   => $body,
        'error'  => '[' . $error . '] ' . $error_str,
    );
    return $res_array;
}


