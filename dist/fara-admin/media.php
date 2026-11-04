<?php
require __DIR__.'/core.php';
try{
 $id=$_GET['asset']??'';$found=false;foreach(image_catalog(false) as $i)if($i['id']===$id){$found=true;break;}
 if(!$found){http_response_code(404);exit;}$s=state();$image=$s['images'][$id]??null;
 $file=$image?data_dir().'/media/'.$image['file']:SITE_ROOT.'/'.$id;
 $types=['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'];header('Content-Type: '.($image['mime']??$types[strtolower(pathinfo($id,PATHINFO_EXTENSION))]));header('X-Content-Type-Options: nosniff');header('Cache-Control: no-cache');header('Content-Length: '.filesize($file));readfile($file);
}catch(Throwable $e){error_log('FARA media: '.$e->getMessage());http_response_code(500);}
