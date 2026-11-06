<?php
// Local/test router, never copied into dist.
$root=getenv('FARA_TEST_ROOT')?:__DIR__.'/dist';$prefix=rtrim(getenv('FARA_TEST_PREFIX')?:'','/');$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($prefix!==''&&!str_starts_with($path,$prefix.'/')){http_response_code(404);return true;}$path=substr($path,strlen($prefix));
$run=function($file)use($root,$prefix){$_SERVER['SCRIPT_NAME']=$prefix.'/fara-admin/'.$file;require $root.'/fara-admin/'.$file;};
if($path==='/fara-admin'||$path==='/fara-admin/'){$run('index.php');return true;}
if(preg_match('~^/fara-admin/(api|index|design|sitemap|preview|render|media)\.php$~D',$path,$m)){$run($m[1].'.php');return true;}
if($path==='/sitemap.xml'){$run('sitemap.php');return true;}
if(preg_match('~^/fara-admin/(?:core|design-core|config)\.php|^/fara-admin/site\.json~',$path)){http_response_code(403);return true;}
if($path==='/'||$path==='/index.html'||preg_match('~^/(?:sectors|pages)/[a-z0-9-]+\.html$~D',$path)){$_GET['page']=$path==='/'?'index.html':ltrim($path,'/');$run('render.php');return true;}
if(preg_match('~^/assets/[^/]+\.(png|jpe?g|webp)$~i',$path)){$_GET['asset']=ltrim($path,'/');$run('media.php');return true;}
$file=realpath($root.$path);$real=realpath($root);if(!$file||!is_file($file)||!str_starts_with($file,$real.DIRECTORY_SEPARATOR)||str_ends_with($file,'.php')){http_response_code(404);return true;}
$ext=pathinfo($file,PATHINFO_EXTENSION);$types=['js'=>'text/javascript','css'=>'text/css','svg'=>'image/svg+xml','woff2'=>'font/woff2','ico'=>'image/x-icon','html'=>'text/html'];header('Content-Type: '.($types[$ext]??'application/octet-stream'));readfile($file);return true;
