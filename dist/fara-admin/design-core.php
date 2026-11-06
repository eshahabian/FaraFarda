<?php
declare(strict_types=1);
// Structured design data is separate from the existing live text overlay.
function design_store(): array {try{return read_json('design',['entities'=>[],'media'=>[]]);}catch(JsonException $e){error_log('FARA: recovering design JSON');return read_json('design-recovery',['entities'=>[],'media'=>[]]);}}
function design_write(array $s): void {write_json('design-recovery',design_store());write_json('design',$s);}
function design_live(string $id): ?array {$e=design_entity($id);return $e['status']==='published'?$e['published']:null;}
function design_can(array $u,string $cap,string $id=''): bool {
 if($u['role']==='admin')return true;
 $p=$u['permissions']??[];
 if($cap==='pages')return $id!==''&&in_array($id,$p['pages']??[],true);
 return ($p[$cap]??false)===true && ($id===''||in_array($id,$p['pages']??[],true));
}
function design_require(array $u,string $cap,string $id=''): void {if(!design_can($u,$cap,$id))api_json(403,['error'=>'مجوز این عملیات را ندارید.']);}
function design_default(string $id): array {
 $base=['title'=>'','blocks'=>[],'text'=>[],'mode'=>'append','seo'=>['title'=>'','description'=>'','canonical'=>'','noindex'=>false,'image'=>''],'categories'=>[]];
 if($id==='shared')return $base+['header'=>[],'footer'=>[],'headerMode'=>'append','footerMode'=>'append','assetOverrides'=>[],'theme'=>[],'mobileTheme'=>[]];
 if($id==='menu')return ['items'=>menu_items(state())];
 if($id==='categories')return ['items'=>array_values(array_map(fn($p)=>['id'=>$p['id'],'title'=>$p['title'],'parent'=>'','active'=>true,'order'=>0],array_filter(site_config()['pages'],fn($p)=>$p['id']!=='home')))];
 if($id==='patterns')return ['items'=>[]];
 foreach(site_config()['pages'] as $p)if($p['id']===$id)return array_replace($base,['title'=>$p['title'],'href'=>$p['href'],'categories'=>$id==='home'?[]:[$id]]);
 throw new InvalidArgumentException('Unknown entity');
}
function design_entity(string $id,?array $store=null): array {$store??=design_store();return $store['entities'][$id]??['version'=>0,'draft'=>design_default($id),'published'=>null,'status'=>'published','history'=>[]];}
function design_text(mixed $v,int $max=10000): string {if(!is_string($v)||strlen($v)>$max||preg_match('~[\x00-\x08\x0b\x0c\x0e-\x1f]~',$v))throw new InvalidArgumentException('Invalid text');return $v;}
function design_link(mixed $v): string {
 $v=design_text($v,2048);if($v===''||preg_match('~[\x00-\x20\\\\]~',$v)||str_contains($v,'..')||str_starts_with($v,'//'))throw new InvalidArgumentException('Invalid link');
 if(preg_match('~^https://~',$v)){if(!filter_var($v,FILTER_VALIDATE_URL))throw new InvalidArgumentException('Invalid URL');return $v;}
 if(preg_match('~^(mailto:[^<>\s]+@[^<>\s]+|tel:\+?[0-9-]+|#[A-Za-z][\w-]*)$~D',$v))return $v;
 $parts=explode('#',$v);$known=false;foreach(site_config()['pages'] as $p)if($p['href']===$parts[0])$known=true;
 foreach(design_store()['entities'] as $e)if(($e['draft']['href']??null)===$parts[0])$known=true;
 if(!$known||count($parts)>2)throw new InvalidArgumentException('Unknown destination');
 if(isset($parts[1])){
  if(!preg_match('~^[A-Za-z][\w-]*$~D',$parts[1]))throw new InvalidArgumentException('Invalid anchor');$anchor=$parts[1];$found=false;
  foreach(site_config()['pages'] as $p)if($p['href']===$parts[0]&&preg_match('~\bid=["\x27]'.preg_quote($anchor,'~').'["\x27]~',file_get_contents(SITE_ROOT.'/'.$p['href'])))$found=true;
  foreach(design_store()['entities'] as $e)if(($e['draft']['href']??'')===$parts[0]){$walk=function($bs)use(&$walk,&$found,$anchor){foreach($bs as $b){if('d-'.$b['id']===$anchor)$found=true;$walk($b['children']);}};$walk($e['draft']['blocks']??[]);}
  if(!$found)throw new InvalidArgumentException('Unknown section');
 }
 return $v;
}
function design_style(mixed $s): array {
 if(!is_array($s))throw new InvalidArgumentException('Invalid style');$out=[];
 foreach($s as $k=>$v){if(in_array($k,['color','background'],true)&&is_string($v)&&preg_match('~^#[a-fA-F0-9]{6}$~D',$v))$out[$k]=$v;
 elseif(in_array($k,['padding','margin','gap','fontSize','width','height'],true)&&is_numeric($v)&&$v>=0&&$v<=1600)$out[$k]=(int)$v;
 elseif($k==='hidden'&&is_bool($v))$out[$k]=$v;
 elseif($k==='font'&&in_array($v,['inherit','sans-serif','serif'],true))$out[$k]=$v;
 else throw new InvalidArgumentException('Invalid style property');}return $out;
}
function design_blocks(mixed $blocks,int $depth=0,?int &$count=null,?array &$seen=null): array {
 $count??=0;$seen??=[];if(!is_array($blocks)||count($blocks)>150||$depth>6)throw new InvalidArgumentException('Block limit');$out=[];
 foreach($blocks as $b){if(++$count>300||!is_array($b)||!in_array($b['type']??'', ['section','columns','heading','text','image','gallery','banner','button','menu','list','faq','spacer','categories'],true))throw new InvalidArgumentException('Invalid block');
 $x=['id'=>design_text($b['id']??'',64),'type'=>$b['type'],'text'=>design_text($b['text']??''),'alt'=>design_text($b['alt']??'',500),'style'=>design_style($b['style']??[]),'mobile'=>design_style($b['mobile']??[])];
 if(!preg_match('~^[a-zA-Z][\w-]{0,63}$~D',$x['id'])||isset($seen[$x['id']]))throw new InvalidArgumentException('Block ID');$seen[$x['id']]=true;
 $x['link']=empty($b['link'])?'':design_link($b['link']);$x['media']=design_text($b['media']??'',100);
 if($x['media']!==''&&!isset(design_store()['media'][$x['media']]))throw new InvalidArgumentException('Unknown media');
 $crop=$b['crop']??[];if(!is_array($crop))throw new InvalidArgumentException('Crop');$x['crop']=[];foreach($crop as $k=>$v){if(!in_array($k,['x','y','ratio'],true)||!is_numeric($v)||$v<0||$v>($k==='ratio'?10:100)||($k==='ratio'&&$v<0.1))throw new InvalidArgumentException('Crop');$x['crop'][$k]=(float)$v;}
 $x['children']=design_blocks($b['children']??[],$depth+1,$count,$seen);if($x['children']&&!in_array($x['type'],['section','columns','gallery'],true))throw new InvalidArgumentException('Invalid nesting');$out[]=$x;
 }return $out;
}
function design_validate(string $id,array $d,array $old): array {
 if($id==='menu'){$items=$d['items']??[];if(!is_array($items)||count($items)>30)throw new InvalidArgumentException('Menu');return ['items'=>array_map(fn($x)=>['label'=>design_text($x['label'],320),'href'=>design_link($x['href'])],$items)];}
 if($id==='categories'){
 $items=$d['items']??[];if(!is_array($items)||count($items)>200)throw new InvalidArgumentException('Categories');$map=[];
 foreach($items as $x){$key=design_text($x['id']??'',64);if(!preg_match('~^[a-z][a-z0-9-]*$~D',$key)||isset($map[$key]))throw new InvalidArgumentException('Category ID');$map[$key]=['id'=>$key,'title'=>design_text($x['title'],320),'parent'=>design_text($x['parent']??'',64),'active'=>(bool)($x['active']??true),'order'=>(int)($x['order']??0)];}
 foreach($map as $x){$seen=[$x['id']];$p=$x['parent'];while($p!==''){if(!isset($map[$p])||in_array($p,$seen,true))throw new InvalidArgumentException('Category cycle');$seen[]=$p;$p=$map[$p]['parent'];}}
 foreach($old['items']??[] as $x)if(!isset($map[$x['id']]))throw new InvalidArgumentException('Deactivate categories instead of deleting references');return ['items'=>array_values($map)];
 }
 if($id==='patterns'){if(count($d['items']??[])>50)throw new InvalidArgumentException('Patterns');return ['items'=>array_map(fn($x)=>['title'=>design_text($x['title'],320),'blocks'=>design_blocks($x['blocks'])],$d['items']??[])];}
 $out=['title'=>design_text($d['title']??'',320),'href'=>$old['href']??'','blocks'=>design_blocks($d['blocks']??[]),'text'=>[],'mode'=>in_array($d['mode']??'', ['append','replace'],true)?$d['mode']:'append','categories'=>[]];
 $allowed=[];if(!str_starts_with($id,'page-'))foreach(fields_for(render_page($id==='shared'?'home':$id),$id==='shared'?'home':$id,state()) as $f)if(($id==='shared')===($f['scope']!=='page'))$allowed[$f['id']]=$f;
 foreach($d['text']??[] as $key=>$v){if(!isset($allowed[$key]))throw new InvalidArgumentException('Unknown text');$out['text'][$key]=design_text($v,120000);}
 $cats=design_entity('categories')['draft']['items'];foreach($d['categories']??[] as $cat){if(!in_array($cat,array_column($cats,'id'),true))throw new InvalidArgumentException('Unknown category');$out['categories'][]=$cat;}
 $seo=$d['seo']??[];$canonical=design_text($seo['canonical']??'',2048);if($canonical!==''&&(!str_starts_with($canonical,'https://')||!filter_var($canonical,FILTER_VALIDATE_URL)))throw new InvalidArgumentException('Canonical');
 $out['seo']=['title'=>design_text($seo['title']??'',320),'description'=>design_text($seo['description']??'',1000),'canonical'=>$canonical,'noindex'=>(bool)($seo['noindex']??false),'image'=>design_text($seo['image']??'',100)];
 if($out['seo']['image']!==''&&!isset(design_store()['media'][$out['seo']['image']]))throw new InvalidArgumentException('SEO image');
 if($id==='shared'){
  $out['theme']=design_style($d['theme']??[]);$out['mobileTheme']=design_style($d['mobileTheme']??[]);
  foreach(['header','footer'] as $part){$out[$part]=design_blocks($d[$part]??[]);$out[$part.'Mode']=($d[$part.'Mode']??'append')==='replace'?'replace':'append';}
  $out['assetOverrides']=[];$assets=array_column(design_catalog(),'id');foreach($d['assetOverrides']??[] as $asset=>$media){if(!in_array($asset,$assets,true)||!isset(design_store()['media'][$media]))throw new InvalidArgumentException('Asset');$out['assetOverrides'][$asset]=$media;}
 }return $out;
}
function design_asset(string $id): string {return site_base().'/fara-admin/design.php?media='.rawurlencode($id);}
function design_catalog(): array {
 $items=array_column(image_catalog(false),null,'id');$root=realpath(SITE_ROOT.'/assets');
 if($root)foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file){if(!$file->isFile()||$file->isLink()||!preg_match('~\.(png|jpe?g|webp)$~i',$file->getFilename()))continue;$key='assets/'.str_replace(DIRECTORY_SEPARATOR,'/',substr($file->getPathname(),strlen($root)+1));$items[$key]??=['id'=>$key,'label'=>$file->getFilename(),'pages'=>[],'url'=>site_base().'/'.$key];}
 $shared=design_entity('shared')['draft'];foreach($items as $key=>&$item){$info=@getimagesize(SITE_ROOT.'/'.$key);$item['width']=$info[0]??0;$item['height']=$info[1]??0;$item['draftMedia']=$shared['assetOverrides'][$key]??'';}unset($item);return array_values($items);
}
function design_preview_css(string $css,array $shared): string {
 return preg_replace_callback('~([^{}]+)\{([^{}]*)\}~',function($m)use($shared){$asset=null;$body=preg_replace_callback('~url\(\s*["\x27]?([^\s)"\x27]+)["\x27]?\s*\)~',function($u)use(&$asset,$shared){$path=preg_replace('~^(?:\.\./|\./)*~','',$u[1]);if(!preg_match('~^assets/[a-zA-Z0-9_./-]+\.(png|jpe?g|webp)$~i',$path))return $u[0];$asset=$path;return 'url("'.esc(isset($shared['assetOverrides'][$path])?design_asset($shared['assetOverrides'][$path]):site_base().'/'.$path).'")';},$m[2]);return $m[1].'{'.$body.($asset?';--editor-asset:"'.$asset.'";':'').'}';},$css);
}
function design_check_public_links(string $id,array $d): void {
 $links=[];$walk=function($v)use(&$walk,&$links){if(!is_array($v))return;foreach($v as $k=>$x){if($k==='link'&&is_string($x)&&$x!=='')$links[]=$x;if(is_array($x))$walk($x);}};$walk($d);if($id==='menu')foreach($d['items'] as $x)$links[]=$x['href'];
 foreach($links as $link){if(preg_match('~^(https:|mailto:|tel:)~',$link))continue;$parts=explode('#',$link);$href=$parts[0]?:($d['href']??'');$target=null;foreach(site_config()['pages'] as $p)if($p['href']===$href)$target=$p['id'];foreach(design_store()['entities'] as $key=>$e)if(($e['draft']['href']??null)===$href)$target=$key;if(!$target)throw new InvalidArgumentException('Unknown public destination');$html=design_render($target,$target===$id?$d:null);if($html===null)throw new InvalidArgumentException('Publish destination first');if(isset($parts[1])&&!preg_match('~\bid=["\x27]'.preg_quote($parts[1],'~').'["\x27]~',$html))throw new InvalidArgumentException('Public section not found');}
}
function design_preview_html(string $id,array $d,?array $shared=null): string {
 $shared??=$id==='shared'?$d:(design_live('shared')??[]);$html=design_render($id,$d,$shared)??'';
 $html=preg_replace_callback('~<link\b[^>]*href=["\x27]([^"\x27]+\.css(?:\?[^"\x27]*)?)["\x27][^>]*>~i',function($m)use($shared){$name=basename(parse_url($m[1],PHP_URL_PATH));if(!is_file(SITE_ROOT.'/'.$name))return $m[0];return '<style>'.design_preview_css(file_get_contents(SITE_ROOT.'/'.$name),$shared).'</style>';},$html);
 $html=preg_replace_callback('~(<style\b[^>]*>)([\s\S]*?)(</style>)~i',fn($m)=>$m[1].design_preview_css($m[2],$shared).$m[3],$html);
 $scan=text_scan($html,$id==='shared'?'home':$id);$by=[];foreach($scan['fields'] as $f)if(!$f['derived'])$by[$f['parentStart']][]=$f['id'];$patches=[];
 foreach($scan['nodes'] as $n)if(isset($by[$n['start']??-1]))$patches[]=['start'=>$n['start'],'end'=>$n['end'],'value'=>substr($n['raw'],0,-1).' data-editor-fields="'.esc(json_encode($by[$n['start']])).'">'];return patch($html,$patches);
}
function design_css(array $s): string {$out='';foreach($s as $k=>$v){$prop=['fontSize'=>'font-size','font'=>'font-family','hidden'=>'display'][$k]??$k;$out.=$prop.':'.($k==='hidden'?($v?'none':'revert'):(is_int($v)?$v.'px':$v)).';';}return $out;}
function design_blocks_html(array $blocks,array $doc=[]): string {
 $html='';foreach($blocks as $b){$id='d-'.$b['id'];$style='<style>#'.$id.'{'.design_css($b['style']).'}@media(max-width:700px){#'.$id.'{'.design_css($b['mobile']).'}}</style>';$a=' id="'.$id.'" data-design-id="'.esc($b['id']).'" class="design-block design-'.esc($b['type']).'"';$text=nl2br(esc($b['text']));$children=design_blocks_html($b['children'],$doc);$img=$b['media']?'<img style="'.(!empty($b['crop'])?'width:100%;object-fit:cover;aspect-ratio:'.($b['crop']['ratio']??1).';object-position:'.($b['crop']['x']??50).'% '.($b['crop']['y']??50).'%;':'').'" src="'.esc(design_asset($b['media'])).'" alt="'.esc($b['alt']).'" loading="lazy">':'';
 $body=match($b['type']){'heading'=>'<h2>'.$text.'</h2>','text'=>'<p>'.$text.'</p>','image'=>$img,'banner'=>$img.'<h2>'.$text.'</h2>','button'=>$b['link']?'<a class="design-button" href="'.esc(design_public_link($b['link'])).'">'.$text.'</a>':'','list'=>'<ul><li>'.implode('</li><li>',array_map('esc',explode("\n",$b['text']))).'</li></ul>','faq'=>'<details><summary>'.esc(explode("\n",$b['text'])[0]).'</summary><p>'.nl2br(esc(implode("\n",array_slice(explode("\n",$b['text']),1)))).'</p></details>','menu'=>design_menu_html(),'categories'=>design_categories_html(),default=>$children};$html.=$style.'<div'.$a.'>'.$body.'</div>';
 }return $html;
}
function design_public_link(string $v): string {if(str_starts_with($v,'#'))return site_base().'/'.($GLOBALS['design_href']??'index.html').$v;return preg_match('~^(https:|mailto:|tel:|#)~',$v)?$v:site_base().'/'.$v;}
function design_menu_html(bool $nav=true): string {
 $items=design_live('menu')['items']??menu_items(state());$h=$nav?'<nav class="design-navigation">':'<div style="--menu-count:'.count($items).'">';
 foreach($items as $x){$hidden=false;foreach(design_store()['entities'] as $e)if(($e['draft']['href']??'')===explode('#',$x['href'])[0]&&$e['status']!=='published')$hidden=true;if(!$hidden)$h.='<a href="'.esc(design_public_link($x['href'])).'">'.esc($x['label']).'</a>';}return $h.($nav?'</nav>':'</div>');
}
function design_categories_html(): string {
 $cats=design_live('categories')['items']??[];usort($cats,fn($a,$b)=>$a['order']<=>$b['order']);$pages=[];foreach(site_config()['pages'] as $p)$pages[$p['id']]=design_entity($p['id']);foreach(design_store()['entities'] as $id=>$e)if(str_starts_with($id,'page-'))$pages[$id]=$e;
 $h='<div class="design-categories">';foreach($cats as $c)if($c['active']){$h.='<details><summary>'.esc($c['title']).'</summary>';foreach($pages as $e){$d=$e['published']??$e['draft'];if($e['status']==='published'&&in_array($c['id'],$d['categories']??[],true))$h.='<a href="'.esc(design_public_link($d['href'])).'">'.esc($d['title']).'</a>';}$h.='</details>';}return $h.'</div>';
}
function design_document_html(string $id,array $d,string $html): string {
 if($d['text']??[]){$edits=state()['edits'];$fields=array_column(fields_for($html,$id,state()),null,'id');foreach($d['text'] as $k=>$v)if(isset($fields[$k]))$edits[$k]=['original'=>$fields[$k]['value'],'value'=>$v];$html=apply_edits($html,$id,$edits);}
 $blocks=design_blocks_html($d['blocks']??[],$d);if(($d['mode']??'append')==='replace')$html=preg_replace_callback('~(<main\b[^>]*>)[\s\S]*?(</main>)~',fn($m)=>$m[1].$blocks.$m[2],$html,1);else $html=str_replace('</main>',$blocks.'</main>',$html);
 $seo=$d['seo']??[];if(!empty($seo['title']))$html=preg_replace_callback('~<title>.*?</title>~s',fn()=>'<title>'.esc($seo['title']).'</title>',$html);
 $html=preg_replace('~<(?:meta\b[^>]*(?:name=["\x27](?:description|robots)["\x27]|property=["\x27]og:image["\x27])[^>]*|link\b[^>]*rel=["\x27]canonical["\x27][^>]*)>~i','',$html);
 $meta='<meta name="description" content="'.esc($seo['description']??'').'"><meta name="robots" content="'.(!empty($seo['noindex'])?'noindex':'index').',follow">';if(!empty($seo['canonical']))$meta.='<link rel="canonical" href="'.esc($seo['canonical']).'">';if(!empty($seo['image']))$meta.='<meta property="og:image" content="'.esc(design_asset($seo['image'])).'">';return str_replace('</head>',$meta.'</head>',$html);
}
function design_render(string $id,?array $override=null,?array $sharedOverride=null): ?string {
 $store=design_store();$e=design_entity($id,$store);$GLOBALS['design_href']=$e['draft']['href']??'index.html';if(in_array($e['status'],['trash','unpublished'],true)&&$override===null)return null;$d=$override??$e['published'];
 if(str_starts_with($id,'page-')){if(!$d)return null;$html=render_page('home');$html=preg_replace_callback('~(<main\b[^>]*>)[\s\S]*?(</main>)~',fn($m)=>$m[1].$m[2],$html,1);$html=preg_replace_callback('~<title>.*?</title>~s',fn()=>'<title>'.esc($d['title']).'</title>',$html);$html=str_replace('<head>','<head><base href="'.esc(site_base()).'/index.html">',$html);}
 else $html=render_page($id==='shared'?'home':$id);
 if($d&&$id!=='shared')$html=design_document_html($id,$d,$html);
 if($override!==null)$html=preg_replace_callback('~<img\b[^>]*\bsrc=["\x27](?:\.\./|\./)*(assets/[a-zA-Z0-9_./-]+)["\x27][^>]*>~i',fn($m)=>substr($m[0],0,-1).' data-editor-asset="'.esc($m[1]).'">',$html);
 $shared=$sharedOverride??design_live('shared');if($id==='shared'&&$override)$shared=$override;
 if(!empty($shared['text'])){$edits=[];foreach(fields_for($html,$id==='shared'?'home':$id,state()) as $f)if(isset($shared['text'][$f['id']]))$edits[$f['id']]=['original'=>$f['value'],'value'=>$shared['text'][$f['id']]];$html=apply_edits($html,$id==='shared'?'home':$id,$edits);}
 if($shared)$html=str_replace('</head>','<style>body{'.design_css($shared['theme']??[]).'}@media(max-width:700px){body{'.design_css($shared['mobileTheme']??[]).'}} </style></head>',$html);
 if($shared)foreach($shared['assetOverrides']??[] as $asset=>$media){$relative=($id!=='shared'&&dirname($e['draft']['href']??'')!=='.'?'../':'').$asset;$html=str_replace($relative,design_asset($media),$html);}
 if($shared)foreach(['header','footer'] as $part){$b=design_blocks_html($shared[$part]);if($shared[$part.'Mode']==='replace')$html=preg_replace_callback('~(<'.$part.'\b[^>]*>)[\s\S]*?(</'.$part.'>)~',fn($m)=>$m[1].$b.$m[2],$html,1);else $html=preg_replace_callback('~</'.$part.'>~',fn()=>$b.'</'.$part.'>',$html,1);}
 $html=preg_replace_callback('~(<nav\b[^>]*id="fara-primary"[^>]*>)[\s\S]*?</nav>~',fn($m)=>$m[1].design_menu_html(false).'</nav>',$html,1);
 if($id==='home'&&design_live('categories'))$html=preg_replace_callback('~(<section\b[^>]*id="sectors"[^>]*>)~',fn($m)=>$m[1].design_categories_html(),$html,1);
 return str_replace('</head>','<link rel="stylesheet" href="'.esc(site_base()).'/fara-admin/design-public.css"></head>',$html);
}
