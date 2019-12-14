<?php

/**
 * *用对比MD5的方法处理杂碎重复文件
 * *确认无误后手动删除原文件
 * *为快速复制文件，对较大的文件不复制
 *
 * *为保护数据，对已经存在的 $update_path 目录作了重命名
 * 
 * *部分代码来源于网络
 */
 
if(php_sapi_name() === 'cli'){
	echo "\r\n  ==================== 检测到以 CLI 模式执行脚本 =====================\r\n";
	echo "\r\n  ==================== 设置一个待处理的目录 =====================\r\n";
    for($i = 0;$i < 10;$i++){
        ob_implicit_flush(1);
        fwrite(STDOUT, "\r\n    请输入待处理的目录(如D:/doc):");
        $source_path = trim(fgets(STDIN));
        if(empty($source_path)or!file_exists($source_path)){
            if($i === 9){
                echo "\r\n\r\n\r\n    输入超时,脚本停止执行\r\n\r\n";
                sleep(3);
                exit(0);
            }
            echo "\r\n   $source_path 该目录不存在，请重新输入\r\n";
        }else{
            echo "\r\n    待处理的目录是 $source_path\r\n\r\n";
            break;
        }
    }
	echo "\r\n  ==================== 设置一个数字 =====================\r\n";
    echo "\r\n    为加快处理速度，太大的文件将被跳过，脚本执行完毕后请自行复制\r\n";
    echo "\r\n    该值只能是大于 0 的数字，默认值为 100M\r\n\r\n";
    for($i = 0;$i < 10;$i++){
        fwrite(STDOUT, "\r\n    请输入最大数值(不输入则默认为100):");
        $maxlenth = trim(fgets(STDIN));
        if(empty($maxlenth)){
            $maxlenth = '104857600';
            echo "\r\n    大于 100M 的文件请自行复制";
            break;
        }elseif(is_numeric($maxlenth) and $maxlenth > 0){
            echo "\r\n    大于 $maxlenth M的文件请自行复制";
            $maxlenth = 1048576 * $maxlenth;
            break;
        }else{
            if($i === 9){
                echo "\r\n\r\n\r\n    输入超时,脚本停止执行\r\n\r\n";
                sleep(3);
                exit(0);
            }
            echo "\r\n    输入错误,请输入大于 0 的数字 \r\n";
        }
    }
}else{
    $source_path = 'D:/doc'; # 需要去重复文件的目录，这个必须有
    $maxlenth = '104857600'; # 为快速复制文件，放弃大于100M的文件
}

//header("Content-type: text/html;charset=utf-8");
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
$t1 = microtime(true);

$update_path = $source_path . '-update';     # 非重复文件的保存目录
$log_path = dirname(__FILE__) . '/phplog';   # 操作记录保存目录
$compare = 'false';                          # 用于比较那些文件发生了变化，取值 true 和 false


// 对 MD5 不同的文件作记录
if($compare == 'true'){
    if(!file_exists($log_path . '/all.log')) die("文件不存在");
    $files = file_get_contents($log_path . '/all.log');
    $array_files = explode("\r\n", $files);
    
    $n = count($array_files);
    for($i = 0;$i < $n;$i++){
        $$array_files_md5 = explode('//// ', $array_files[$i]);
        $filename = trim($array_files_md5['0']);
        $md5 = trim($array_files_md5['1']);
	    if(!file_exists($filename)) {
		    echo "找不到的文件 $filename \r\n<br>";	
			continue;
			}
        $md5file = md5_file($filename);
        if($md5file !== $md5) @file_put_contents($log_path . '/changes.log', $filename, FILE_APPEND);
        }
    # rm_empty_dir($path);      # 删除空目录
exit(0);
}




if(!file_exists($source_path)) die(" 需要去重复文件的目录不存在，什么也没干 。;)");
echo "\r\n  正在读取文件的 MD5 值\r\n";
customize_flush();
$array_files = getDir($source_path);
$maxstr = getItem($array_files);
$maxlen = strlen($maxstr) + 2;
$max = count($array_files);
$filemd5 = '';
$array_files_md5 = array();
for($i = 0;$i < $max;$i++){
    $filename = $array_files[$i];
    $md5 = md5_file($filename) . "\r\n";
    $filename = str_pad($filename, $maxlen);         # 用空格补全
    $array_files_md5 = $array_files_md5 + array($filename => $md5,);
    $filemd5 .= $filename . ' ////  ' . $md5;
    }
$array_files_compact = array_flip($array_files_md5);    # 删除数组中的重复key
$min = count($array_files_compact); 
if(!file_exists($log_path)) @mkdir($log_path, '0777', true);
if(file_exists($log_path . '/all.log')) unlink($log_path . '/all.log');
if(file_exists($log_path . '/compact.log')) unlink($log_path . '/compact.log');
file_put_contents($log_path . '/all.log', $filemd5);
$fp = fopen($log_path . '/compact.log', 'a+b');
fwrite($fp, print_r($array_files_compact, true));
fclose($fp);
unset($array_files);
unset($array_files_md5);
unset($md5);
unset($filemd5);
unset($filename);
# $maxlen, $max, $min 和 $array_files_compact 不能释放
echo "\r\n  文件读取完毕，生成了无重复文件列表并作了记录 \r\n  正在复制文件中，请耐心等待 ...... \r\n";
foreach($array_files_compact as $md5 => $srcfile){
    $srcfile = trim($srcfile);
    copyfiles($srcfile);
    }
file_exists($log_path . '/false.log') ? $str = file_get_contents($log_path . '/false.log') : $str = '';
$false = substr_count($str,'////');
$t2 = microtime(true);
echo "\r\n  执行完毕，共计  " . $max . " 个 \r\n  去掉重复后剩余  " . $min . " 个"; 
echo "\r\n  没有复制的文件  " . $false . " 个。相关文档 " . $log_path . "/false.log \r\n  共计耗时 " . round($t2-$t1,3) . " 秒";
echo "\r\n  检测复制过程中可能遗漏的大文件 ......  如果有漏掉的文件，请动手复制 \r\n ";

$miss = '';
foreach($array_files_compact as $md5 => $srcfile){
    $srcfile = trim($srcfile);
	if(strpos($srcfile, '\\') !== false){
        $path = str_replace("\\", '/', $srcfile);
	}else{
	    $path = $srcfile;
	}
    $relativepath = explode('/', $path, 2);
	$dstfile = $update_path.'/'.$relativepath['1'];
    if (!file_exists($dstfile)) {
	    $miss .= $srcfile . "    \r\n";
        #echo  $dstfile ."\r\n";
        }
    }
echo "\r\n  遗漏了 " . substr_count($miss,"\r\n") . " 个文件   遗漏的文件是 \r\n\r\n $miss ";
file_put_contents($log_path . '/large.log', $miss);
sleep(30);
exit(0);

/********************函数区，无需改动*********************/

/**
 * *刷新缓冲
 */	
function customize_flush(){
    if(php_sapi_name() === 'cli'){
	return true;
	}else{
        echo(str_repeat(' ',256));
        // check that buffer is actually set before flushing
        if (ob_get_length()){           
            @ob_flush();
            @flush();
            @ob_end_flush();
        }   
        @ob_start();
	}
}

/**
 * *复制文件，log 被记录
 * *使用 copyfiles($srcfile)，无返回值
 *
 * @参数 $srcfile 原文件，$update_path 目标目录，$maxlenth 限制最大文件
 */	
function copyfiles($srcfile){

    global $update_path;
    global $log_path;
    global $maxlenth;
	global $maxlen;
	if (empty($update_path)) $update_path = $source_path . '-update';
	if (empty($log_path)) $log_path = dirname(__FILE__) . '\phplog';
	if (empty($maxlenth)) $maxlenth = 104857600;
	if (!file_exists($update_path))  mkdir($update_path, '0777',  true);
    $srcfileinfo = customize_fileinfo($srcfile);
    $dstpath = $update_path . '/' . $srcfileinfo['relativepath'] . '/';
    $dstfile = $dstpath . $srcfileinfo['basename'];
    if (!is_dir($dstpath)){
        $mode = $srcfileinfo['perms'];
        @mkdir($dstpath, $mode, true);
        }
    # 如果目标文件存在，则新文件名为原文件名-文件日期-4位随机数
    if (file_exists($dstfile)){
	    $dstfilemd5 = md5_file($dstfile);
		if($srcfileinfo['md5'] !== $dstfilemd5){ 
            $dstfilename = $srcfileinfo['filename'] . '-' . $srcfileinfo['mtime'] . '-' . rand_char($n = 4) . '.' . $srcfileinfo['extension'];
            $dstfile = $dstpath . $dstfilename;
		    }
        }
    $srcfileinfo['size'] < $maxlenth ? $cp = copy($srcfile, $dstfile) : $cp = 0;
    // 记录
	if(file_exists($log_path . '/false.log')) unlink($log_path . '/false.log');
    $log = str_pad($srcfile, $maxlen);
    if ($cp !== 1 ) @file_put_contents($log_path . '/false.log', $log . "  ////  no \r\n", FILE_APPEND);
	return $cp;
    }

/**
 * *获取随机字串
 * 
 * @参数 $n=4 字串
 */
function rand_char($n=4) { 
    $rand = '';
    for($i = 0;$i < $n;$i++ ){
        $base = 62;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $rand .= $chars[mt_rand(1, $base) - 1];
	}
	return $rand;
}

/**
 * *取数组长度最长的值
 * 
 * @参数 $array数组
 */
function getItem($array) {
    $index = 0;
    foreach ($array as $k => $v) {
        if (strlen($array[$index]) < strlen($v))
            $index = $k;
    }
    return @$array[$index];
}

/**
 * *遍历目录中的文件
 * *由 searchDir() 和 getDir() 两个函数组成，
 * *使用 getDir($path) ，返回数组
 *
 * @参数 $path目录路径
 */	
function searchDir($path, & $data){
    if(is_dir($path)){
        $dp = dir($path);
        while($file = $dp -> read()){
            if($file != '.' && $file != '..'){
                searchDir($path . '/' . $file, $data);
                }
            }
        $dp -> close();
        }
    if(is_file($path)){
        $data[] = $path;
        }
    }
function getDir($path){
    $data = array();
    searchDir($path, $data);
    return $data;
    }

/**
 * *获取文件信息
 * *由两个函数组成  customize_fileinfo() , minetype_array() 
 * *用法 customize_fileinfo($file)，返回数组
 *
 * @参数 $file 包括路径和文件名
 */

function customize_fileinfo($file){
    
    if(!file_exists($file)) die("文件不存在或者是超链接");
    $file_info = array();
    $realpath = realpath($file);
    $pathinfo = pathinfo($file);
	if(strpos($pathinfo['dirname'], '\\') !== false){
		$relativepath_win = explode('\\', $pathinfo['dirname'], 2);
		$drive = $relativepath_win[0];
		$relativepath_backslashes = $relativepath_win[1];
		$dir = str_replace("\\", '/', $pathinfo['dirname']);
		$relativepath = explode('/', $dir, 2);
	}else{
		$relativepath = explode('/', $pathinfo['dirname'], 2);
    }
	$size = filesize($file);
	$type = filetype($file);
	$mimeType = minetype_array();
	$key = @$pathinfo['extension'];
	if(array_key_exists($key,$mimeType)) {
	        $mime_type = $mimeType[$key];
	    }else{
	        $mime_type = 'application/x-' . $key;
	    }
    $md5 = md5_file($file);
    $sha1 = sha1_file($file);
    $ctime = filectime($file);
    $ctime = date("Ymd-His", $ctime);
    $atime = fileatime($file);
    $atime = date("Ymd-His", $atime);
    $mtime = filemtime($file);
    $mtime = date("Ymd-His", $mtime);
    $group = filegroup($file);
    $owner = fileowner($file);
    $inode = fileinode($file);
    $perms = fileperms($file);
    $is_file = is_file($file);
    $is_file == 1 ? $is_file = 'yes' : $is_file = 'no';
    $is_dir = is_dir($file);
    $is_dir == 1?$is_dir = 'yes':$is_dir = 'no';
    $is_executable = is_executable($file);
    $is_executable == 1?$is_executable = 'yes':$is_executable = 'no';
    $is_readable = is_readable($file);
    $is_readable == 1?$is_readable = 'yes':$is_readable = 'no';
    $is_writable = is_writable($file);
    $is_writable == 1?$is_writable = 'yes':$is_writable = 'no';
    $is_link = is_link($file);
    $is_link == 1?$is_link = 'yes':$is_link = 'no';
	$stat = stat($file);
	
    $file_info = $file_info + array('realpath' => $realpath, 'relativepath' => $relativepath['1']) + $pathinfo;
	if(isset($relativepath_win)) $file_info = $file_info + array('drive' => $drive, 'relativepath_win' => $relativepath_backslashes);
    $file_info = $file_info + array(
	    'mime' => $mime_type, 
	    'type' => $type, 
	    'size' => $size,
	    'md5' => $md5,
	    'sha1' => $sha1,		
	    'ctime' => $ctime, 
		'mtime' => $mtime, 
		'atime' => $atime,
		'group' => $group, 
		'owner' => $owner, 
		'inode' => $inode, 
		'perms' => $perms,
		'is_file' => $is_file, 
		'is_dir' => $is_dir, 
		'is_executable' => $is_executable, 
		'is_readable' => $is_readable, 
		'is_writable' => $is_writable, 
		'is_link' => $is_link,
		'dev' => $stat['dev'],
		'nlink' => $stat['nlink'],
		'uid' => $stat['uid'],
		'gid' => $stat['gid'],
		'rdev' => $stat['rdev'],
		'blksize' => $stat['blksize'],
		'blocks' => $stat['blocks'],
		);
	if(strpos($pathinfo['dirname'], '/') !== false){
		$basename = explode('/', $pathinfo['basename']);
		$filename = explode('/', $pathinfo['filename']);
	    $file_info['basename'] = $basename[count($basename)-1];
		$file_info['filename'] = $filename[count($filename)-1];
		}
    return $file_info;
}

function minetype_array(){
    $mimeType = array(
        // applications(应用类型)
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'exe' => 'application/octet-stream',
        'doc' => 'application/vnd.ms-word',
        'xls' => 'application/vnd.ms-excel',
        'pdf' => 'application/pdf',
        'xml' => 'application/xml',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pps' => 'application/vnd.ms-powerpoint',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'swf' => 'application/x-shockwave-flash',
        
        // archives(档案类型)
        'gz' => 'application/x-gzip',
        'tgz' => 'application/x-gzip',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar',
        'tar' => 'application/x-tar',
        'bz' => 'application/x-bzip2',
        'bz2' => 'application/x-bzip2',
        'tbz' => 'application/x-bzip2',
        '7z' => 'application/x-7z-compressed',
        
        // texts(文本类型)
        'txt' => 'text/plain',
        'php' => 'text/x-php',
        'html' => 'text/html',
        'htm' => 'text/html',
        'js' => 'text/javascript',
        'css' => 'text/css',
        'rtf' => 'text/rtf',
        'rtfd' => 'text/rtfd',
        'py' => 'text/x-python',
        'java' => 'text/x-java-source',
        'pl' => 'text/x-perl',
        'sql' => 'text/x-sql',
        'rb' => 'text/x-ruby',
        'sh' => 'text/x-shellscript',
        
        // images(图片类型)
        'bmp' => 'image/x-ms-bmp',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'tga' => 'image/x-targa',
        'psd' => 'image/vnd.adobe.photoshop',
        
        // audio(音频类型)
        'mp3' => 'audio/mpeg',
        'mid' => 'audio/midi',
        'ogg' => 'audio/ogg',
        'mp4a' => 'audio/mp4',
        'wav' => 'audio/wav',
        'wma' => 'audio/x-ms-wma',
        
        // video(视频类型)
        'avi' => 'video/x-msvideo',
        'dv' => 'video/x-dv',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'wm' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'mkv' => 'video/x-matroska'
        );	
    return $mimeType;
    }
	
/**
 * *删除所有空目录
 * 
 * @参数 $path目录路径
 */
function rm_empty_dir($path){
    if(is_dir($path) && ($handle = opendir($path)) !== false){
        while(($file = readdir($handle)) !== false){ // 遍历文件夹
            if($file != '.' && $file != '..'){
                $curfile = $path . '/' . $file;//当前目录
                if(is_dir($curfile)){ // 目录
                    rm_empty_dir($curfile);//如果是目录则继续遍历
                    if(count(scandir($curfile)) == 2){ // 目录为空,=2是因为.和..存在
                        rmdir($curfile);//删除空目录
                        }
                    }
                }
            }
        closedir($handle);
        }
    }
	
/**
 * *建立目录
 * 
 * @参数 $dir 字串，$mode 指定目录属性
 */
function mkdir_empty($dir, $mode){
    if(empty($mode)) $mode = 0777;
    if(file_exists($dir)) rename($dir, $dir . '-' . rand_char($n = 4) . '-old');
	@mkdir($dir, $mode, true);
    return @mkdir($dir, $mode, true);
    }
	
function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg) {
        if (substr($arg, 0, 2) == '--') {
            $eqPos = strpos($arg, '=');
            if ($eqPos === false) {
                $key       = substr($arg, 2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key       = substr($arg, 2, $eqPos - 2);
                $out[$key] = substr($arg, $eqPos + 1);
            }
        } elseif (substr($arg, 0, 1) == '-') {
            if (substr($arg, 2, 1) == '=') {
                $key       = substr($arg, 1, 1);
                $out[$key] = substr($arg, 3);
            } else {
                $chars = str_split(substr($arg, 1));
                foreach ($chars as $char) {
                    $key       = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}
	
?>
