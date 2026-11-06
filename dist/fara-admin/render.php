<?php
require __DIR__.'/core.php';
require __DIR__.'/design-core.php';
try{
 $file=$_GET['page']??'index.html';$page=null;foreach(site_config()['pages'] as $p)if($p['href']===$file)$page=$p;
 if(!$page)foreach(design_store()['entities'] as $id=>$e)if(str_starts_with($id,'page-')&&($e['draft']['href']??'')===$file)$page=['id'=>$id];
 if(!$page){http_response_code(404);exit('Page not found');}
 header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-cache');header('X-Content-Type-Options: nosniff');
 $html=design_render($page['id']);if($html===null){http_response_code(404);exit('Page not found');}echo $html;
}catch(Throwable $e){error_log('FARA render: '.$e->getMessage());http_response_code(503);echo 'سایت موقتاً در دسترس نیست.';}
