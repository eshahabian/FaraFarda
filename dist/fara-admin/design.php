<?php
declare(strict_types=1);
require __DIR__.'/core.php';require __DIR__.'/design-core.php';
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try {
 if(isset($_GET['media'])){
  $id=(string)$_GET['media'];$s=design_store();$m=$s['media'][$id]??null;if(!$m){http_response_code(404);exit;}
  $used=false;$walk=function($v)use(&$walk,&$used,$id){if(!is_array($v))return;foreach($v as $k=>$x){if(in_array($k,['media','image'],true)&&$x===$id)$used=true;if(is_array($x))$walk($x);}};
  foreach($s['entities'] as $e)if($e['status']==='published'){$walk($e['published']);if(in_array($id,$e['published']['assetOverrides']??[],true))$used=true;}
  if(!$used){session_boot();$u=current_user();if(!$u||$u['mustChange']){http_response_code(404);exit;}$allowed=design_can($u,'media');foreach($s['entities'] as $key=>$e){$cap=$key==='shared'?'shared':'pages';if(!design_can($u,$cap,$key==='shared'?'':$key))continue;$used=false;$walk($e['draft']);if($used||in_array($id,$e['draft']['assetOverrides']??[],true))$allowed=true;}if(!$allowed)api_json(403,['error'=>'مجوز رسانه را ندارید.']);}
  header('Content-Type: '.$m['mime']);$file=data_dir().'/design-media/'.$m['file'];header('Content-Length: '.filesize($file));readfile($file);exit;
 }
 session_boot();$u=current_user();if(!$u)api_json(401,['error'=>'وارد پنل شوید.']);if($u['mustChange'])api_json(403,['error'=>'رمز موقت را تغییر دهید.']);
 $method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'list';$id=(string)($_GET['id']??'home');$d=[];
 if($method==='POST'){
  if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json')||!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_EDITOR_TOKEN']??''))api_json(403,['error'=>'درخواست یا نشست معتبر نیست.']);
  $origin=$_SERVER['HTTP_ORIGIN']??'';if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site'||($origin!==''&&$origin!==(https()?'https://':'http://').($_SERVER['HTTP_HOST']??'')))api_json(403,['error'=>'مبدأ معتبر نیست.']);
  $raw=file_get_contents('php://input',false,null,0,15*1024*1024);if(strlen($raw)>=15*1024*1024)api_json(413,['error'=>'فایل بزرگ است.']);$d=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($d))throw new InvalidArgumentException('Body');
 }elseif($method!=='GET')api_json(405,['error'=>'روش درخواست معتبر نیست.']);
 $cap=match($id){'shared'=>'shared','menu'=>'menu','categories'=>'categories','patterns'=>'blocks',default=>'pages'};$pageScope=in_array($id,['shared','menu','categories','patterns'],true)?'':$id;
 if($action==='list'){
  $s=design_store();$items=[];foreach(site_config()['pages'] as $p)$items[$p['id']]=design_entity($p['id'],$s);foreach($s['entities'] as $key=>$e)if(str_starts_with($key,'page-'))$items[$key]=$e;
  $result=[];foreach($items as $key=>$e)if(design_can($u,'pages',$key))$result[]=['id'=>$key,'title'=>$e['draft']['title'],'href'=>$e['draft']['href'],'status'=>$e['status'],'version'=>$e['version'],'hasDraft'=>$e['draft']!==$e['published']];
  api_json(200,['pages'=>$result,'user'=>public_user($u)+['permissions'=>$u['permissions']??[]],'media'=>design_can($u,'media')?$s['media']:[],'assets'=>design_can($u,'media')?image_catalog(false):[]]);
 }
 if($action==='permissions'){
  if($u['role']!=='admin')api_json(403,['error'=>'فقط مدیر.']);
  if($method==='GET')api_json(200,['users'=>array_map(fn($key,$v)=>public_user($v+['id'=>$key])+['permissions'=>$v['permissions']??[]],array_keys(read_json('users')),array_values(read_json('users')))]);
  locked(function()use($d,$u){$users=read_json('users');if(!password_verify((string)($d['password']??''),$users[$u['id']]['hash']))api_json(403,['error'=>'رمز مدیر درست نیست.']);$key=$d['user']??'';if(!isset($users[$key])||$users[$key]['role']==='admin')throw new InvalidArgumentException('Designer required');$p=$d['permissions']??[];$out=[];foreach(['pages','text','blocks','media','menu','shared','categories','create','publish'] as $c){if($c==='pages'){$out[$c]=array_values(array_filter($p[$c]??[],fn($v)=>is_string($v)&&preg_match('~^[a-z0-9-]+$~D',$v)));}else $out[$c]=($p[$c]??false)===true;}
   // Page access is an explicit list; capabilities remain independent.
   $out['pageAccess']=true;$users[$key]['permissions']=$out;$users[$key]['version']++;write_json('users',$users);audit('design-permissions');});api_json(200,['ok'=>true]);
 }
 if($action==='create'&&$method==='POST'){
  design_require($u,'create');$slug=design_text($d['slug']??'',80);if(!preg_match('~^[a-z][a-z0-9-]{0,79}$~D',$slug))throw new InvalidArgumentException('Slug');$key='page-'.$slug;
  locked(function()use($d,$key,$slug,$u){$s=design_store();if(count($s['entities'])>=500)throw new InvalidArgumentException('Page limit');if(isset($s['entities'][$key]))api_json(409,['error'=>'این نشانی قبلاً استفاده شده است.']);$base=design_default('home');$base['title']=design_text($d['title']??'',320);$base['href']='pages/'.$slug.'.html';$base['mode']='replace';
   if(isset($d['copy'])){design_require($u,'pages',(string)$d['copy']);$copy=design_entity((string)$d['copy']);$base=array_replace($base,array_intersect_key($copy['draft'],array_flip(['blocks','seo','categories'])));}
   elseif(($d['template']??'')==='intro')$base['blocks']=design_blocks([['id'=>'intro','type'=>'heading','text'=>$base['title']]]);
   $s['entities'][$key]=['version'=>1,'draft'=>$base,'published'=>null,'status'=>'draft','history'=>[]];design_write($s);audit('design-create');});api_json(200,['ok'=>true,'id'=>$key]);
 }
 if($action==='media-meta'&&$method==='POST'){
  design_require($u,'media');locked(function()use($d){$s=design_store();$key=$d['media']??'';if(!isset($s['media'][$key]))throw new InvalidArgumentException('Media');$s['media'][$key]['title']=design_text($d['title']??'',320);$s['media'][$key]['alt']=design_text($d['alt']??'',500);design_write($s);});api_json(200,['ok'=>true]);
 }
 if($action==='upload'&&$method==='POST'){
  design_require($u,'media');$dest=$d['destination']??'';if(!in_array($dest,['library','block','header','footer','existing'],true))throw new InvalidArgumentException('Choose destination');
  if($dest!=='library'){design_require($u,$dest==='block'?'blocks':'shared',$dest==='block'?$id:'');}
  $bytes=base64_decode((string)($d['base64']??''),true);if($bytes===false||strlen($bytes)>10*1024*1024)throw new InvalidArgumentException('File');$info=@getimagesizefromstring($bytes);$types=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!$info||!isset($types[$info['mime']])||$info[0]*$info[1]>40000000||str_contains($bytes,'<?'))throw new InvalidArgumentException('Image type');
  $key=bin2hex(random_bytes(12));$m=['file'=>hash('sha256',$bytes).'.'.$types[$info['mime']],'mime'=>$info['mime'],'width'=>$info[0],'height'=>$info[1],'title'=>design_text($d['title']??'',320),'alt'=>design_text($d['alt']??'',500)];
  locked(function()use($key,$m,$bytes,$dest,$id,$d,$u){$s=design_store();$folder=data_dir().'/design-media';if(!is_dir($folder))mkdir($folder,0700);$tmp=$folder.'/'.bin2hex(random_bytes(12)).'.tmp';if(file_put_contents($tmp,$bytes)!==strlen($bytes))throw new RuntimeException('Media write');chmod($tmp,0600);if(!rename($tmp,$folder.'/'.$m['file']))throw new RuntimeException('Media rename');$s['media'][$key]=$m;
   if($dest!=='library'){$fresh=current_user();if(!$fresh)api_json(401,['error'=>'نشست باطل شده است.']);design_require($fresh,'media');design_require($fresh,$dest==='block'?'blocks':'shared',$dest==='block'?$id:'');$target=$dest==='block'?$id:'shared';$e=design_entity($target,$s);if(($d['version']??-1)!==$e['version'])api_json(409,['error'=>'طرح تغییر کرده؛ دوباره بارگذاری کنید.']);$field=$dest==='block'?'blocks':$dest;$found=false;$replace=function(&$blocks)use(&$replace,&$found,$key,$d){foreach($blocks as &$b){if($b['id']===($d['block']??'')){$b['media']=$key;$found=true;}$replace($b['children']);}};
    if($dest==='existing'){$asset=$d['asset']??'';if(!in_array($asset,array_column(image_catalog(false),'id'),true))throw new InvalidArgumentException('Asset');$e['draft']['assetOverrides'][$asset]=$key;}
    else {
    if(($d['operation']??'add')==='replace'){$replace($e['draft'][$field]);if(!$found)throw new InvalidArgumentException('Target block');}
    else $e['draft'][$field][]=design_blocks([['id'=>'image-'.$key,'type'=>'image','media'=>'','alt'=>$m['alt']]])[0]+[];
    if(($d['operation']??'add')!=='replace')$e['draft'][$field][array_key_last($e['draft'][$field])]['media']=$key;
    }
    $e['history'][]=['at'=>gmdate('c'),'by'=>$u['username'],'action'=>'upload','draft'=>design_entity($target,$s)['draft']];$e['history']=array_slice($e['history'],-50);$e['version']++;$s['entities'][$target]=$e;
   }design_write($s);audit('design-upload');});api_json(200,['ok'=>true,'id'=>$key]);
 }
 design_require($u,$cap,$pageScope);$e=design_entity($id);
 if($method==='GET'){
  if($action==='preview'){$draft=$e['draft'];if($id==='shared')$html=design_render('shared',$draft);elseif(in_array($id,['menu','categories','patterns'],true))throw new InvalidArgumentException('Preview page required');else $html=design_preview_html($id,$draft);
   header('Content-Type: text/html; charset=utf-8');header('X-Robots-Tag: noindex');echo $html;exit;}
  $fields=[];if($pageScope!==''&&!str_starts_with($id,'page-'))$fields=array_values(array_map(fn($f)=>array_intersect_key($f,array_flip(['id','label','value'])),array_filter(fields_for(render_page($id),$id,state()),fn($f)=>$f['scope']==='page')));
  api_json(200,['entity'=>$e,'fields'=>$fields]);
 }
 $result=locked(function()use($u,$id,$cap,$pageScope,$action,$d){$s=design_store();$e=design_entity($id,$s);if(($d['version']??-1)!==$e['version'])api_json(409,['error'=>'نسخهٔ جدیدتری ذخیره شده؛ برای جلوگیری از بازنویسی، دوباره بارگذاری کنید.']);
  // Recheck current permissions while holding the write lock.
  $fresh=current_user();if(!$fresh)api_json(401,['error'=>'مجوز نشست تغییر کرده است.']);design_require($fresh,$cap,$pageScope);
  $before=$e;$next=$e['draft'];
  if($action==='save'||$action==='preview'){
   if(!is_array($d['document']??null))throw new InvalidArgumentException('Document');
   $next=design_validate($id,$d['document']??[],$e['draft']);
   if($pageScope!==''){
    if(($next['categories']??[])!==($e['draft']['categories']??[]))design_require($fresh,'categories',$id);
    if(($next['text']??[])!==($e['draft']['text']??[]))design_require($fresh,'text',$id);
    if(($next['blocks']??[])!==($e['draft']['blocks']??[])||($next['mode']??'')!==($e['draft']['mode']??''))design_require($fresh,'blocks',$id);
   }
   if($action==='preview'){header('Content-Type: text/html; charset=utf-8');header('X-Robots-Tag: noindex');echo design_preview_html($id,$next);exit;}
   $e['draft']=$next;
  }elseif($action==='publish'){design_require($fresh,'publish',$pageScope);if($e['status']==='trash')throw new InvalidArgumentException('Restore first');design_check_public_links($id,$e['draft']);$e['published']=$e['draft'];$e['status']='published';}
  elseif(in_array($action,['unpublish','trash'],true)){design_require($fresh,'publish',$pageScope);$e['status']=$action==='trash'?'trash':'unpublished';}
  elseif($action==='restore'){$e['status']=$e['published']?'unpublished':'draft';}
  elseif($action==='revision'){if($pageScope!==''){design_require($fresh,'text',$pageScope);design_require($fresh,'blocks',$pageScope);}$n=$d['revision']??-1;if(!isset($e['history'][$n]))throw new InvalidArgumentException('Revision');$e['draft']=$e['history'][$n]['draft'];}
  else throw new InvalidArgumentException('Action');
  $e['history'][]=['at'=>gmdate('c'),'by'=>$fresh['username'],'action'=>$action,'draft'=>$before['draft']];$e['history']=array_slice($e['history'],-50);$e['version']++;$s['entities'][$id]=$e;design_write($s);audit('design-'.$action);return ['ok'=>true,'version'=>$e['version']];});api_json(200,$result);
}catch(InvalidArgumentException|JsonException $e){api_json(400,['error'=>'داده معتبر نیست؛ نشانی، بلوک، سبک، فایل یا ارجاع را بررسی کنید.']);}catch(Throwable $e){error_log('FARA design: '.$e->getMessage());api_json(500,['error'=>'عملیات انجام نشد؛ فضای خصوصی و گزارش سرور را بررسی کنید.']);}
