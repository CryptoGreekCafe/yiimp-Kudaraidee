<?php

$user = getuserparam(getparam('address'));
if(!$user) return;

$userid = intval($user->id);
$coinid = intval($user->coinid);
if ($coinid) {
	$coin = getdbo('db_coins', $coinid);
}

echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Miners: {$user->username}</div>";
echo "<div class='main-left-inner'>";

echo '<table class="dataGrid2">';
echo "<thead>";
echo "<tr>";
echo "<th align=left>Summary</th>";
echo "<th align=right width=80>Workers</th>";
echo "<th align=right width=80>Hashrate*</th>";
echo "<th align=right width=60>TTF</th>";
echo "</tr>";
echo "</thead>";

foreach(yaamp_get_algos() as $algo)
{
	if (!YAAMP_ALLOW_EXCHANGE && isset($coin) && $coin->algo != $algo) continue;

	$user_pool_rate = yaamp_user_rate($userid, $algo);

	$pool_hash = yaamp_coin_rate($coin->id);
	$user_ttf  = $user_pool_rate ? $coin->difficulty * 0x100000000 / $pool_hash : 0;
	$user_ttf  = $user_ttf ? sectoa2($user_ttf) : '-';

	$user_pool_rate = $user_pool_rate? Itoa2($user_pool_rate).'h/s': '-';
	
	$minercount = getdbocount('db_workers', "userid=$userid AND algo=:algo", array(':algo'=>$algo));

	echo '<tr class="ssrow">';
	echo '<td><b>'.$algo.'</b></td>';
	echo '<td align="right" >'.$minercount.'</td>';
	echo '<td align="right" width="100"><b>'.$user_pool_rate.'</b></td>';
	echo '<td align="right" width="100">'.$user_ttf.'</td>';
	echo '</tr>';
}

echo "</table>";
////////////////////////////////////////////////////////////////////////////////

$minercount = getdbolist('db_workers', "userid=$user->id order by password");
if(count($minercount))
{
	echo "<br>";
	echo '<table class="dataGrid2">';
	echo "<thead>";
	echo "<tr>";
	echo "<th align=left>Mode</th>";
	echo "<th align=right width=80>Workers</th>";
	echo "<th align=right>Shares</th>";
	echo "<th align=right width=80>Hashrate*</th>";
	echo "<th align=right width=60>TTF</th>";
	echo "</tr>";
	echo "</thead>";

	foreach(yaamp_get_algos() as $algo)
	{
		if (!YAAMP_ALLOW_EXCHANGE && isset($coin) && $coin->algo != $algo) continue;

		$user_shared_rate = yaamp_user_shared_rate($userid, $algo);
		$user_solo_rate = yaamp_user_solo_rate($userid, $algo);
		
		$pool_shared_hash = yaamp_pool_shared_rate($algo);
		$user_shared_ttf  = $user_shared_rate ? $coin->difficulty * 0x100000000 / $pool_shared_hash : 0;
		$user_shared_ttf  = $user_shared_ttf ? sectoa2($user_shared_ttf) : '';

		$pool_solo_hash = yaamp_pool_solo_rate($algo);
		$user_solo_ttf  = $user_solo_rate ? $coin->difficulty * 0x100000000 / $pool_solo_hash : 0;
		$user_solo_ttf  = $user_solo_ttf ? sectoa2($user_solo_ttf) : '';


		$user_shared_rate = $user_shared_rate? Itoa2($user_shared_rate).'h/s': '-';
		$user_solo_rate = $user_solo_rate? Itoa2($user_solo_rate).'h/s': '-';
	
		$shared_minercount = getdbocount('db_workers', "userid=$userid AND algo=:algo and password not like '%m=solo%'", array(':algo'=>$algo));
		$solo_minercount = getdbocount('db_workers',"algo=:algo and userid=$userid and password like '%m=solo%'",array(':algo'=>$algo));

		if ($shared_minercount)
		{
			if (YAAMP_ALLOW_EXCHANGE || !$user->coinid) 
			{

				$user_shared_shares = controller()->memcache->get_database_scalar("wallet_user_shared_shares-$userid-$algo",
					"SELECT SUM(difficulty) FROM shares WHERE valid AND userid=$userid AND algo=:algo AND solo=0", array(':algo'=>$algo));
				if(!$user_shared_shares && !$shared_minercount) continue;

				$total_shared_shares = controller()->memcache->get_database_scalar("wallet_total_shared_shares-$algo",
					"SELECT SUM(difficulty) FROM shares WHERE valid AND algo=:algo AND solo=0", array(':algo'=>$algo));

			} 
			else 
			{
				$user_shared_shares = controller()->memcache->get_database_scalar("wallet_user_shared_shares-$algo-$coinid-$userid",
					"SELECT SUM(difficulty) FROM shares WHERE valid AND userid=$userid AND coinid=$coinid AND algo=:algo AND solo=0", array(':algo'=>$algo));
				if(!$user_shared_shares) continue;

				$total_shared_shares = controller()->memcache->get_database_scalar("wallet_coin_shared_shares-$coinid",
					"SELECT SUM(difficulty) FROM shares WHERE valid AND coinid=$coinid AND algo=:algo AND solo=0", array(':algo'=>$algo));
			}
	
			if(!$total_shared_shares) continue;
			$percent_shared_shares = round($user_shared_shares * 100 / $total_shared_shares, 4);
			$percent_shared_shares = $percent_shared_shares? Itoa2($percent_shared_shares).'%': '-';
		}

		if ($shared_minercount)
		{
			echo '<tr class="ssrow">';
			echo '<td><b>Shared</b></td>';
			echo '<td align="right" >'.$shared_minercount.'</td>';
			echo '<td align="right" width="100">'.$percent_shared_shares.'</td>';
			echo '<td align="right" width="100"><b>'.$user_shared_rate.'</b></td>';
			echo '<td align="right" width="100">'.$user_shared_ttf.'</td>';
			echo '</tr>';
		}
		
		if ($solo_minercount)
		{
			echo '<tr class="ssrow">';
			echo '<td><b>Solo</b></td>';
			echo '<td align="right" >'.$solo_minercount.'</td>';
		 	echo '<td align="right" width="100">-</td>';
			echo '<td align="right" width="100"><b>'.$user_solo_rate.'</b></td>';
			echo '<td align="right" width="100">'.$user_solo_ttf.'</td>';
			echo '</tr>';
		}
	}
	echo "</table>"; 
}


////////////////////////////////////////////////////////////////////////////////

$workers = getdbolist('db_workers', "userid=$user->id order by password");
if(count($workers))
{
	echo "<br>";
	echo "<table  class='dataGrid2'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th align=left>Details</th>";
	if ($this->admin) echo "<th>IP</th>";
	echo "<th align=left>Password</th>";
	echo "<th align=left>Algo</th>";
	echo "<th align=right>Diff</th>";
	echo "<th align=right title='extranonce.subscribe'>ES**</th>";
	echo "<th align=right width=80>Hashrate*</th>";
	echo "<th align=right width=60>Shares/Min*</th>";
	echo "</tr>";
	echo "</thead>";

	foreach($workers as $worker)
	{
		$user_rate1 = yaamp_worker_rate($worker->id, $worker->algo);
		
		$user_rate1 = $user_rate1? Itoa2($user_rate1).'h/s': '';

		$version = substr($worker->version, 0, 20);
		$password = substr($worker->password, 0, 32);
		
		$name = $worker->worker;

		$subscribe = Booltoa($worker->subscribe);

		$t = time() - 60;
		$shares_per_minute = getdbocount('db_shares',"algo=:algo and userid=$user->id and workerid=$worker->id and time>=$t",array(':algo'=>$worker->algo));

		echo '<tr class="ssrow">';
		echo '<td title="'.$worker->version.'"><b>Version:</b> '.$version.' <br> <b>Worker Name:</b> '.$name.' </td>';
		if ($this->admin) echo "<td>{$worker->ip}</td>";
		echo '<td title="'.$worker->password.'">'.$password.'</td>';
		echo '<td>'.$worker->algo.'</td>';
		echo '<td align="right">'.$worker->difficulty.'</td>';
		echo '<td align="right">'.$subscribe.'</td>';
		echo '<td align="right">'.$user_rate1.'</td>';
		echo '<td align="center" title="">'.$shares_per_minute.'</td>';
		echo '</tr>';
	}

	echo "</table>";
}

echo "</div>";

echo "<p style='font-size: .8em'>
		&nbsp;* approximate from the last 5 minutes submitted shares<br>
		&nbsp;** extranonce.subscribe<br>
		</p>";

echo "</div><br>";








