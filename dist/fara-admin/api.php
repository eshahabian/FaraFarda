<?php
declare(strict_types=1);
require __DIR__.'/core.php';
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');
try {
 session_boot();$route=$_GET['r']??'';$method=$_SERVER['REQUEST_METHOD'];$data=[];
 if($method==='POST'){
  if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json'))api_json(415,['error'=>'قالب درخواست معتبر نیست.']);
  if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_EDITOR_TOKEN']??''))api_json(403,['error'=>'نشست معتبر نیست؛ پنل را دوباره باز کنید.']);
  if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site')api_json(403,['error'=>'درخواست خارج از پنل مجاز نیست.']);
  $origin=$_SERVER['HTTP_ORIGIN']??'';if($origin!==''&&$origin!==(https()?'https://':'http://').($_SERVER['HTTP_HOST']??''))api_json(403,['error'=>'مبدأ درخواست معتبر نیست.']);
  $raw=file_get_contents('php://input',false,null,0,16*1024*1024+1);if(strlen($raw)>16*1024*1024)api_json(413,['error'=>'حجم درخواست بیش از حد مجاز است.']);$data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($data))api_json(400,['error'=>'درخواست معتبر نیست.']);
 }elseif($method!=='GET')api_json(405,['error'=>'روش درخواست معتبر نیست.']);
 if($route==='session'&&$method==='GET'){$u=current_user();api_json(200,['user'=>$u?public_user($u):null,'csrf'=>$_SESSION['csrf'],'needsActivation'=>!is_file(data_dir().'/users.json')]);}
 if($route==='login'&&$method==='POST'){
  foreach(['username'=>40,'password'=>128,'activation'=>100] as $key=>$limit)if(isset($data[$key])&&(!is_string($data[$key])||strlen($data[$key])>$limit))api_json(400,['error'=>'اطلاعات ورود معتبر نیست.']);
  $result=locked(function()use($data){
   $key=hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown');$attempts=read_json('attempts');$attempts=array_filter($attempts,fn($a)=>($a['since']??0)>time()-900);$a=$attempts[$key]??['count'=>0,'since'=>time()];
   if($a['count']>=8)return [429,['error'=>'تعداد تلاش زیاد است؛ ۱۵ دقیقه بعد دوباره امتحان کنید.']];
   $users=read_json('users');$user=null;$uid=null;
   if(!$users){
    $valid=is_string($data['activation']??null)&&hash_equals(config()['activationHash'],hash('sha256',$data['activation']));
    if($valid&&($data['username']??'')==='eshahabian'&&password_verify((string)($data['password']??''),config()['bootstrapHash'])){
     $uid=bin2hex(random_bytes(12));$user=['username'=>'eshahabian','hash'=>config()['bootstrapHash'],'role'=>'admin','active'=>true,'mustChange'=>true,'version'=>1];$users[$uid]=$user;write_json('users',$users);
    }
   }else{foreach($users as $id=>$u)if(strcasecmp($u['username'],(string)($data['username']??''))===0){$uid=$id;$user=$u;break;}if(!$user||!$user['active']||!password_verify((string)($data['password']??''),$user['hash'])){$user=null;password_verify('dummy',config()['bootstrapHash']);}}
   if(!$user){$a['count']++;$attempts[$key]=$a;write_json('attempts',$attempts);return [401,['error'=>'اطلاعات ورود یا کد فعال‌سازی درست نیست.']];}
   unset($attempts[$key]);write_json('attempts',$attempts);session_regenerate_id(true);$_SESSION=['uid'=>$uid,'uv'=>$user['version'],'csrf'=>bin2hex(random_bytes(32)),'last'=>time(),'created'=>time()];audit('login');return [200,['ok'=>true,'user'=>public_user($user+['id'=>$uid])]];
  });api_json(...$result);
 }
 $user=current_user();if(!$user)api_json(401,['error'=>'برای ادامه وارد پنل شوید.']);
 if($route==='logout'&&$method==='POST'){$_SESSION=[];session_regenerate_id(true);api_json(200,['ok'=>true]);}
 if($route==='password'&&$method==='POST'){
  $result=locked(function()use($data,$user){$users=read_json('users');$u=$users[$user['id']];$new=$data['password']??'';
   if(!is_string($new)||strlen($new)<12||strlen($new)>128||$new!==($data['confirm']??'')||!password_verify((string)($data['current']??''),$u['hash'])||password_verify($new,$u['hash']))return [400,['error'=>'رمز فعلی باید درست باشد؛ رمز جدید باید متفاوت و حداقل ۱۲ نویسه و با تکرارش یکسان باشد.']];
   $u['hash']=password_hash($new,PASSWORD_DEFAULT);$u['mustChange']=false;$u['version']++;$users[$user['id']]=$u;write_json('users',$users);session_regenerate_id(true);$_SESSION['uv']=$u['version'];audit('password-change');return [200,['ok'=>true]];
  });api_json(...$result);
 }
 if($user['mustChange'])api_json(403,['error'=>'پیش از استفاده از پنل باید رمز موقت را تغییر دهید.','mustChange'=>true]);
 if($user['role']!=='admin')api_json(403,['error'=>'ویرایش طراحی فقط از API جدید و با مجوزهای اختصاصی مجاز است.']);
 if($method==='POST'&&in_array($route,['save','menu','image','undo'],true))api_json(410,['error'=>'ویرایش مستقیم بازنشسته شده است؛ از پیش‌نویس و انتشار استودیوی طراحی استفاده کنید.']);
 if($route==='users'){
  if($user['role']!=='admin')api_json(403,['error'=>'مدیریت کاربران فقط برای مدیر مجاز است.']);
  if($method==='GET'){api_json(200,['users'=>array_map(fn($id,$u)=>public_user($u+['id'=>$id]),array_keys(read_json('users')),array_values(read_json('users')))]);}
  $result=locked(function()use($data,$user){$users=read_json('users');$action=$data['action']??'';$id=$data['id']??'';
   if(!password_verify((string)($data['adminPassword']??''),$users[$user['id']]['hash']))return [403,['error'=>'رمز فعلی مدیر درست نیست.']];
   if($action==='add'){
    $name=$data['username']??'';$pass=$data['password']??'';$role=$data['role']??'';
    if(!is_string($name)||!preg_match('~^[A-Za-z0-9_.-]{3,40}$~D',$name)||!is_string($pass)||strlen($pass)<12||strlen($pass)>128||!in_array($role,['admin','editor'],true)||count($users)>=50)return [400,['error'=>'نام کاربری ۳ تا ۴۰ حرف انگلیسی و رمز موقت حداقل ۱۲ نویسه باشد.']];
    foreach($users as $u)if(strcasecmp($u['username'],$name)===0)return [409,['error'=>'این نام کاربری قبلاً ثبت شده است.']];
    $id=bin2hex(random_bytes(12));$users[$id]=['username'=>$name,'hash'=>password_hash($pass,PASSWORD_DEFAULT),'role'=>$role,'active'=>true,'mustChange'=>true,'version'=>1];
   }else{
    if(!isset($users[$id]))return [404,['error'=>'کاربر پیدا نشد.']];
    if($action==='reset'){$pass=$data['password']??'';if(!is_string($pass)||strlen($pass)<12||strlen($pass)>128)return [400,['error'=>'رمز موقت حداقل ۱۲ نویسه باشد.']];$users[$id]['hash']=password_hash($pass,PASSWORD_DEFAULT);$users[$id]['mustChange']=true;$users[$id]['version']++;}
    elseif($action==='update'){$role=$data['role']??'';if(!in_array($role,['admin','editor'],true)||!is_bool($data['active']??null))return [400,['error'=>'نقش یا وضعیت معتبر نیست.']];$users[$id]['role']=$role;$users[$id]['active']=$data['active'];$users[$id]['version']++;}
    else return [400,['error'=>'عملیات معتبر نیست.']];
    if($id===$user['id']&&(!$users[$id]['active']||$users[$id]['role']!=='admin'))return [400,['error'=>'نمی‌توانی حساب مدیر خودت را غیرفعال کنی یا نقش آن را کاهش بدهی.']];
   }
   if(!array_filter($users,fn($u)=>$u['active']&&$u['role']==='admin'&&!$u['mustChange']))return [400,['error'=>'حداقل یک مدیر فعال با رمز قطعی باید باقی بماند.']];
   write_json('users',$users);if($id===$user['id'])$_SESSION['uv']=$users[$id]['version'];audit('users-'.$action);return [200,['ok'=>true]];
  });api_json(...$result);
 }
 if($method==='GET'){
  $s=state();
  if($route==='pages')api_json(200,['pages'=>array_map(fn($p)=>['id'=>$p['id'],'title'=>$p['title']],site_config()['pages']),'canUndo'=>is_file(data_dir().'/backup.json')]);
  if($route==='page'){$id=$_GET['id']??'';$p=page_config($id);$html=render_page($id,$s);api_json(200,['id'=>$id,'title'=>$p['title'],'url'=>site_base().'/fara-admin/preview.php?page='.rawurlencode($id),'revision'=>revision($html),'fields'=>array_map(fn($f)=>array_intersect_key($f,array_flip(['id','scope','label','value'])),fields_for($html,$id,$s))]);}
  if($route==='menu'){$items=menu_items($s);api_json(200,['items'=>$items,'revision'=>revision(json_encode($items)),'destinations'=>site_config()['pages']]);}
  if($route==='images')api_json(200,['images'=>image_catalog()]);
  api_json(404,['error'=>'مسیر پیدا نشد.']);
 }
 $result=locked(function()use($route,$data){
  $s=state();
  if($route==='undo'){if(!is_file(data_dir().'/backup.json'))return [404,['error'=>'نسخه‌ای برای بازگردانی وجود ندارد.']];$next=read_json('backup');commit_state($next);audit('undo');return [200,['ok'=>true,'message'=>'آخرین ذخیره برگردانده شد.']];}
  if($route==='menu'){
   if(($data['revision']??'')!==revision(json_encode(menu_items($s))))return [409,['error'=>'منو تغییر کرده؛ دوباره بارگذاری کنید.']];
   $items=$data['items']??null;if(!is_array($items)||count($items)<1||count($items)>20)return [400,['error'=>'منو باید بین ۱ تا ۲۰ گزینه داشته باشد.']];$seen=[];
   foreach($items as &$item){if(!is_array($item)||!preg_match('~^[a-zA-Z0-9-]{1,80}$~D',(string)($item['id']??''))||isset($seen[$item['id']])||!is_string($item['label']??null)||trim($item['label'])===''||strlen($item['label'])>320||!is_string($item['href']??null)||strlen($item['href'])>2048||preg_match('~[\x00-\x20\\\\]~',$item['href']))return [400,['error'=>'مشخصات منو معتبر نیست.']];$seen[$item['id']]=true;$href=$item['href'];
    if(preg_match('~^(https?:|mailto:|tel:)~',$href)){if(preg_match('~^https?:~',$href)&&!filter_var($href,FILTER_VALIDATE_URL))return [400,['error'=>'لینک معتبر نیست.']];}
    else{$parts=explode('#',$href);$target=null;foreach(site_config()['pages'] as $p)if($p['href']===$parts[0])$target=$p;if(!$target||count($parts)>2||str_contains($href,'..'))return [400,['error'=>'مقصد لینک معتبر نیست.']];if(isset($parts[1])&&$parts[1]!==''&&!str_contains(render_page($target['id'],$s),'id="'.$parts[1].'"'))return [400,['error'=>'بخش مقصد وجود ندارد.']];}
    $item=['id'=>$item['id'],'label'=>trim($item['label']),'href'=>$href];
   }unset($item);$s['menu']=array_values($items);commit_state($s);audit('menu-save');return [200,['ok'=>true,'message'=>'منو در همهٔ صفحات ذخیره شد.']];
  }
  if($route==='save'){
   $id=$data['page']??'';page_config($id);$html=render_page($id,$s);if(($data['revision']??'')!==revision($html))return [409,['error'=>'صفحه تغییر کرده؛ دوباره آن را بارگذاری کنید.']];
   $changes=$data['changes']??null;if(!is_array($changes)||count($changes)>1000)return [400,['error'=>'ویرایش معتبر نیست.']];$fields=array_column(fields_for($html,$id,$s),null,'id');
   foreach($changes as $change){$f=$fields[$change['id']??'']??null;$value=$change['value']??null;if(!$f||!is_string($value)||strlen($value)>120000||preg_match('~[\x00-\x08\x0b\x0c\x0e-\x1f]~',$value))return [400,['error'=>'متن معتبر نیست.']];$prior=$s['edits'][$f['id']]??null;$s['edits'][$f['id']]=['original'=>$prior['original']??$f['value'],'value'=>trim($value),'label'=>$f['label'],'scope'=>$f['scope'],'nodePath'=>$prior['nodePath']??$f['parentPath'],'placement'=>$prior['placement']??($f['start']===$f['parentEnd']?'head':'tail')];}
   commit_state($s);audit('text-save');return [200,['ok'=>true,'message'=>'متن‌ها در سایت ذخیره شدند.']];
  }
  if($route==='image'){
   $item=null;foreach(image_catalog() as $i)if($i['id']===($data['id']??''))$item=$i;if(!$item)return [400,['error'=>'عکس پیدا نشد.']];if(($data['revision']??'')!==$item['revision'])return [409,['error'=>'عکس تغییر کرده؛ دوباره بارگذاری کنید.']];
   $bytes=base64_decode((string)($data['base64']??''),true);if($bytes===false||strlen($bytes)>10*1024*1024)return [400,['error'=>'عکس باید حداکثر ۱۰ مگابایت باشد.']];$info=@getimagesizefromstring($bytes);$ext=strtolower(pathinfo($item['id'],PATHINFO_EXTENSION));$expected=['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'][$ext]??'';if(!$info||$info['mime']!==$expected||$info[0]*$info[1]>40000000)return [400,['error'=>'قالب یا اندازهٔ عکس معتبر نیست.']];
   $folder=data_dir().'/media';if(!is_dir($folder))mkdir($folder,0700);$file=hash('sha256',$bytes).'.'.$ext;if(file_put_contents($folder.'/'.$file,$bytes)===false)throw new RuntimeException('Media write failed');chmod($folder.'/'.$file,0600);$s['images'][$item['id']]=['file'=>$file,'mime'=>$expected];commit_state($s);audit('image-save');return [200,['ok'=>true,'message'=>'عکس در همهٔ محل‌های استفاده ذخیره شد.']];
  }
  return [404,['error'=>'مسیر پیدا نشد.']];
 });api_json(...$result);
}catch(InvalidArgumentException|JsonException $e){api_json(400,['error'=>'اطلاعات درخواست معتبر نیست.']);}catch(Throwable $e){error_log('FARA CMS: '.$e->getMessage());api_json(500,['error'=>'ذخیره یا خواندن اطلاعات انجام نشد. مجوز پوشهٔ خصوصی و تنظیمات PHP هاست را بررسی کنید.']);}
