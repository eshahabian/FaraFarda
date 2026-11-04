<?php
require __DIR__.'/core.php';
session_boot();$u=current_user();if(!$u||$u['mustChange']){http_response_code(403);exit('ابتدا وارد پنل شوید و رمز موقت را عوض کنید.');}
try{
 $id=$_GET['page']??'home';$page=page_config($id);$html=render_page($id);$scan=text_scan($html,$id);$byParent=[];
 foreach($scan['fields'] as $f)if(!$f['derived'])$byParent[$f['parentStart']][]=$f['id'];$patches=[];
 foreach($scan['nodes'] as $n)if(isset($n['start'],$byParent[$n['start']]))$patches[]=['start'=>$n['start'],'end'=>$n['end'],'value'=>substr($n['raw'],0,-1).' data-editor-fields="'.esc(json_encode($byParent[$n['start']],JSON_UNESCAPED_UNICODE)).'">'];
 $html=patch($html,$patches);$html=str_replace('<head>','<head><base href="'.esc(site_base().'/'.$page['href']).'">',$html);$html=str_replace('</body>','<script src="'.esc(site_base()).'/fara-admin/preview.js"></script></body>',$html);
 header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');header('X-Frame-Options: SAMEORIGIN');echo $html;
}catch(Throwable $e){http_response_code(404);echo 'صفحه پیدا نشد.';}
