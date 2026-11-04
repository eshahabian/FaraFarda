<?php
declare(strict_types=1);
// Runtime data is deliberately outside the public document root and Git checkout.
const SITE_ROOT = __DIR__ . '/..';
function config(): array { static $c; return $c ??= require __DIR__.'/config.php'; }
function data_dir(): string {
    static $dir; if ($dir) return $dir;
    $root=realpath(SITE_ROOT); $public=realpath(($_SERVER['DOCUMENT_ROOT'] ?? '') ?: SITE_ROOT) ?: $root;
    $dir=getenv('FARA_CMS_DATA_DIR') ?: dirname($public).'/.fara-cms-'.config()['installation'];
    if (!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Private storage is not writable');
    $dir=realpath($dir);
    if (!$dir || $dir===$public || str_starts_with(strtolower($dir).DIRECTORY_SEPARATOR,strtolower($public).DIRECTORY_SEPARATOR)) throw new RuntimeException('Storage must be outside document root');
    return $dir;
}
function read_json(string $name,array $default=[]): array { $f=data_dir().'/'.$name.'.json';return is_file($f)?json_decode(file_get_contents($f),true,512,JSON_THROW_ON_ERROR):$default; }
function write_json(string $name,array $value): void {
    $f=data_dir().'/'.$name.'.json'; $tmp=$f.'.'.bin2hex(random_bytes(6)).'.tmp';
    $bytes=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);if(file_put_contents($tmp,$bytes)!==strlen($bytes)){@unlink($tmp);throw new RuntimeException('Write failed');}
    chmod($tmp,0600);if(!rename($tmp,$f)){@unlink($tmp);throw new RuntimeException('Atomic write failed');}
}
function locked(callable $fn): mixed { $lock=fopen(data_dir().'/write.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Lock failed');try{return $fn();}finally{flock($lock,LOCK_UN);fclose($lock);} }
function initial_state(): array {return ['edits'=>[],'menu'=>null,'images'=>[],'version'=>0];}
function state(): array {return read_json('state',initial_state());}
function commit_state(array $next): void {
    $before=state();write_json('backup',$before);$next['version']=$before['version']+1;write_json('state',$next);
    // Retain images used by the current state and the single undo snapshot.
    $keep=[];foreach([$before,$next] as $s)foreach($s['images'] as $image)$keep[$image['file']]=true;
    foreach(glob(data_dir().'/media/*')?:[] as $file)if(preg_match('~^[a-f0-9]{64}\.(png|jpe?g|webp)$~D',basename($file))&&!isset($keep[basename($file)]))@unlink($file);
}
function site_config(): array {static $s;return $s??=json_decode(file_get_contents(__DIR__.'/site.json'),true,512,JSON_THROW_ON_ERROR);}
function page_config(string $id): array {foreach(site_config()['pages'] as $p)if($p['id']===$id)return $p;throw new InvalidArgumentException('صفحه پیدا نشد.');}
function esc(string $s): string {return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function revision(string $s): string {return hash('sha256',$s);}
function text_scan(string $html,string $page): array {
    $nodes=[['tag'=>'root','path'=>'','counts'=>[],'textCount'=>0,'raw'=>'']];$stack=[0];$fields=[];
    preg_match_all('~<!--[\s\S]*?-->|<![^>]*>|</?[A-Za-z][A-Za-z0-9:-]*(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>|[^<]+|<~',$html,$matches,PREG_OFFSET_CAPTURE);
    foreach($matches[0] as [$raw,$offset]){
        $parentId=end($stack);$parent=&$nodes[$parentId];
        if(in_array($parent['tag'],['script','style'],true)&&!str_starts_with(strtolower($raw),'</'.$parent['tag']))continue;
        if(str_starts_with($raw,'<!--')||str_starts_with($raw,'<!'))continue;
        if($raw[0]==='<'){
            if(!preg_match('~^<(/?)([\w:-]+)~',$raw,$m))continue;$tag=strtolower($m[2]);
            if($m[1]){for($j=count($stack)-1;$j>0;$j--)if($nodes[$stack[$j]]['tag']===$tag){$nodes[$stack[$j]]['closeStart']=$offset;$stack=array_slice($stack,0,$j);break;}continue;}
            $nth=$parent['counts'][$tag]??0;$parent['counts'][$tag]=$nth+1;
            $node=['tag'=>$tag,'path'=>$parent['path'].'/'.$tag.'['.$nth.']','counts'=>[],'textCount'=>0,'start'=>$offset,'end'=>$offset+strlen($raw),'raw'=>$raw];$nodes[]=$node;$nodeId=count($nodes)-1;
            if(!preg_match('~^(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)$~',$tag)&&!str_ends_with($raw,'/>'))$stack[]=$nodeId;continue;
        }
        $nth=$parent['textCount']++;$skip=false;$shared=null;$menu=false;$toc=false;
        foreach($stack as $i){$n=$nodes[$i];if(in_array($n['tag'],['head','script','style','noscript','svg','button'],true)||preg_match('~aria-hidden\s*=\s*["\x27]true~',$n['raw']))$skip=true;if(in_array($n['tag'],['header','footer'],true)&&$shared===null)$shared=$n;if(preg_match('~class=["\x27][^"\x27]*fara-sector-strip~',$n['raw']))$menu=true;if(preg_match('~class=["\x27][^"\x27]*profile-contents~',$n['raw']))$toc=true;}
        $value=trim(html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8'));if($skip||!preg_match('~[\p{L}\p{N}]~u',$value))continue;
        $scope=$shared?$shared['tag']:'page';$relative=$shared?substr($parent['path'],strlen($shared['path'])):$parent['path'];
        $key=($shared?'shared:'.$scope:$page).$relative.'/text['.$nth.']';
        $labels=['h1'=>'عنوان اصلی','h2'=>'عنوان بخش','h3'=>'عنوان فرعی','p'=>'متن','li'=>'گزینهٔ فهرست','a'=>'متن لینک','small'=>'متن کوتاه','strong'=>'متن برجسته'];
        $fields[]=['id'=>$key,'scope'=>$scope,'label'=>$labels[$parent['tag']]??'متن','value'=>$value,'derived'=>$menu||($toc&&$parent['tag']==='a'&&preg_match('~href=["\x27]#profile-~',$parent['raw'])),'menu'=>$menu,'start'=>$offset,'end'=>$offset+strlen($raw),'raw'=>$raw,'parentStart'=>$parent['start'],'parentEnd'=>$parent['end'],'parentPath'=>$relative];
    }
    unset($parent);return ['fields'=>$fields,'nodes'=>$nodes];
}
function patch(string $html,array $replacements): string {usort($replacements,fn($a,$b)=>$b['start']<=>$a['start']);foreach($replacements as $r)$html=substr($html,0,$r['start']).$r['value'].substr($html,$r['end']);return $html;}
function apply_edits(string $html,string $page,array $edits): string {
    $scan=text_scan($html,$page);$replacements=[];$derived=[];$known=[];
    foreach($scan['fields'] as $f){$known[$f['id']]=true;if($f['derived']||!isset($edits[$f['id']]))continue;$edit=$edits[$f['id']];if($f['value']!==$edit['original']&&$f['value']!==$edit['value'])continue;
        preg_match('~^\s*~u',$f['raw'],$pre);preg_match('~\s*$~u',$f['raw'],$post);$replacements[]=['start'=>$f['start'],'end'=>$f['end'],'value'=>$pre[0].esc($edit['value']).$post[0]];
        if($f['scope']==='page')$derived[$f['value']]=$edit['value'];
    }
    foreach($scan['fields'] as $f)if($f['derived']&&!$f['menu']&&isset($derived[$f['value']]))$replacements[]=['start'=>$f['start'],'end'=>$f['end'],'value'=>esc($derived[$f['value']])];
    // An emptied text node remains editable even after it disappears from the scanner.
    foreach($edits as $id=>$edit){if(isset($known[$id])||!isset($edit['nodePath'])||$edit['value']==='')continue;if(!str_starts_with($id,$page.'/')&&!str_starts_with($id,'shared:'))continue;
        foreach($scan['nodes'] as $n){$nodeKey=$n['path'];if(str_starts_with($id,'shared:')){$scope=str_starts_with($id,'shared:footer')?'footer':'header';$pos=strpos($nodeKey,'/'.$scope.'[');if($pos===false)continue;$nodeKey=preg_replace('~^/[^/]+~','',substr($nodeKey,$pos));}if($nodeKey!==$edit['nodePath'])continue;$at=($edit['placement']??'tail')==='head'?$n['end']:($n['closeStart']??$n['end']);$replacements[]=['start'=>$at,'end'=>$at,'value'=>esc($edit['value'])];break;}
    }
    return patch($html,$replacements);
}
function menu_items(array $s): array {return $s['menu']??site_config()['menu']['items'];}
function render_page(string $id,?array $s=null): string {
    $s??=state();$p=page_config($id);$html=file_get_contents(SITE_ROOT.'/'.$p['href']);$html=apply_edits($html,$id,$s['edits']);
    if($s['menu']!==null){$links='';$base=dirname($p['href'])==='.'?'':'../';foreach(menu_items($s) as $item){$href=preg_match('~^(https?:|mailto:|tel:)~',$item['href'])?$item['href']:$base.$item['href'];$links.='<a href="'.esc($href).'"'.(explode('#',$item['href'])[0]===$p['href']?' aria-current="page"':'').'>'.esc($item['label']).'</a>';}
        $html=preg_replace_callback('~(<nav\b[^>]*\bid="fara-primary"[^>]*>)[\s\S]*?(</nav>)~',fn($m)=>$m[1].'<div style="--menu-count:'.count(menu_items($s)).'">'.$links.'</div>'.$m[2],$html,1);
    }
    // Respect intentional line breaks without touching public source files.
    $html=str_replace('</head>','<style>main p,main li,main h1,main h2,main h3,.fara-footer p{white-space:pre-line}</style></head>',$html);
    return $html;
}
function fields_for(string $html,string $page,array $s): array {
    $scan=text_scan($html,$page);$fields=array_values(array_filter($scan['fields'],fn($f)=>!$f['derived']));$known=array_column($fields,null,'id');
    foreach($s['edits'] as $id=>$e){if($e['value']!==''||isset($known[$id])||(!str_starts_with($id,$page.'/')&&!str_starts_with($id,'shared:')))continue;$fields[]=['id'=>$id,'scope'=>$e['scope'],'label'=>$e['label'],'value'=>'','parentPath'=>$e['nodePath'],'start'=>0,'parentEnd'=>-1];}
    return $fields;
}
function image_catalog(bool $withRevision=true): array {
    $found=[];$assets=realpath(SITE_ROOT.'/assets');
    $collect=function(string $html,string $base,?string $page)use(&$found,$assets){preg_match_all('~(?:src=["\x27]([^"\x27]+)["\x27]|url\(\s*["\x27]?([^\s)\x27";]+))~',$html,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);foreach($matches as $m){$href=($m[1][0]??'')?:($m[2][0]??'');if(!preg_match('~\.(png|jpe?g|webp)(?:[?#].*)?$~i',$href))continue;$file=realpath($base.'/'.preg_split('~[?#]~',$href)[0]);if(!$file||!str_starts_with($file,$assets.DIRECTORY_SEPARATOR))continue;$id='assets/'.str_replace(DIRECTORY_SEPARATOR,'/',substr($file,strlen($assets)+1));$label=basename($file);if(($m[1][0]??'')!==''){$start=strrpos(substr($html,0,$m[0][1]),'<');$tag=substr($html,$start,strpos($html,'>',$m[0][1])-$start+1);if(preg_match('~\balt=["\x27]([^"\x27]+)~',$tag,$alt))$label=html_entity_decode($alt[1],ENT_QUOTES|ENT_HTML5,'UTF-8');}elseif(basename($file)==='translog-hero.jpg')$label='بنر اصلی صفحهٔ اول';if(!isset($found[$id]))$found[$id]=['id'=>$id,'label'=>$label,'pages'=>[]];if($page&&!in_array($page,$found[$id]['pages'],true))$found[$id]['pages'][]=$page;}};
    foreach(site_config()['pages'] as $p)$collect(file_get_contents(SITE_ROOT.'/'.$p['href']),dirname(SITE_ROOT.'/'.$p['href']),$p['id']);
    foreach(glob(SITE_ROOT.'/*.css') as $file)$collect(file_get_contents($file),SITE_ROOT,null);
    $s=state();foreach($found as $id=>&$item){$file=isset($s['images'][$id])?data_dir().'/media/'.$s['images'][$id]['file']:SITE_ROOT.'/'.$id;if($withRevision)$item['revision']=hash_file('sha256',$file);$item['url']=site_base().'/'.$id;}unset($item);return array_values($found);
}
function site_base(): string {return rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/');}
function is_local(): bool {return in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)&&in_array(explode(':',$_SERVER['HTTP_HOST']??'')[0],['127.0.0.1','localhost'],true);}
function https(): bool {return (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||($_SERVER['SERVER_PORT']??'')==='443';}
function session_boot(): void {
    if(PHP_SAPI==='cli')return;
    if(!https()&&!is_local()){http_response_code(403);exit('برای ورود به پنل، سایت را با HTTPS باز کنید.');}
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.use_trans_sid','0');
    session_name('FARA_'.config()['installation']);session_set_cookie_params(['lifetime'=>0,'path'=>site_base().'/fara-admin/','secure'=>https(),'httponly'=>true,'samesite'=>'Strict']);session_start();
    if(isset($_SESSION['last'])&&(time()-$_SESSION['last']>1800||time()-($_SESSION['created']??0)>28800))$_SESSION=[];
    $_SESSION['last']=time();$_SESSION['created']??=time();$_SESSION['csrf']??=bin2hex(random_bytes(32));
}
function current_user(): ?array {$id=$_SESSION['uid']??null;if(!$id)return null;$users=read_json('users');$u=$users[$id]??null;return $u&&$u['active']&&($u['version']??0)===($_SESSION['uv']??-1)?$u+['id'=>$id]:null;}
function public_user(array $u): array {return array_intersect_key($u,array_flip(['id','username','role','active','mustChange']));}
function api_json(int $status,array $data): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function audit(string $action): void {$events=read_json('audit');$events[]=['time'=>gmdate('c'),'user'=>current_user()['username']??'setup','action'=>$action];write_json('audit',array_slice($events,-300));}
