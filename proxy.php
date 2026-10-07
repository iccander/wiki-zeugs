<?php
header('Content-Type: application/json; charset=utf-8');
$q=trim($_GET['q']??'');
if(!$q){echo '[]';exit;}
$url='https://lobid.org/gnd/search?'.http_build_query(['q'=>$q,'filter'=>'type:Person','size'=>20,'format'=>'json:suggest']);
$ch=curl_init($url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
$response=curl_exec($ch);
curl_close($ch);
if($response===false){http_response_code(502);echo '[]';exit;}
$data=json_decode($response,true);
if(!is_array($data)){http_response_code(502);echo '[]';exit;}
$result=[];
foreach($data as $item)
	$result[]=['label'=>$item['label'],'id'=>basename($item['id'])];
echo json_encode($result);
