<?php

echo "<a href='/coin'>Back to coin list</a>";

$this->widget('UniForm');

echo CUFHtml::beginForm();
echo CUFHtml::errorSummary($coin);
echo CUFHtml::openTag('fieldset', array('class'=>'inlineLabels'));

if(!$coin->installed)
{
	echo CUFHtml::openActiveCtrlHolder($coin, 'name');
	echo CUFHtml::activeLabelEx($coin, 'name');
	echo CUFHtml::activeTextField($coin, 'name');
	echo "<p class='formHint2'></p>";
	echo CUFHtml::closeCtrlHolder();

	echo CUFHtml::openActiveCtrlHolder($coin, 'symbol');
	echo CUFHtml::activeLabelEx($coin, 'symbol');
	echo CUFHtml::activeTextField($coin, 'symbol');
	echo "<p class='formHint2'></p>";
	echo CUFHtml::closeCtrlHolder();

	echo CUFHtml::openActiveCtrlHolder($coin, 'algo');
	echo CUFHtml::activeLabelEx($coin, 'algo');
	$ListAlgos = array();
	$db_algos = getdbolist('db_algos');
	foreach ($db_algos as $algo) 
	{
		$NameAlgo = $algo->name;
		$ListAlgos[$NameAlgo] = $NameAlgo;
	}
	echo CUFHtml::dropDownList('db_coins[algo]', $coin->algo, $ListAlgos, array(
		'style' => 'border: none; height: 26px; width:38%',
		'class' => 'textInput tweetnews-input'
	));
	echo '<p class="formHint2">Required all lower case</p>';
	echo CUFHtml::closeCtrlHolder();

	echo CUFHtml::openActiveCtrlHolder($coin, 'image');
	echo CUFHtml::activeLabelEx($coin,'image');
	echo CUFHtml::activeTextField($coin, 'image');
	echo "<p class='formHint2'></p>";
	echo CUFHtml::closeCtrlHolder();
}

echo CUFHtml::openActiveCtrlHolder($coin, 'link_bitcointalk');
echo CUFHtml::activeLabelEx($coin, 'link_bitcointalk');
echo CUFHtml::activeTextField($coin, 'link_bitcointalk');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_github');
echo CUFHtml::activeLabelEx($coin, 'link_github');
echo CUFHtml::activeTextField($coin, 'link_github');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_site');
echo CUFHtml::activeLabelEx($coin, 'link_site');
echo CUFHtml::activeTextField($coin, 'link_site');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_exchange');
echo CUFHtml::activeLabelEx($coin, 'link_exchange');
echo CUFHtml::activeTextField($coin, 'link_exchange');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_explorer');
echo CUFHtml::activeLabelEx($coin, 'link_explorer');
echo CUFHtml::activeTextField($coin, 'link_explorer');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_twitter');
echo CUFHtml::activeLabelEx($coin, 'link_twitter');
echo CUFHtml::activeTextField($coin, 'link_twitter');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_discord');
echo CUFHtml::activeLabelEx($coin, 'link_discord');
echo CUFHtml::activeTextField($coin, 'link_discord');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::openActiveCtrlHolder($coin, 'link_facebook');
echo CUFHtml::activeLabelEx($coin, 'link_facebook');
echo CUFHtml::activeTextField($coin, 'link_facebook');
echo "<p class='formHint2'></p>";
echo CUFHtml::closeCtrlHolder();

echo CUFHtml::closeTag('fieldset');
showSubmitButton($update? 'Save': 'Create');
echo CUFHtml::endForm();

echo "<br><br><br><br><br>";
echo "<br><br><br><br><br>";


