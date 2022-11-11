<?php
//https://v2.altmarkets.io/api/v2/peatio/public/markets
function altmarkets_api_query($method)
{
	$uri = "https://v2.altmarkets.io/api/v2/peatio/public/{$method}";

    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $execResult = strip_tags(curl_exec($ch));
    $ret = json_decode($execResult);

    return $ret;
}