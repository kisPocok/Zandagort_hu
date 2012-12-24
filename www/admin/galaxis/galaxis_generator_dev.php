<?php

// TODO szedd ki, ha használni szeretnéd!
//exit();

//error_reporting(0);
error_reporting(E_ALL ^ E_NOTICE);
set_time_limit(30);
$font_cim='/www/zandagort/www/img/arial.ttf';

$meret=500;$felmeret=$meret/2;$kep_zoom=5;
$kep=imagecreatetruecolor($meret,$meret);
$feher=imagecolorallocate($kep,255,255,255);
$sarga=imagecolorallocate($kep,255,255,200);
$piros=imagecolorallocate($kep,255,0,0);
$kek=imagecolorallocate($kep,100,160,255);
$zold=imagecolorallocate($kep,0,100,0);
$v_zold=imagecolorallocate($kep,0,200,0);
$regio_szinek[1]=imagecolorallocate($kep,255,20,40);
$regio_szinek[2]=imagecolorallocate($kep,247,91,51);
$regio_szinek[3]=imagecolorallocate($kep,255,180,0);
$regio_szinek[4]=imagecolorallocate($kep,255,255,0);
$regio_szinek[5]=imagecolorallocate($kep,0,255,0);
$regio_szinek[6]=imagecolorallocate($kep,0,255,200);
$regio_szinek[7]=imagecolorallocate($kep,20,160,255);
$regio_szinek[8]=imagecolorallocate($kep,40,80,255);
$regio_szinek[9]=imagecolorallocate($kep,160,30,255);
$regio_szinek[10]=imagecolorallocate($kep,255,64,128);
$regio_szinek[11]=imagecolorallocate($kep,255,128,192);
$regio_szinek[12]=imagecolorallocate($kep,255,255,255);

//rajz racs
$hatar = 80000;
$skala = 200;
for ($x=-$hatar; $x<=$hatar; $x+=10000) {
	imageline($kep,round($felmeret+$x/$skala),round($felmeret-$hatar/$skala),round($felmeret+$x/$skala),round($felmeret+$hatar/$skala),($x==-2*$eltolas_x)?$v_zold:$zold);
	imageline($kep,round($felmeret-$hatar/$skala),round($felmeret+$x/$skala),round($felmeret+$hatar/$skala),round($felmeret+$x/$skala),($x==-2*$eltolas_y)?$v_zold:$zold);
	//imagettftext($kep,8,90,round($felmeret+$x/$skala+4),round($felmeret-$hatar/$skala-5),$zold,$font_cim,str_pad($x/2+$eltolas_x,6,' ',STR_PAD_LEFT));
	//imagettftext($kep,8,0,round($felmeret-$hatar/$skala-35),round($felmeret+$x/$skala+4),$zold,$font_cim,str_pad($x/2+$eltolas_y,6,' ',STR_PAD_LEFT));
	//imagettftext($kep,8,90,round($felmeret+$x/$skala+4),round($felmeret+$hatar/$skala+38),$zold,$font_cim,str_pad($x/2+$eltolas_x,6,' ',STR_PAD_LEFT));
	//imagettftext($kep,8,0,round($felmeret+$hatar/$skala+5),round($felmeret+$x/$skala+4),$zold,$font_cim,str_pad($x/2+$eltolas_y,6,' ',STR_PAD_LEFT));
}

$bolygok_szama=0;
$reg_bolygok_szama=0;
$max_hexa_tav=360;
$hexa_kep_zoom=1/200;

$zoom_x=round(125*sqrt(3));
$zoom_y=125*2;

function put_bolygo($x, $y, $regio, $meret, $zona) {
	global $suruseg, $bolygok, $bolygok_szama, $reg_bolygok_szama;
	if (!isset($bolygok[$x][$y])) {
		$bolygok_szama++;
		if ($zona) {
			$reg_bolygok_szama++;
		}
		$bolygok[$x][$y]=array($regio, $meret, $zona);
		$suruseg[$x/40][$y/40]++;
	}
}
function remove_bolygo($hx,$hy) {
	global $bolygok,$bolygok_szama,$reg_bolygok_szama;
	if (isset($bolygok[$hx][$hy])) {
		$bolygok_szama--;
		if ($bolygok[$hx][$hy][2]) $reg_bolygok_szama--;
		unset($bolygok[$hx][$hy]);
	}
}
function random_bolygo() {
	$rnd = mt_rand(0,99);
	if ($rnd<33) {
		return 2;
	}
	if ($rnd<59) {
		return 4;
	}
	if ($rnd<79) {
		return 6;
	}
	if ($rnd<92) {
		return 8;
	}
	return 10;
}

// Admin bolygó :)
put_bolygo(0, 0, 1, 10, 1);

// TODO settings
$planetLimit = 200;
$mapSize = 40;

for ($i = 0; $i < $planetLimit; $i++) {
	$x = mt_rand(-$mapSize,$mapSize);
	$y = mt_rand(-$mapSize,$mapSize);
	if ($x > 0 && $y < 0) {
		$region = 2;
	}
	if ($x > 0 && $y > 0) {
		$region = 3;
	}
	if ($x < 0 && $y > 0) {
		$region = 4;
	}
	if ($x < 0 && $y < 0) {
		$region = 5;
	}
	$planetSize = random_bolygo();
	put_bolygo(
		$x, $y,
		$region,
		$planetSize,
		$planetSize === 2
	);
}

//bolygok kirajzolasa
foreach($bolygok as $y => $planetsY) {
	foreach($planetsY as $x => $planetData) {
		$planetRegion  = $planetData[0];
		$planetSize    = $planetData[1];
		$planetRegable = $planetData[2];

		$reg_b[ $planetRegion ]++;
		$meret_b[ $planetSize ]++;
		$xx = $x * round(125 * sqrt(3));
		$yy = $y * 125 * 2 - (($x % 2) ? 0 : 125);
		imagefilledellipse(
			$kep,
			$felmeret + $hexa_kep_zoom * $xx,
			$felmeret + $hexa_kep_zoom * $yy,
			3,
			3,
			$regio_szinek[ $planetRegion ]
		);
	}
}



imagettftext($kep,8,0,10,30,$feher,$font_cim,'bolygok szama: ' . $bolygok_szama);
imagettftext($kep,8,0,10,45,$feher,$font_cim,'regisztralhato: ' . $reg_bolygok_szama);

// regiok szerinti eloszlas (szöveg)
for($regio=1; $regio<=12; $regio++) {
	imagettftext($kep,8,0,10,45+15*$regio,$feher,$font_cim,$regio);
	imagettftext($kep,8,0,30,45+15*$regio,$feher,$font_cim,$reg_b[$regio]);
}

// bolygó méretek (szöveg)
for($meret=1; $meret<=5; $meret++) {
	imagettftext($kep,8,0,10,245+15*$meret,$feher,$font_cim,(2*$meret) . 'M:');
	imagettftext($kep,8,0,40,245+15*$meret,$feher,$font_cim,$meret_b[2*$meret]);
}

// TODO ha megvan a kivant terkep, utana engedd meg, hogy tovabbfusson a kod!
//header('Content-type: image/png');
//imagepng($kep);
//exit;

require_once '../../config.php';

$mysql_username = $zanda_db_user;
$mysql_password = $zanda_db_password;
//$szerver_prefix = 'dev'; // ha felül szeretné definiálni a configot

$mysql_csatlakozas = mysql_connect($zanda_db_host, $mysql_username, $mysql_password);
$result = mysql_select_db('mmog');
if ($result === false) {
	exit("Nem letezik az 'mmog' adatbazis. Hozd letre, majd futtasd ujra!\n");
}
mysql_query('set names "utf8"');

if (mysql_query('truncate bolygok_'.$szerver_prefix) === false) {
	// Nem letezett a tabla => letrehozas
	$create = mysql_query('CREATE TABLE IF NOT EXISTS `bolygok_'.$szerver_prefix.'` (
	  `x` int(11) NOT NULL,
	  `y` int(11) NOT NULL,
	  `hexa_x` int(11) NOT NULL,
	  `hexa_y` int(11) NOT NULL,
	  `galaktikus_regio` int(11) NOT NULL,
	  `terulet` int(11) NOT NULL,
	  `alapbol_regisztralhato` int(11) NOT NULL,
	  `osztaly` varchar(5) COLLATE utf8_hungarian_ci NOT NULL,
	  `hold` int(11) NOT NULL,
	  KEY `alapbol_regisztralhato` (`alapbol_regisztralhato`),
	  KEY `terulet` (`terulet`),
	  KEY `galaktikus_regio` (`galaktikus_regio`),
	  KEY `osztaly` (`osztaly`),
	  KEY `coordinate` (`x`,`y`),
	  KEY `hexa_coordinate` (`hexa_x`,`hexa_y`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;');
}

// bolygók mentése átmeneti
foreach($bolygok as $y => $planetsY) {
	foreach($planetsY as $x => $planetData) {
		$planetRegion  = $planetData[0];
		$planetSize    = $planetData[1] * 1000000;
		$planetRegable = intval($planetData[2]);
		mysql_query("INSERT INTO bolygok_$szerver_prefix
			(hexa_x, hexa_y, galaktikus_regio, terulet, alapbol_regisztralhato)
			VALUES ($x, $y, $planetRegion, $planetSize, $planetRegable);");
	}
}

//tavolsagok
mysql_query('update bolygok_'.$szerver_prefix.'
set x=hexa_x*round(125*sqrt(3))+rand()*100-50,
y=hexa_y*125*2-if(hexa_x%2=0,0,125)+rand()*60-30');

//paros koorinatak
mysql_query('update bolygok_'.$szerver_prefix.' set x=round(x/2)*2,y=round(y/2)*2');

//osztaly
mysql_query('update bolygok_'.$szerver_prefix.' set osztaly=1+floor(rand()*5)');

//hold
mysql_query('update bolygok_'.$szerver_prefix.' set hold=case osztaly
when 1 then if(rand()<1/2,1,0)
when 2 then if(rand()<1/2,1,0)
when 3 then if(rand()<2/3,1,0)
when 4 then if(rand()<1/3,1,0)
when 5 then 1
end');


/*
select b.alapbol_regisztralhato,b.terulet,count(1) as darab,round(count(1)/mind*100) as szazalek
from bolygok b, (select count(1) as mind from bolygok) t
group by b.alapbol_regisztralhato,b.terulet

select b.alapbol_regisztralhato,b.terulet,count(1) as darab,round(count(1)/mind*100) as szazalek,sum(tulaj>0) as foglalt,round(sum(tulaj>0)/count(1)*100) as foglalt_szazalek
from bolygok b, (select count(1) as mind from bolygok) t
group by b.alapbol_regisztralhato,b.terulet
*/

header('Content-type: image/png');
imagepng($kep);
