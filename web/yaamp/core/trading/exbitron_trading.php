<?php
	#set_time_limit(60);
	
	require_once("/var/web/serverconfig.php");
	//require_once("/var/web/keys.php");
    // require_once("/var/web/yaamp/core/core.php");

	
	
 

function doExbitronTrading()
{
	$withdraw_min = 0.05;
	$exclude_cancel_order = array('VTL');
	$mysqli = mysql_connect();
	$exchange = 'exbitron';
	$updatebalances = true;
	$dtime = 1;

	$sell_ask_pct = 1.005;
	$min_sell_price = 0.00001;

	// echo ("Clear Old Orders\n");
	// $orders = graviex_api_query_post("orders/clear.json");
    // count_delay($dtime);
    // print_r ($orders);


	echo ("Check Balance\n");
	$balances = exbitron_api_get('account/balances');
    // print_r ($balances);
	if (!is_array($balances)) return;
	echo("Get Info Success\n");
	count_delay($dtime);

	foreach ($balances as $balance) 
	{
		$symbol = strtoupper($balance["currency"]);
		$amount = $balance["balance"];
		$onsell = $balance["locked"];
		if (($amount>0)||($onsell>0))
		{
			echo 'Method Symbol => ' .$symbol. ' Balance ' .$amount. ' Lock ' .$onsell;
			echo "\n";
		}
		if ($symbol == 'BTC') 
		{
			echo "Update BTC Balance\n";
			$sql = "UPDATE balances SET balance=$amount WHERE `name`='$exchange';";
			$result_update = $mysqli->query($sql);

			//Auto Witddraw
			// $withdraw_min = EXCH_AUTO_WITHDRAW;
			// $withdraw_fee = 0.0015;
			// $balance = $amount;
			// if(floatval($withdraw_min) > 0 && $balance >= ($withdraw_min + $withdraw_fee))
			// {
			// 	$btcaddr = YAAMP_BTCADDRESS;
			// 	$withdraw_fee = 0.0015;
			// 	if(floatval($withdraw_min) > 0 && $balance >= ($withdraw_min + $withdraw_fee))
			// 	{
			// 		echo "===== Auto Withdraw ====\n";
			// 		$btcaddr = YAAMP_BTCADDRESS;
			// 		$amount = $balace - $withdraw_fee;
			// 		echo "$exchange: withdraw $amount BTC to $btcaddr\n";

			// 		//count_delay($dtime);
			// 		$params = array("currency"=>"btc", "amount"=>$amount, "rid"=>$btcaddr);
			// 		$result = graviex_api_query_post('create_withdraw.json', $params);
			// 		print_r ($result);
			// 		echo "Reset Balance\n";
			// 		$sql = "UPDATE balances SET balance=0 WHERE `name`='$exchange';";
			// 		$result = $mysqli->query($sql);
			// 	}
			// }
		} else if (($symbol == 'DOGE')||($symbol == 'LTC')) {
			echo "Update DOGE Balance\n";
			$sql = "UPDATE markets SET balance=$amount,ontrade=$onsell,lasttraded=UNIX_TIMESTAMP() WHERE `name`='$exchange' AND coinid=(SELECT id FROM coins WHERE symbol = '$symbol');";
			$result_update = $mysqli->query($sql);
		}
		else
		{
			//Check Balance and create Order
			#if ($amount>1)
			{
				$sql = "SELECT * FROM coins WHERE symbol='$symbol' AND dontsell=0";
				$result = $mysqli->query($sql);
				if ($result->num_rows>0)
				{
					$coin = $result->fetch_object();
					echo $coin->symbol. "\n";

					//Update Balance
					$sql = "UPDATE markets SET balance=$amount,ontrade=$onsell,lasttraded=UNIX_TIMESTAMP() WHERE `name`='$exchange' AND coinid=$coin->id;";
					$result_update = $mysqli->query($sql);

					$uri = strtolower($coin->symbol).'btc';
			        $ticker = exbitron_api_query("markets/tickers");
					$sellamount = min(5000,bitcoinvaluetoa($amount));

					if ($coin->sellonbid)
		                $sellprice = $ticker["ticker"]["low"];
		            else
		                $sellprice = $ticker["ticker"]["high"];

			        $limit_price = $amount * $sellprice;

			        if ($limit_price>$min_sell_price)
			        {
			        	echo "Sell $symbol";
						
			            $params = array('market' => strtolower($symbol).'btc', 'side' => 'sell', 'price' => $sellprice, 'volume' => $sellamount);
			            print_r ($params);
			            $res = exbitron_api_post('market/orders', $params);
			            print_r ($res);
			            #$res = json_decode($res,true);
			            // if (isset($res->error))
			            // {
			            // 	$message = $res->error->message;
			            // 	if ((strpos($message,"Fee")!==false) || (strpos($message,"CreateOrderError")!==false))
			            // 	{
			            // 		$sql = "UPDATE markets SET message='$message',lasttraded=UNIX_TIMESTAMP(),message='$message' WHERE `name`='$exchange' AND coinid=$coin->id;";
			            // 		$result_update = $mysqli->query($sql);

			            // 	}
			            // 	else
			            // 	{
				        //     	system('mail "' .$exchange. ' ' .$symbol. ' ' .$message. '"');
				        //     	$sql = "UPDATE markets SET message='$message' WHERE `name`='$exchange' AND coinid=$coin->id;";
				        //     	#echo $sql;
				        //     	$result_update = $mysqli->query($sql);
				        //     }
			            // }
			            // else
			            // {
			            // 	$sql = "UPDATE markets SET lasttraded=UNIX_TIMESTAMP() WHERE `name`='$exchange' AND coinid=$coin->id;";
			            // 	$result_update = $mysqli->query($sql);
			            // }
			            count_delay($dtime);

			            
			        }
                
                }
			}

			// Get Deposit Addresss
			$sql = "SELECT * FROM markets WHERE `name`='$exchange' AND coinid IN (SELECT id FROM coins WHERE (symbol='$symbol' OR symbol2='$symbol'))";
			$result_market = $mysqli->query($sql);
			if ($result_market->num_rows>0)
			{
				$market = $result_market->fetch_object();
				// if ($market->deposit_address=="")
				{
					echo "Get Deposit Address: $symbol\n";
					$res = exbitron_api_get('account/deposit_address/' .strtolower($symbol));
					// print_r ($res);
					$address = $res['address'];
					if ($market->deposit_address!=$address) {
						echo "Update Deposite Address to $address\n";
						$sql = "UPDATE markets SET deposit_address='$address' WHERE `name`='$exchange' AND coinid IN (SELECT id FROM coins WHERE (symbol='$symbol' OR symbol2='$symbol'));";
						$result_update = $mysqli->query($sql);
					}
				}
		
			}
		}
	}

	
}
/*
function exbitron_api_query($method)
{
	$uri = "https://www.exbitron.com/api/v2/peatio/public/$method";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$res = curl_exec($ch);
	$obj = json_decode($res,true);

	return $obj;
}
*/
function exbitron_api_get($method, $req = array())
{
    // echo __METHOD__;
    require_once('/etc/yiimp/keys.php');
    $uri = "https://www.exbitron.com/api/v2/peatio/$method";

    $reqStr=print_r($req, true);
    sleep(1);

    // optional secret key
    if (empty(EXCH_EXBITRON_SECRET) && strpos($method, 'public') === FALSE) return FALSE;
    if (empty(EXCH_EXBITRON_KEY) && strpos($method, 'public') === FALSE) return FALSE;

    $req = auth();

    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $req);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $execResult = curl_exec($ch);


    $resData = json_decode($execResult,true);
    
    return $resData;
}

function exbitron_api_post($method, $postData = array())
{
    // echo __METHOD__;
    require_once('/etc/yiimp/keys.php');
    $uri = "https://www.exbitron.com/api/v2/peatio/$method";

    // optional secret key
    if (empty(EXCH_EXBITRON_SECRET) && strpos($method, 'public') === FALSE) return FALSE;
    if (empty(EXCH_EXBITRON_KEY) && strpos($method, 'public') === FALSE) return FALSE;

    $req = auth();

    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $req);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $execResult = curl_exec($ch);

    $resData = json_decode($execResult,true);
    
    return $resData;
}

function auth() {
	require_once('/etc/yiimp/keys.php');

    $apikey = EXCH_EXBITRON_KEY; // your API-key
    $apisecret = EXCH_EXBITRON_SECRET; // your Secret-key

	$nonce = time()*1000;
    $sign = hash_hmac('sha256', $nonce. '' .$apikey, $apisecret);
    
	$req = array();
	$req[] = "X-Auth-Apikey: $apikey";
	$req[] = "X-Auth-Nonce: $nonce";
    $req[] = "X-Auth-Signature: $sign";

	return $req;
}


function endsWith($name,$match)
{
    $match_len = strlen($match)*(-1);
    if (substr($name,$match_len)==$match)
    {
        return true;
    }
    else
    {
        return false;
    }
}



function mysql_connect()
{
	echo "Mysql Connect\n";
	$mysqli = new mysqli(YAAMP_DBHOST, YAAMP_DBUSER, YAAMP_DBPASSWORD,YAAMP_DBNAME);
	if ($mysqli->connect_errno) {
	    echo "Error: Unable to connect to MySQL." . PHP_EOL;
	    echo "Debugging errno: " .$mysqli->connect_error. PHP_EOL;
	    exit;
	}
	echo "Connected successfully\n";
	return $mysqli;
}

function count_delay($time=1)
{
	for ($i=1;$i<=$time;$i++)
	{
		//echo "Delay Time = " .$i. "\n";
		sleep(1);
	}
}


?>