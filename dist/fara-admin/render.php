<?php
require __DIR__.'/core.php';
try{
 $file=$_GET['page']??'index.html';$page=null;foreach(site_config()['pages'] as $p)if($p['href']===$file)$page=$p;
 if(!$page){http_response_code(404);exit('Page not found');}
 header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-cache');header('X-Content-Type-Options: nosniff');
 echo render_page($page['id']);
}catch(Throwable $e){error_log('FARA render: '.$e->getMessage());http_response_code(503);echo 'سایت موقتاً در دسترس نیست.';}
