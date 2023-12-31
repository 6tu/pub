<?php
set_time_limit(0);
require_once '/home/libs/config.php';

$libs_path = LIBS;
$article_path = ARTICLE;
$cache_path = CACHE;
$webpage_path = WEBPAGE;
$tmp_path = TMP;
$elements_tag = LIBS . '/article-elements-tag.txt';
// $dir = getcwd();#当前工作目录
// chdir('/path/to/new_directory');

$url = 'https://gmachine1729.livejournal.com/140968.html';
$url = '/data/article/livejournal/140968.html';

# ================ 提交URL ================ #
header("Content-type: text/html; charset=utf-8");
$cli = preg_match("/cli/i", php_sapi_name());
if($cli){
    # print_r($argv);
    if(!empty($argv[1])){
        $url = $argv[1];
    }else{
        fwrite(STDOUT, "\n\n Enter url or file: ");
        $url = trim(fgets(STDIN));
    }
}else{
    if(empty($_GET['url']) and !$cli) die('<br><br><center>请使用参数?url=https://...</center>' . form_html());
    if(isset($_GET['url'])) $url = $_GET['url'];
}
if(empty(trim($url))) die("\n 输入为空，请输入本地文件名或者网址\n\n");
else print_r("\n $url\n\n");

# ================ 设定文件名 ================ #
$time = date('YmdHis');
$fn_key = str_pad(crc32($url), 10, '0'); # CRC32输出长度: 8-9-10位
$url_array = parse_url($url);
if(empty($url_array['host'])){
    $domain_key = 'localhost';
}else{
    $host = $url_array['host'];
    $scheme = $url_array['scheme'];
    $domain_key = get_domain_key($host);
}
$url_array = pathinfo($url);
if(empty($url_array['basename'])){
    $fn = $time.'-'.$fn_key . '.html';
    $cache_fn = $domain_key .'_'. $fn_key . '.bak';
}else if(empty($url_array['extension'])){
    $fn = $url_array['basename'] . '.html';
    $cache_fn = $domain_key .'_'. $url_array['basename'] . '.bak';
}else{
    $fn = $url_array['basename'];
    $cache_fn = $domain_key .'_'. $url_array['filename'] . '.bak';
}
if(!is_dir(ARTICLE .'/'. $domain_key)) mkdir(ARTICLE .'/'. $domain_key);
$article_path = ARTICLE .'/'. $domain_key .'/'. $fn;
$cache_path = CACHE .'/'. $cache_fn;

# ================ 获取文件内容 ================ #
if($domain_key === 'localhost'){
    $header = '';
    if(!file_exists($url)) die("\n $url 没找到\n");
    $str = file_get_contents($url);
    # 这里有必要获取 url
    if(strpos($str, 'rel="canonical') !== false){
        $match = preg_match_all("'<link\srel=\"canonical[^>]*?>'si", $str, $matches);
        $canonical = $matches ? $matches[0][0] : '';
        $url = explode('href="', $canonical, 2)[1];
        $url = explode('"', $url, 2)[0];
    }
}
if(file_exists($article_path)){
    if(Server_OS() === 'windows') $article_path = str_replace('/', '\\', $article_path);
    if($cli) print_r(" start $article_path \n\n");
    else print_r("\n<br> 相关文件 <a href='$article_path'>$article_path</a>");
    exit;
}else if(file_exists($cache_path)){
    $str = file_get_contents($cache_path);
}else{
    $res_array = get_url_contents($url);
    $header = $res_array['header'];
    $str = $res_array['body'];
    file_put_contents($cache_path, $str);
}

# ================ 修改head 内容 ================ #
$str = html_pretreat($str);
$str = preg_replace('/<\/head>/i', '</head>', $str);
if(strpos($str, '</head>') === false){
    die("文件没有找到 </head>\n\n");
}
$head_array = explode('</head>', $str, 2);
$charset = get_charset($head_array[0]);
if($charset !== 'utf-8' and !empty($charset)){
    $str = mb_convert_encoding($str, 'utf-8', $charset);
}
$title = get_title($head_array[0], $url);
$head = add_head($title, $url);

# ================ 修改body 内容 ================ #
$str = $head_array[1];
$article_titile = preg_match("/<h1[^>]*?>.*?<\/h1>/is", $str, $temp) ? strtolower($temp[0]): "";
print_r(" $article_titile\n\n");

# 常用网站建立标签文件
$tags = file_get_contents($elements_tag);
if(strpos($tags, $host) !== false){
    $info_array = explode("=", $tags);
    $tag = explode($host, $tags, 2)[1];
    $tag = explode("\n", $tag, 2)[0];
    $tag_array = explode(",", $tag);

    $main_array = explode("=", $tag_array[1]);
    if(trim($main_array[0]) === 'class'){
        $mainclass = str_replace('"', '', trim($main_array[1]));
        $article_main = getElementByClassname($str, $mainclass);
        $article_main = mb_convert_encoding($article_main, 'UTF-8', 'HTML-ENTITIES');
    }
    if(trim($main_array[0]) === 'id'){
        $mainid = str_replace('"', '', trim($main_array[1]));
        $article_main = getElementByIdname($str, $mainid);
        $article_main = mb_convert_encoding($article_main, 'UTF-8', 'HTML-ENTITIES');
    }
    if(trim($main_array[0]) === 'custom'){
        $main_custom = str_replace('"', '', trim($main_array[1]));
        $article_main = getElementByCustomname($str, $main_custom);
    }

    if(!empty(trim($tag_array[2]))){
        $info_array = explode("=", $tag_array[2]);
        if(trim($info_array[0]) === 'class'){
            $infoclass = str_replace('"', '', trim($info_array[1]));
            $article_info = getElementByClassname($str, $infoclass);
            $article_info = mb_convert_encoding($article_info, 'UTF-8', 'HTML-ENTITIES');
        }
        if(trim($info_array[0]) === 'id'){
            $infoid = str_replace('"', '', trim($info_array[1]));
            $article_info = getElementByIdname($str, $infoid);
            $article_info = mb_convert_encoding($article_info, 'UTF-8', 'HTML-ENTITIES');
        }
        if(trim($info_array[0]) === 'custom'){
            $infocustom = str_replace('"', '', trim($info_array[1]));
            $article_info = getElementByCustomname($str, $infocustom);
        }
    }
    if(!empty(trim($tag_array[3]))){
        $ext_array = explode("=", $tag_array[3]);
        if(trim($ext_array[0]) === 'class'){
            $extclass = str_replace('"', '', trim($ext_array[1]));
            $article_ext = getElementByClassname($str, $extclass);
            $article_ext = mb_convert_encoding($article_ext, 'UTF-8', 'HTML-ENTITIES');
        }
        if(trim($ext_array[0]) === 'id'){
            $extid = str_replace('"', '', trim($ext_array[1]));
            $article_ext = getElementByIdname($str, $extid);
            $article_ext = mb_convert_encoding($article_ext, 'UTF-8', 'HTML-ENTITIES');
        }
        if(trim($ext_array[0]) === 'custom'){
            $extcustom = str_replace('"', '', trim($ext_array[1]));
            $article_ext = getElementByCustomname($str, $extcustom);
        }
    }
}
if(empty($article_info)) $article_info = '';
if(empty($article_ext)) $article_ext = '';
$str = $article_info .$article_main . $article_ext;




# pre 标签语法加亮风格
if(strpos($str, '<pre') !== false){
    $pre_array = explode("<pre", $str);
    $str = '';
    foreach($pre_array as $value){
        if(strpos($value, '</pre>') !== false){
            $array = explode('</pre>', $value, 2);
            $pre = "\n<pre" . $array[0] . '</pre>';
            $nopre = nonewline($array[1]);
            $nopre = imglink($nopre);
            $str .= $pre . $nopre;
        }else{
            $value = nonewline($value);
            $value = imglink($value);
            $str .= $value;
        }
    }
}else{
    $str = nonewline($str);
    $str = imglink($str);
}

$str = modify_code($str);
$str = del_script($str);
$str = $head .'<body>'.$article_titile. $str ."<hr>\n$url<br><br></body></html>";

$str = beautify_html($str);

if(strpos($str, '</pre>') !== false) $str .= "\n\n". add_style();

file_put_contents($article_path, $str);

if(Server_OS() === 'windows') $article_path = str_replace('/', '\\', $article_path);
if($cli) print_r(" start $article_path\n\n");
else print_r("\n<br> 相关文件 <a href='$article_path'>$article_path</a>");
















# ================ 函数部分 ================ #
function form_html(){
    header("Content-type: text/html; charset=utf-8");
    $html = "<html><head><title>Get CSDN blog posts</title></head>\r\n<body><center><br>\r\n<form action=\"" . php_self() . "\"method='GET'/>\r\n";
    $html .= '<b>CSDN blog\'s URL:<input type="text" name="url" size=50 value="https://blog.csdn.net"/>' . "\r\n" . '<input type="submit" value="Send"/>';
    $html .= "</b>\r\n</form>\r\n<br>\r\n";
    echo $html;
}

# 获取当前PHP文件名
function php_self(){
    $php_self = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1);
    return $php_self;
}

# html 预处理
function html_pretreat($str){
    $str = preg_replace("/\r\n|\r|\n/", "\n", $str);
    $str = preg_replace("/<\s+/is", "<", $str);
    $str = preg_replace("/\s+>/is", ">", $str);
    $str = preg_replace("/<pre[^>]*?>/i", "\n<pre class=\"brush:php;toolbar:false\">\n", $str);
    $str = preg_replace("/<\/pre[^>]*?>/i", "\n</pre>\n", $str);
    return $str;
}

# 换行
function nonewline($str){
    $line_array = explode("\n", $str);
    $line = '';
    foreach($line_array as $value){
        if(!empty(trim($value))){
            $value = preg_replace("/\s(?=\s)/is", "\\1", $value); # 去空格
            $value = str_replace('  ', ' ', $value);
            $value = str_replace(array(' "', '=" '), array('"', '="'), $value);
            $value = str_replace(array('= ', ' ='), array('=', '='), $value);
            $line .= trim($value) . ' ';
        }
    }
    return $line;
}

# 图片
function imglink($str){
    $gt_array = explode('>', $str);
    $gt = '';
    foreach($gt_array as $value){
        if(empty(trim($value))) continue;
        $value = $value . '>';
        preg_match_all('/<img.*?src=/i', $value, $img_array);
        // print_r($img_array[0]);
        if(!empty($img_array[0])){
            $value = str_replace($img_array[0][0], '<img src=', $value);
            $value = str_replace("src='", 'src="', $value);
            $src_array = explode('<img src="', $value, 2);
            $imgsrc = str_replace("' ", '" ', $src_array[1]);
            $imgsrc = explode('" ', $imgsrc, 2)[0];
            $value = $src_array[0] . '<img src="' . $imgsrc . '">';
        }
        $gt .= $value;
    }
    return $gt;
}

# HTML 格式化，后处理
function beautify_html($html){
    require_once LIBS . '/beautify-html.php';
    $beautify_config = array(
        'indent_inner_html' => false,
        'indent_char' => " ",
        'indent_size' => 2,
        'wrap_line_length' => 32786,
        'unformatted' => ['code', 'pre', 'span'],
        'preserve_newlines' => false,
        'max_preserve_newlines' => 32786,
        'indent_scripts' => 'normal', // keep|separate|normal
    );
    $beautify = new Beautify_Html($beautify_config);
    $html = $beautify -> beautify($html);
    return $html;
}

# 包含 charset 的 meta
function get_meta_charset($html){
    // $charset = preg_match("/<meta.+?charset=[^\w]?([-\w]+)/i", $html, $temp) ? strtolower($temp[1]):"";
    preg_match_all('/<meta.*?>/i', $html, $matches);
    $meta = '';
    foreach($matches[0] as $value){
        $value = strtolower(trim($value));
        # 多个空格转为一个空格
        $value = preg_replace("/\s(?=\s)/", "\\1", $value);
        // $value = preg_replace("/ {2,}/", "", $value); # {2,}前面的空格不能少
        $value = preg_replace("/'/", '"', $value);
        $value = str_replace(array(' "', '=" '), array('"', '="'), $value);
        $value = str_replace(array('= ', ' ='), array('=', '='), $value);
        if(strpos($value, 'charset') !== false) $meta .= $value . "\n";
    }
    return $meta;
}

// if($charset !== 'utf-8' and !empty($charset)){
//     $html = mb_convert_encoding($html, 'utf-8', $charset);
// }

# 提取charset
function get_charset($html){
    $meta = get_meta_charset($html);
    $charset = preg_match("/charset=[^\w]?([-\w]+)/i", $meta, $temp) ? strtolower($temp[1]): "";
    // if(empty($charset)){
    //     $header = print_r($html_array['header'], true);
    //     $charset = preg_match("/charset=[^\w]?([-\w]+)/i", $header, $temp) ? strtolower($temp[1]): "";
    // }
    return $charset;
}

# 提取 title
function get_title($str){
    preg_match('/<title>(.*?)<\/title>/iUs', $str, $title);
    $title = $title ? $title[1] : '';
    return trim($title);
}

# 重写 head
function add_head($title, $url){
    $head = '
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <link rel="canonical" href="' . $url . '" />
  <title>' . $title . '</title>
  <style>
    body,html{
        width: 90%;
        height:100%;
        margin: auto;
        line-height: 1.8;
        background: #;
        color: #000
    }
    div.article-bar-top{
        color:#999aaa;
        width:88%;
        display:-webkit-box;
        display:-ms-flexbox;
        display:flex
    }
    .bar-content{
        display:-webkit-box;
        display:-ms-flexbox;
        display:flex;-ms-flex-wrap:wrap;
        flex-wrap:wrap;-webkit-box-align:center;-ms-flex-align:center;
        align-items:center;
        padding-left:12px
    }
    .article-type-img{
        width:36px;
        height:32px;
        line-height:32px
    }
  </style>
</head>';
    return $head;
}

# pre 标签语法加亮风格
function add_style(){
    $style = '
<script type="text/javascript" src="http://localhost/reader/libs/styles/SyntaxHighlighter/js/shCore.js"></script>
<script type="text/javascript" src="http://localhost/reader/libs/styles/SyntaxHighlighter/js/shBrushPhp.js"></script>
<link type="text/css" rel="stylesheet" href="http://localhost/reader/libs/styles/SyntaxHighlighter/css/shCore.css" />
<link type="text/css" rel="stylesheet" href="http://localhost/reader/libs/styles/SyntaxHighlighter/css/shThemeLiuQing.css" />
<style>
  .syntaxhighlighter{
      width: 740;
      padding-top:40px;padding-bottom:20px;
      border: 1px solid #333;
      background: url("http://localhost/reader/libs/styles/SyntaxHighlighter/top_bg.svg");
      background-size: 43px;
      background-repeat: no-repeat;
      margin-bottom: -7px;
      border-radius: 15px;
      background-position: 16px 12px;
      padding-left: 10px;
      font-size: 0.8em !important;
      }
      .gutter{
      display: none;
      }
</style>
<script type="text/javascript">
  SyntaxHighlighter.all();
</script>
    ';
    return $style;
}

# 用curl 获取 url内容，返回数组
function url_get_contents($url){
    # 该链接的来源处/引用处
    $url_array = parse_url($url);
    $refer = $url_array['scheme'] . '://' . $url_array['host'] . '/';
    $pos = strrpos($url, '/', 0);
    $refer = substr($url, 0, $pos + 1);
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
    $cookie_jar = TMP . '/tmp/' . md5($url_array['host']) . '.cookie';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL , $url);
    curl_setopt($ch, CURLOPT_USERAGENT , $useragent);
    curl_setopt($ch, CURLOPT_REFERER , $refer);
    curl_setopt($ch, CURLOPT_HTTPHEADER , ["Accept-Language: $lang"]);
    curl_setopt($ch, CURLOPT_HEADER , true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS , false); #不验证证书状态
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false); #禁止验证对等证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false); #不检查证书公用名的存在和与主机名匹配
    curl_setopt($ch, CURLOPT_SSL_ENABLE_ALPN , false); #禁用用于协商到http2的ALPN,
    curl_setopt($ch, CURLOPT_SSL_ENABLE_NPN , false); #禁用用于协商到http2的NPN
    curl_setopt($ch, CURLOPT_TIMEOUT , 60); #响应超时
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10); #链接超时
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

# 获取url内容,返回数组
function get_url_contents($url){
    # 该链接的来源处/引用处
    $url_array = parse_url($url);
    $refer = $url_array['scheme'] . '://' . $url_array['host'] . '/';
    $pos = strrpos($url, '/', 0);
    $refer = substr($url, 0, $pos + 1);
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
    $options = array(
        'http' => array(
            'method' => "GET",
            'header' => "Accept-language: $lang\r\n" .
                        "Referer: $refer\r\n" .
                        "User-Agent: $useragent\r\n" .
                        "Cookie: foo=bar\r\n"
        ),
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        )
    );
    $context = stream_context_create($options);
    set_error_handler(function($err_severity, $err_msg, $err_file, $err_line, array$err_context){
            throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
    }, E_WARNING);
    try{
        $body = file_get_contents($url, false, $context);
        $header = $http_response_header;
    }
    catch(Exception $e){
        $error = $e -> getMessage();
    }
    # restore the previous error handler 还原以前的错误处理程序
    restore_error_handler();
    if(!isset($body)) $body = NULL;
    if(!isset($header)) $header = NULL;
    if(!isset($error))$error = NULL;
    $res_array = array(
        'header' => $header,
        'body' => $body,
        'error' => $error,
    );
    return $res_array;
}

# 二级域名值
function get_domain_key($host){
    $domain_prefix = array('www', 'mail', 'blog', 'shop', 'forum', 'bbs', 'news', 'music',);
    $domain_2level = array("ac", "biz", "co", "com", "edu", "gov", "info", "mil", "net", "org",);
    $domain_2level_list = json_decode(file_get_contents(LIBS . '/domain-2level.json'));
    if(substr_count($host, '.') > 2){;
        $new = substr($host, 0, strripos($host, '.'));
        $domain_2 = substr($host, strripos($new, '.') + 1);
        if(in_array($domain_2, $domain_2level_list)) $host = str_replace($domain_2, '', $host);
    }
    if(filter_var($host, FILTER_VALIDATE_IP)){
        $domain_key = crc32($host); //md5($host);
    }else{
        $host_array = explode('.', $host);
        $num_dot = count($host_array);
        if($num_dot === 1) $domain_key = $host_array[0];
        elseif($num_dot === 2) $domain_key = $host_array[1];
        else $domain_key = $host_array[$num_dot-2];
    }
    return $domain_key;
}

# 子域名值
function get_domain_user($host){
    $host_array = explode('.', $host);
    $num_dot = count($host_array);
    $prefix = array('www', 'mail', 'blog', 'shop', 'forum', 'bbs', 'news', 'music',);
    $domain_key = '';
    foreach($host_array as $value){
        if(in_array($value, $prefix)) continue;
        else{
            $domain_key = $value;
            break;
        }
    }
    return $domain_key;
}

# <div id 获取主体
function getElementByIdname($html, $idname){
    $hackEncoding = '<?xml encoding="UTF-8">';
    $doc = new DOMDocument();
    @$doc -> loadHTML($hackEncoding . $html); # 这里带上encode
    $chinese = $doc -> getElementById($idname);
    $result = $doc -> saveHTML($chinese);
    return $result;
}

# <div class 获取主体
function getElementByClassname($html, $classname){
    $dom = new DOMDocument();
    @$dom -> loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXpath($dom);
    $nodes = $xpath -> query('//div[@class="' . $classname . '"]'); // $xpath->query('//div[@id="main"]/*')
    $tmp_dom = new DOMDocument();
    foreach($nodes as $node){
        $tmp_dom -> appendChild($tmp_dom -> importNode($node, true));
    }
    return trim($tmp_dom -> saveHTML());
}

# 自定义 闭合标签，比如 article
function getElementByCustomname($html, $tag){
    $tag1 = '<'. $tag;
    $tag2 = '</'. $tag .'>';
    $article = explode($tag1, $html, 2)[1];
    $article = explode($tag2, $article, 2)[0];
    $article = $tag1 . $article . $tag2;
    return $article;
}

# 服务器操作系统类型
function Server_OS(){
    // echo php_uname('s') . "\n";
    if(strpos(strtoupper(PHP_OS), 'WIN') === 0){
        return 'windows';
    }else{
        return 'linux';
    }
}

# 去掉js和css风格，提取title重构head
function del_script($str){
    $search = array(
                "'<script[^>]*?>.*?</script>'si", # 去掉 javascript
                "'<style[^>]*?>.*?</style>'si",   # 去掉 css
                "'<link[/!]*?[^<>]*?>'si",        # 去掉 link
                /*
                "'<meta\sname[^>]*?>'si"          # 去掉 meta
                "'<meta[/!]*?[^<>]*?>'si",        # 去掉meta
                */
            );
    $replace = array("", "", "",);
    $str = preg_replace($search, $replace, $str);
    return $str;
}

# 修改 code 标签
function modify_code($str){
    /* $str = preg_replace("'<div[^>]*?>'iUs", '<div>', $str); */
    $str = preg_replace("'<span[^>]*?>'iUs", '', $str);
    $str = preg_replace("'</span>'iUs", '', $str);
    preg_match_all('/<code.*?>/i', $str, $code_array);
    // print_r($code_array);
    foreach($code_array[0] as $value){
        $str = str_replace($value, $value ."\n", $str);
        $str = str_replace("\n\n", "\n", $str);
    }
    return $str;
}


# 替换 no-break space 为普通空格
// $space = chr(160).chr(194);
// $space2nbsp = htmlentities($space, ENT_COMPAT, "UTF-8"); #  no-break space 转义为 nbsp
// $nbsp2space = html_entity_decode($nbsp, ENT_COMPAT, "UTF-8"); # nbsp 转义为 no-break space
// $str = str_replace(array("\xa0", "\xc2"), array(' ', ' '), $str); # 汉字会乱码
// $str = str_replace(array(' <', '> '), array('<', '>'), $str);
//for($i = 0; $i < 100; $i++){
// parse_url($url)['path']
// $basename = $url_array['basename']; # 含后缀
// $filename = $url_array['filename']; # 不含后缀
