<?php
require __DIR__.'/core.php';require __DIR__.'/design-core.php';
header('Content-Type: application/xml; charset=utf-8');header('X-Content-Type-Options: nosniff');
// Set FARA_PUBLIC_URL on the server; never trust the request Host for canonical URLs.
$base=rtrim(getenv('FARA_PUBLIC_URL')?:'','/');if(!filter_var($base,FILTER_VALIDATE_URL)||!str_starts_with($base,'https://')){http_response_code(503);exit('Public URL is not configured');}
$s=design_store();$pages=[];foreach(site_config()['pages'] as $p)$pages[$p['id']]=$p['href'];foreach($s['entities'] as $id=>$e)if(str_starts_with($id,'page-'))$pages[$id]=$e['draft']['href'];
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';foreach($pages as $id=>$href){$e=design_entity($id,$s);if($e['status']!=='published'||($e['published']['seo']['noindex']??false))continue;echo '<url><loc>'.esc($e['published']['seo']['canonical']??'') . (($e['published']['seo']['canonical']??'')===''?esc($base.'/'.$href):'').'</loc></url>';}echo '</urlset>';
