<?
include('../csatlak.php');
if (!isset($argv[1]) or $argv[1]!=$zanda_private_key) exit;
set_time_limit(0);
header('Content-type: text/html; charset=utf-8');

function unsigned_parity($x) {return ($x>=0)?($x%2):((-$x)%2);}


$debug=true;//alapbol ne rakjon flottakat, csak irja ki


function get_indulo_pont($x,$y) {
	global $indulo_pontok;
	$min_tav=null;$min_pont=0;
	foreach($indulo_pontok as $i=>$indulo_pont) {
		$d=pow($indulo_pont[0]-$x,2)+pow($indulo_pont[1]-$y,2);
		if (is_null($min_tav)) {
			$min_pont=$i;$min_tav=$d;
		} elseif ($d<$min_tav) {
			$min_pont=$i;$min_tav=$d;
		}
	}
	return array($indulo_pontok[$min_pont][0],$indulo_pontok[$min_pont][1],$min_tav);
}
function zanda_flotta_egyenertek($hajok) {//frissiteni kell, ha valtoznak
	return round($hajok[0]*400
		+$hajok[1]*10
		+$hajok[2]*0.5
		+$hajok[3]*200
		+$hajok[4]*8
		+$hajok[5]*12);
}
function zanda_flottat_felrak($x,$y,$hajok,$statusz,$cel_x=null,$cel_y=null,$cel_id=null,$cel_tulaj_szov=null) {
	global $debug;
	if ($debug) echo '[D]';
	echo "zanda_flottat_felrak($x,$y,array(".implode(',',$hajok)."),$statusz,$cel_x,$cel_y,$cel_id,$cel_tulaj_szov);<br />\n";
	if ($debug) return;
	$tulaj=-1;$tulaj_szov=ZANDAGORT_TULAJ_SZOV;$elotag='Z';
	mysql_query('insert into flottak (nev,tulaj,tulaj_szov,kezelo,bolygo,bazis_bolygo,statusz,sebesseg,x,y,zanda_statusz,zanda_cel_x,zanda_cel_y,zanda_cel_id,zanda_cel_tulaj_szov) values("'.$elotag.'",'.$tulaj.','.$tulaj_szov.',0,0,0,'.STATUSZ_ALL.',0,'.$x.','.$y.','.$statusz.','.((int)$cel_x).','.((int)$cel_y).','.((int)$cel_id).','.((int)$cel_tulaj_szov).')');
	$flotta_id=mysql2num('select last_insert_id() from flottak');
	mysql_query('update flottak set nev="'.$elotag.$flotta_id.'" where id='.$flotta_id);
	mysql_query('insert into flotta_hajo (flotta_id,hajo_id,ossz_hp) values('.$flotta_id.',0,100)');
	for($i=201;$i<=224;$i++) mysql_query('insert into flotta_hajo (flotta_id,hajo_id,ossz_hp) values('.$flotta_id.','.$i.',0)');//fontos, h minden legyen benne, kulonben az atallitott flottakkal baj lesz!
	foreach($hajok as $id=>$db) mysql_query('update flotta_hajo set ossz_hp='.round(100*$db).' where flotta_id='.$flotta_id.' and hajo_id='.(219+$id));
	flotta_minden_frissites($flotta_id);
	//
	if ($statusz>10) {
		//mindenkepp menjen el valahova, utana kezdje meg a tevekenyseget (ld szim.php)
		mysql_query('update flottak set statusz='.STATUSZ_MEGY_XY.',cel_x='.$cel_x.',cel_y='.$cel_y.' where id='.$flotta_id);
	} else {
		switch($statusz) {
			case 1://szondak ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_FLOTTARA.',cel_flotta='.$cel_id.' where id='.$flotta_id);
			break;
			case 2://flottak ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_FLOTTARA.',cel_flotta='.$cel_id.' where id='.$flotta_id);
				//mysql_query('update flottak set statusz='.STATUSZ_MEGY_XY.',cel_x='.$cel_x.',cel_y='.$cel_y.' where id='.$flotta_id);
			break;
			case 3://bolygok ellen
			case 4://bolygok ellen
			case 5://bolygok ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
				//mysql_query('update flottak set statusz='.STATUSZ_MEGY_XY.',cel_x='.$cel_x.',cel_y='.$cel_y.' where id='.$flotta_id);
			break;
			case 6://npc bolygok ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
			break;
			case 7://npc flottak ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_FLOTTARA.',cel_flotta='.$cel_id.' where id='.$flotta_id);
			break;
			case 8://tetszoleges bolygok ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
			break;
/*			case 4://npc bolygok ellen
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
			break;
			case 5://mindenfele bolygok ellen kozvetlenul
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
			break;
			case 6://mindenfele bolygok ellen kozvetlenul, majd utana barkit tamad
				mysql_query('update flottak set statusz='.STATUSZ_TAMAD_BOLYGORA.',cel_bolygo='.$cel_id.' where id='.$flotta_id);
			break;*/
		}
	}
}
function minden_zanda_flottat_leszed() {
	$r=mysql_query('select * from flottak where tulaj<0');
	while($aux=mysql_fetch_array($r)) flotta_torles($aux['id']);
}
$flottak_szama=0;$flottak_egyenerteke=0;




/***************************** MINDENFELE LESZEDO KODOK *****************************/

/*
//veletlenul bekerult 0-s flottak
$er=mysql_query('select * from flottak where tulaj=-1 and egyenertek=0');
while($aux=mysql_fetch_array($er)) {
	flotta_torles($aux['id']);
}
echo 'aaa';exit;
*/

/*
$er=mysql_query('select * from flottak where tulaj=-1 and egyenertek=0');
while($aux=mysql_fetch_array($er)) {
	flotta_torles($aux['id']);
	$hajok=array(0,0,0,10,0,0);
	zanda_flottat_felrak($aux['x'],$aux['y'],$hajok,5,0,0,$aux['cel_bolygo']);
}
echo 'aaa';exit;
*/

/*
$er=mysql_query('select * from flottak where tulaj=-1 and id>=102318');
while($aux=mysql_fetch_array($er)) {
	flotta_torles($aux['id']);
}
echo 'aaa';exit;
*/

//echo 'aaa';exit;
//zanda_flottat_felrak(80000,8000,array(0,1,0,0,0,0),3,81000,9000,7586);//b,z,sz,a,c,p
//flotta_torles(81481);

//flotta_torles(84402);


//minden_zanda_flottat_leszed();




/***************************************************** ITT KEZDODIK A LENYEG ********************************************************************/


//lehetseges indulasi pontok (amig sok bolygo van)
/*
for($y=-16;$y<=16;$y++) for($x=-16;$x<=16;$x++) {
	$aux=mysql2row('select sum(letezik),sum(letezik=1 and tulaj>0) from bolygok where x between '.($x*5000-5000).' and '.($x*5000+5000).' and y between '.($y*5000-5000).' and '.($y*5000+5000).'');
	$aux2=mysql2row('select sum(letezik),sum(letezik=1 and tulaj>0) from bolygok where x between '.($x*5000-3000).' and '.($x*5000+3000).' and y between '.($y*5000-3000).' and '.($y*5000+3000).'');
	$aux3=mysql2row('select sum(letezik),sum(letezik=1 and tulaj>0) from bolygok where x between '.($x*5000-1500).' and '.($x*5000+1500).' and y between '.($y*5000-1500).' and '.($y*5000+1500).'');
	if ($aux[0]>0) if ($aux2[1]==0) if ($aux3[0]==0) {
		$indulo_pontok[]=array($x*5000,$y*5000);
	}
}
*/
//$indulo_pontok[]=array(-5500,11000);//specko, az s8-ra


//lehetseges indulasi pontok (amikor mas keves bolygo van)
for($y=-16;$y<=16;$y++) for($x=-16;$x<=16;$x++) {
	$aux=mysql2row('select sum(letezik),sum(letezik=1 and tulaj>0) from bolygok where x between '.($x*5000-20000).' and '.($x*5000+20000).' and y between '.($y*5000-20000).' and '.($y*5000+20000).'');
	$aux2=mysql2row('select sum(letezik),sum(letezik=1 and tulaj>0) from bolygok where x between '.($x*5000-3000).' and '.($x*5000+3000).' and y between '.($y*5000-3000).' and '.($y*5000+3000).'');
	if ($aux[0]>0) if ($aux2[1]==0) {
		$indulo_pontok[]=array($x*5000,$y*5000);
	}
}



//1. hullam: NPC bolygok ellen (pentek)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select b.*,round(coalesce(sum(f.egyenertek),0)/100) as vedok
from bolygok b
left join flottak f on f.bolygo=b.id and f.statusz=1 and f.tulaj=0
where b.letezik=1 and b.tulaj=0
group by b.id');
while($celpont_bolygo=mysql_fetch_array($r)) {
	$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
	if ($ip[2]<10000*10000) {//5kpc-n belul
		$egyenertek=round($celpont_bolygo['vedok']);
		if ($egyenertek==0) {//vedtelen -> zeusz
			$hajok[0]=0;
			$hajok[1]=mt_rand(1,100);
			$hajok[2]=0;
			$hajok[3]=0;
			$hajok[4]=0;
			$hajok[5]=0;
		} else {//vedett
			$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[1]=0;
			$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
		}
		$ee=zanda_flotta_egyenertek($hajok);
		//
		$x=$ip[0]+mt_rand(0,200)-100;
		$y=$ip[1]+mt_rand(0,200)-100;
		echo number_format($ee,0,',',' ').' vs '.number_format($celpont_bolygo['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,6,0,0,$celpont_bolygo['id'],0);
		$flottak_szama++;$flottak_egyenerteke+=$ee;
	}
}
*/



//2. hullam: jatekosok ellen (pentek)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select u.id,u.nev,uf.flottak_szama,uf.ee,count(b.id) as bolygok_szama
from userek u
inner join (select u.id,count(f.id) as flottak_szama,round(coalesce(sum(f.egyenertek),0)/100) as ee
from userek u
left join flottak f on f.tulaj=u.id
group by u.id) uf on uf.id=u.id
left join bolygok b on b.tulaj=u.id and b.letezik=1
group by u.id');
while($celpont_user=mysql_fetch_array($r)) if ($celpont_user['bolygok_szama']>=2) if ($celpont_user['ee']>0) {//legalabb 2 bolygo
	$hanyadik_flotta=0;
	$r2=mysql_query('select * from bolygok where letezik=1 and tulaj='.$celpont_user['id'].' order by iparmeret desc');
	while($celpont_bolygo=mysql_fetch_array($r2)) if ($hanyadik_flotta<10) if (mt_rand(1,2)==1) {
		$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
		if ($ip[2]<10000*10000) {//5kpc-n belul
			$hanyadik_flotta++;
			if ($hanyadik_flotta==1) $egyenertek=round($celpont_user['ee']/100*50/2);//csak a teljes egyenertek fele menjen
			elseif ($hanyadik_flotta<=5) $egyenertek=round($celpont_user['ee']/100*10/2);
			else $egyenertek=round($celpont_user['ee']/100*2/2);
			$zeusz=false;
			if ($hanyadik_flotta>5) if (mt_rand(1,2)==1) $zeusz=true;
			if ($zeusz) {
				$hajok[0]=0;
				$hajok[1]=1;
				$hajok[2]=0;
				$hajok[3]=0;
				$hajok[4]=0;
				$hajok[5]=0;
			} else {
				$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[1]=0;
				$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			}
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
			$ee=zanda_flotta_egyenertek($hajok);
			//
			$x=$ip[0]+mt_rand(0,200)-100;
			$y=$ip[1]+mt_rand(0,200)-100;
			echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,5,0,0,$celpont_bolygo['id'],$celpont_user['id']);
			$flottak_szama++;$flottak_egyenerteke+=$ee;
		}
	}
}
*/



//3. hullam: NPC bolygok ellen (szombat)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select b.*,round(coalesce(sum(f.egyenertek),0)/100) as vedok
from bolygok b
left join flottak f on f.bolygo=b.id and f.statusz=1 and f.tulaj=0
where b.letezik=1 and b.tulaj=0
group by b.id');
while($celpont_bolygo=mysql_fetch_array($r)) {
	$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
	if ($ip[2]<10000*10000) {//5kpc-n belul
		$mar_tamadja=mysql2row('select * from flottak where tulaj=-1 and cel_bolygo='.$celpont_bolygo['id'].' and statusz in (7,8) limit 1');
		if ($mar_tamadja) continue;
		$egyenertek=round($celpont_bolygo['vedok']);
		if ($egyenertek==0) {//vedtelen -> zeusz
			$hajok[0]=0;
			$hajok[1]=mt_rand(1,100);
			$hajok[2]=0;
			$hajok[3]=0;
			$hajok[4]=0;
			$hajok[5]=0;
		} else {//vedett
			$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[1]=0;
			$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
		}
		$ee=zanda_flotta_egyenertek($hajok);
		//
		$x=$ip[0]+mt_rand(0,200)-100;
		$y=$ip[1]+mt_rand(0,200)-100;
		echo number_format($ee,0,',',' ').' vs '.number_format($celpont_bolygo['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,6,0,0,$celpont_bolygo['id'],0);
		$flottak_szama++;$flottak_egyenerteke+=$ee;
	}
}
*/



//4. hullam: jatekosok ellen (szombat)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select u.id,u.nev,uf.flottak_szama,uf.ee,count(b.id) as bolygok_szama
from userek u
inner join (select u.id,count(f.id) as flottak_szama,round(coalesce(sum(f.egyenertek),0)/100) as ee
from userek u
left join flottak f on f.tulaj=u.id
group by u.id) uf on uf.id=u.id
left join bolygok b on b.tulaj=u.id and b.letezik=1
group by u.id');
while($celpont_user=mysql_fetch_array($r)) if ($celpont_user['bolygok_szama']>=2) if ($celpont_user['ee']>0) {//legalabb 2 bolygo
	$ennyi_kell_kuldeni=$celpont_user['ee']*0.75;//csak a teljes egyenertek haromnegyede menjen
	$tamado_egyenertek=mysql2num('select round(coalesce(sum(f.egyenertek),0)/100) as tamado from flottak f, bolygok b where f.tulaj=-1 and f.cel_bolygo=b.id and f.statusz in (7,8) and b.tulaj='.$celpont_user['id']);
	if ($tamado_egyenertek>$ennyi_kell_kuldeni/2) continue;//ha mar nagyon keves tamadoja van, akkor jojjon utanpotlas, amugy hadd "pihenjen"
	$ennyi_kell_kuldeni=$ennyi_kell_kuldeni-$tamado_egyenertek;
	$hanyadik_flotta=0;
	$r2=mysql_query('select * from bolygok where letezik=1 and tulaj='.$celpont_user['id'].' order by rand()');
	while($celpont_bolygo=mysql_fetch_array($r2)) if ($hanyadik_flotta<10) {
		$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
		if ($ip[2]<10000*10000) {//5kpc-n belul
			$hanyadik_flotta++;
			if ($hanyadik_flotta==1) $egyenertek=round($ennyi_kell_kuldeni/100*50);
			elseif ($hanyadik_flotta<=5) $egyenertek=round($ennyi_kell_kuldeni/100*10);
			else $egyenertek=round($ennyi_kell_kuldeni/100*2);
			$zeusz=false;
			if ($hanyadik_flotta>5) if (mt_rand(1,2)==1) $zeusz=true;
			if ($egyenertek<1000) $zeusz=true;
			if ($zeusz) {
				$hajok[0]=0;
				$hajok[1]=1;
				$hajok[2]=0;
				$hajok[3]=0;
				$hajok[4]=0;
				$hajok[5]=0;
			} else {
				$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[1]=0;
				$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			}
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
			$ee=zanda_flotta_egyenertek($hajok);
			//
			$x=$ip[0]+mt_rand(0,200)-100;
			$y=$ip[1]+mt_rand(0,200)-100;
			echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,5,0,0,$celpont_bolygo['id'],$celpont_user['id']);
			$flottak_szama++;$flottak_egyenerteke+=$ee;
		}
	}
}
*/


/*
//5. hullam: NPC bolygok ellen (vasarnap)
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select b.*,round(coalesce(sum(f.egyenertek),0)/100) as vedok
from bolygok b
left join flottak f on f.bolygo=b.id and f.statusz=1 and f.tulaj=0
where b.letezik=1 and b.tulaj=0
group by b.id');
while($celpont_bolygo=mysql_fetch_array($r)) {
	$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
	if ($ip[2]<20000*20000) {//10kpc-n belul (most mar mindegyik hatralevo)
		$mar_tamadja=mysql2row('select * from flottak where tulaj=-1 and cel_bolygo='.$celpont_bolygo['id'].' and statusz in (7,8) limit 1');
		if ($mar_tamadja) continue;
		//$egyenertek=round($celpont_bolygo['vedok']*1.5);//most mar kemenyebben kell fellepni
		$egyenertek=round($celpont_bolygo['vedok']*1.2);//annyira azert ne
		if ($egyenertek==0) {//vedtelen -> zeusz
			$hajok[0]=0;
			$hajok[1]=mt_rand(1,100);
			$hajok[2]=0;
			$hajok[3]=0;
			$hajok[4]=0;
			$hajok[5]=0;
		} else {//vedett
			$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[1]=0;
			$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
		}
		$ee=zanda_flotta_egyenertek($hajok);
		//
		$x=$ip[0]+mt_rand(0,200)-100;
		$y=$ip[1]+mt_rand(0,200)-100;
		echo number_format($ee,0,',',' ').' vs '.number_format($celpont_bolygo['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,6,0,0,$celpont_bolygo['id'],0);
		$flottak_szama++;$flottak_egyenerteke+=$ee;
	}
}
*/




//6. hullam: jatekosok ellen (vasarnap)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select u.id,u.nev,uf.flottak_szama,uf.ee,count(b.id) as bolygok_szama
from userek u
inner join (select u.id,count(f.id) as flottak_szama,round(coalesce(sum(f.egyenertek),0)/100) as ee
from userek u
left join flottak f on f.tulaj=u.id
group by u.id) uf on uf.id=u.id
left join bolygok b on b.tulaj=u.id and b.letezik=1
group by u.id');
while($celpont_user=mysql_fetch_array($r)) if ($celpont_user['bolygok_szama']>=1) if ($celpont_user['ee']>0) {//lehet egybolygosnak is, csak gyenget
	if ($celpont_user['bolygok_szama']==1) $ennyi_kell_kuldeni=$celpont_user['ee']*1;//mivel csak egy flottat fog kapni, ami a vedelmenek a fele
	elseif ($celpont_user['bolygok_szama']==2) $ennyi_kell_kuldeni=$celpont_user['ee']*1;
	else $ennyi_kell_kuldeni=$celpont_user['ee']*0.75;
	//
	$tamado_egyenertek=mysql2num('select round(coalesce(sum(f.egyenertek),0)/100) as tamado from flottak f, bolygok b where f.tulaj=-1 and f.cel_bolygo=b.id and f.statusz in (7,8) and b.tulaj='.$celpont_user['id']);
	if ($tamado_egyenertek>$ennyi_kell_kuldeni/2) continue;//ha mar nagyon keves tamadoja van, akkor jojjon utanpotlas, amugy hadd "pihenjen"
	$ennyi_kell_kuldeni=$ennyi_kell_kuldeni-$tamado_egyenertek;
	$hanyadik_flotta=0;
	$r2=mysql_query('select * from bolygok where letezik=1 and tulaj='.$celpont_user['id'].' order by rand()');
	while($celpont_bolygo=mysql_fetch_array($r2)) if ($hanyadik_flotta<10) {
		$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
		if ($ip[2]<20000*20000) {//10kpc-n belul (mert c+p gyors)
			$hanyadik_flotta++;
			if ($hanyadik_flotta==1) $egyenertek=round($ennyi_kell_kuldeni/100*50);
			elseif ($hanyadik_flotta<=5) $egyenertek=round($ennyi_kell_kuldeni/100*10);
			else $egyenertek=round($ennyi_kell_kuldeni/100*2);
			$zeusz=false;
			if ($hanyadik_flotta>5) if (mt_rand(1,2)==1) $zeusz=true;
			if ($egyenertek<1000) $zeusz=true;
			if ($zeusz) {
				$hajok[0]=0;
				$hajok[1]=0;
				$hajok[2]=0;
				$hajok[3]=0;
				$hajok[4]=3;
				$hajok[5]=2;
			} else {
				$hajok[0]=0;
				$hajok[1]=0;
				$hajok[2]=0;
				$hajok[3]=0;
				$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			}
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
			$ee=zanda_flotta_egyenertek($hajok);
			//
			$x=$ip[0]+mt_rand(0,200)-100;
			$y=$ip[1]+mt_rand(0,200)-100;
			echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,5,0,0,$celpont_bolygo['id'],$celpont_user['id']);
			$flottak_szama++;$flottak_egyenerteke+=$ee;
		}
	}
}
*/



//7. hullam: NPC flottak ellen (hetfo)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select f.*,round(f.egyenertek/100) as vedok
from flottak f
where f.tulaj=0');
while($celpont_flotta=mysql_fetch_array($r)) {
	$ip=get_indulo_pont($celpont_flotta['x'],$celpont_flotta['y']);
	$egyenertek=round($celpont_flotta['vedok']*1.5);//most mar kemenyebben kell fellepni
	if ($ip[2]>10000*10000) {//tavol van: zeusz
		$hajok[0]=0;
		$hajok[1]=1;
		$hajok[2]=0;
		$hajok[3]=0;
		$hajok[4]=0;
		$hajok[5]=0;
		$ee=zanda_flotta_egyenertek($hajok);
		for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	} else {//kozel van: vegyes
		$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[1]=0;
		$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
		$ee=zanda_flotta_egyenertek($hajok);
		for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	}
	$ee=zanda_flotta_egyenertek($hajok);
	//
	$x=$ip[0]+mt_rand(0,200)-100;
	$y=$ip[1]+mt_rand(0,200)-100;
	echo number_format($ee,0,',',' ').' vs '.number_format($celpont_flotta['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,7,0,0,$celpont_flotta['id'],0);
	$flottak_szama++;$flottak_egyenerteke+=$ee;
}
*/




//8. hullam: jatekosok ellen (hetfo)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select u.id,u.nev,uf.flottak_szama,uf.ee,count(b.id) as bolygok_szama
from userek u
inner join (select u.id,count(f.id) as flottak_szama,round(coalesce(sum(f.egyenertek),0)/100) as ee
from userek u
left join flottak f on f.tulaj=u.id
group by u.id) uf on uf.id=u.id
left join bolygok b on b.tulaj=u.id and b.letezik=1
group by u.id');
while($celpont_user=mysql_fetch_array($r)) if ($celpont_user['bolygok_szama']>=1) {//lehet egybolygosnak is, csak gyenget
	if ($celpont_user['ee']<10) $celpont_user['ee']=10;//aki nem vedekezik, most mar az is kapjon
	if ($celpont_user['bolygok_szama']==1) $ennyi_kell_kuldeni=$celpont_user['ee']*1;//mivel csak egy flottat fog kapni, ami a vedelmenek a fele
	elseif ($celpont_user['bolygok_szama']==2) $ennyi_kell_kuldeni=$celpont_user['ee']*1;
	else $ennyi_kell_kuldeni=$celpont_user['ee']*1;
	//
	$tamado_egyenertek=mysql2num('select round(coalesce(sum(f.egyenertek),0)/100) as tamado from flottak f, bolygok b where f.tulaj=-1 and f.cel_bolygo=b.id and f.statusz in (7,8) and b.tulaj='.$celpont_user['id']);
	if ($tamado_egyenertek>$ennyi_kell_kuldeni/2) continue;//ha mar nagyon keves tamadoja van, akkor jojjon utanpotlas, amugy hadd "pihenjen"
	$ennyi_kell_kuldeni=$ennyi_kell_kuldeni-$tamado_egyenertek;
	$hanyadik_flotta=0;
	$r2=mysql_query('select * from bolygok where letezik=1 and tulaj='.$celpont_user['id'].' order by rand()');
	while($celpont_bolygo=mysql_fetch_array($r2)) if ($hanyadik_flotta<10) {
		$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
		if ($ip[2]<20000*20000) {//5kpc-n belul
			$hanyadik_flotta++;
			if ($hanyadik_flotta==1) {
				$egyenertek=round($ennyi_kell_kuldeni/100*50);
				$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[1]=0;
				$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			} elseif ($hanyadik_flotta<=5) {
				$egyenertek=round($ennyi_kell_kuldeni/100*10);
				$hajok[0]=0;
				$hajok[1]=0;
				$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas))*mt_rand(0,1);
				$hajok[3]=0;
				$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
				$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
			} else {
				$egyenertek=round($ennyi_kell_kuldeni/100*2);
				$hajok[0]=0;
				$hajok[1]=1;
				$hajok[2]=0;
				$hajok[3]=0;
				$hajok[4]=0;
				$hajok[5]=0;
			}
			$ee=zanda_flotta_egyenertek($hajok);
			for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
			$ee=zanda_flotta_egyenertek($hajok);
			//
			$x=$ip[0]+mt_rand(0,200)-100;
			$y=$ip[1]+mt_rand(0,200)-100;
			echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,5,0,0,$celpont_bolygo['id'],$celpont_user['id']);
			$flottak_szama++;$flottak_egyenerteke+=$ee;
		}
	}
}
*/



//9. hullam: NPC flottak ellen (kedd)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select f.*,round(f.egyenertek/100) as vedok
from flottak f
where f.tulaj=0');
while($celpont_flotta=mysql_fetch_array($r)) {
	$ip=get_indulo_pont($celpont_flotta['x'],$celpont_flotta['y']);
	$egyenertek=round($celpont_flotta['vedok']*1.5);//most mar kemenyebben kell fellepni
	if ($ip[2]>10000*10000) {//tavol van: zeusz
		$hajok[0]=0;
		$hajok[1]=1;
		$hajok[2]=0;
		$hajok[3]=0;
		$hajok[4]=0;
		$hajok[5]=0;
		$ee=zanda_flotta_egyenertek($hajok);
		for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	} else {//kozel van: vegyes
		$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[1]=0;
		$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
		$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
		$ee=zanda_flotta_egyenertek($hajok);
		for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	}
	$ee=zanda_flotta_egyenertek($hajok);
	//
	$x=$ip[0]+mt_rand(0,200)-100;
	$y=$ip[1]+mt_rand(0,200)-100;
	echo number_format($ee,0,',',' ').' vs '.number_format($celpont_flotta['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,7,0,0,$celpont_flotta['id'],0);
	$flottak_szama++;$flottak_egyenerteke+=$ee;
}
*/



//10. hullam: jatekosok ellen (kedd)
/*
//$debug=false;//mehetnek a flottak tenyleg
$randomitas=100;
$r=mysql_query('select u.id,u.nev,uf.flottak_szama,uf.ee,count(b.id) as bolygok_szama
from userek u
inner join (select u.id,count(f.id) as flottak_szama,round(coalesce(sum(f.egyenertek),0)/100) as ee
from userek u
left join flottak f on f.tulaj=u.id
group by u.id) uf on uf.id=u.id
left join bolygok b on b.tulaj=u.id and b.letezik=1
group by u.id');
while($celpont_user=mysql_fetch_array($r)) if ($celpont_user['bolygok_szama']>=1) if ($celpont_user['ee']<1000000) {//egybolygosnak is, es most csak a kisebb jatekosokat
	$celpont_bolygo=mysql2row('select * from bolygok where letezik=1 and tulaj='.$celpont_user['id'].' order by iparmeret desc limit 1');
	$ip=get_indulo_pont($celpont_bolygo['x'],$celpont_bolygo['y']);
	$egyenertek=round($celpont_user['ee']/2);//teljes egyenertek felet egy bolygora
	if ($egyenertek==0) {//zeusz
		$hajok[0]=0;
		$hajok[1]=1;
		$hajok[2]=0;
		$hajok[3]=0;
		$hajok[4]=0;
		$hajok[5]=0;
		$egyenertek=10;//1 darab zeusz
	} else {
		if ($ip[2]<10000*10000) {//zeusz
			$hajok[0]=0;
			$hajok[1]=1;
			$hajok[2]=0;
			$hajok[3]=0;
			$hajok[4]=0;
			$hajok[5]=0;
		} else {//c,p es c,p,sz
			$hajok[0]=0;
			$hajok[1]=0;
			$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas))*mt_rand(0,1);
			$hajok[3]=0;
			$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
			$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
		}
	}
	$ee=zanda_flotta_egyenertek($hajok);
	for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	//
	$ee=zanda_flotta_egyenertek($hajok);
	//
	$x=$ip[0]+mt_rand(0,200)-100;
	$y=$ip[1]+mt_rand(0,200)-100;
	echo number_format($ee,0,',',' ').' vs '.number_format($celpont_bolygo['vedok'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,5,0,0,$celpont_bolygo['id'],0);
	$flottak_szama++;$flottak_egyenerteke+=$ee;
}
*/





/*
PARBAJ (szerda)
dezertalast letiltani!!!
szondakat kiloni vagy egyszeruen a helyukon hagyni
fog of war-t kikapcsolni



kalozok: menekulnek

update flottak
set bolygo=0,statusz=5,cel_x=x*10,cel_y=y*10
where tulaj=0;
*/

//11. hullam: utolso oriasflotta (szerda)
/*
//$debug=false;//mehetnek a flottak tenyleg
for($ii=0;$ii<10;$ii++) {
	$egyenertek=10000000;
	$hajok[0]=round(1000/100*mt_rand(100-$randomitas,100+$randomitas));
	$hajok[1]=0;
	$hajok[2]=round(250000/100*mt_rand(100-$randomitas,100+$randomitas));
	$hajok[3]=round(100/100*mt_rand(100-$randomitas,100+$randomitas));
	$hajok[4]=round(15000/100*mt_rand(100-$randomitas,100+$randomitas));
	$hajok[5]=round(10000/100*mt_rand(100-$randomitas,100+$randomitas));
	$ee=zanda_flotta_egyenertek($hajok);
	for($i=0;$i<6;$i++) $hajok[$i]=ceil($hajok[$i]/$ee*$egyenertek);
	$ee=zanda_flotta_egyenertek($hajok);
	//
	$x=-400;
	$y=($i-50)*100;
	echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,0,0,0,0,0);
	$flottak_szama++;$flottak_egyenerteke+=$ee;
}

$hajok[0]=100000;
$hajok[1]=0;
$hajok[2]=5000000;
$hajok[3]=10000;
$hajok[4]=3000000;
$hajok[5]=2000000;
$ee=zanda_flotta_egyenertek($hajok);
//
$x=-300;
$y=0;
echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,0,0,0,0,0);
$flottak_szama++;$flottak_egyenerteke+=$ee;

$hajok[0]=500000;
$hajok[1]=0;
$hajok[2]=10000000;
$hajok[3]=20000;
$hajok[4]=12000000;
$hajok[5]=8000000;
$ee=zanda_flotta_egyenertek($hajok);
//
$x=-200;
$y=0;
echo number_format($ee,0,',',' ').' vs '.number_format($celpont_user['ee'],0,',',' ').' # ';zanda_flottat_felrak($x,$y,$hajok,0,0,0,0,0);
$flottak_szama++;$flottak_egyenerteke+=$ee;
*/

/*
//parbaj: zanda koltozese
$x=-300;$dy=0;$par=1;
//$er=mysql_query('select * from flottak where tulaj=-1 and egyenertek>=10000000 order by egyenertek desc');
$er=mysql_query('select * from flottak where tulaj=-1 order by egyenertek desc');
while($aux=mysql_fetch_array($er)) {
	$y=$dy*$par;
	$x=round(-300-$dy/3);
	mysql_query("update flottak set x=$x,y=$y,statusz=2 where id=".$aux['id']);
	$par=-$par;
	$dy+=6;
}
*/


/*
mysql_query('update flottak set x=round(x/2)*2, y=round(y/2)*2');
mysql_query('update flottak f
inner join (select id,x,y,
@hx:=floor(x/217),
@hy:=floor(y/125),
@hatarparitas:=(abs(@hx%2)+abs(@hy%2))%2,
@xmar:=x-217*@hx,
@ymar:=y-125*@hy,
@m:=if(@hatarparitas=0,-1,1)*217/125,
@N:=if(@ymar-125/2>@m*(@xmar-217/2),1,0),
@hx+if(@hatarparitas=1,1-@N,@N) as hexa_x,
if(@hy%2=0,round(@hy/2)+@N,round((@hy+1)/2)) as hexa_y
from flottak) t on f.id=t.id
left join hexak h on t.hexa_x=h.x and t.hexa_y=h.y
set f.hexa_x=t.hexa_x, f.hexa_y=t.hexa_y, f.hexa_voronoi_bolygo_id=coalesce(h.voronoi_bolygo_id,0)');
echo 'parbaj';exit;
*/

/*
//csak szondak
$er=mysql_query('select f.id,f.egyenertek
from flottak f, flotta_hajo fh
where fh.flotta_id=f.id and fh.hajo_id>0
group by f.id
having sum(if(fh.hajo_id=206,fh.ossz_hp,0))>0 and sum(if(fh.hajo_id!=206,fh.ossz_hp,0))=0');
while($aux=mysql_fetch_array($er)) {
	flotta_torles($aux['id']);
}
echo 'szondak';exit;
*/






/*
//parbaj: emberek koltozese
$x=500;$y=-645;
$er=mysql_query('select tulaj_szov,tulaj,count(1) from flottak where tulaj>0 group by tulaj_szov,tulaj');
while($aux=mysql_fetch_array($er)) {
	mysql_query("update flottak set x=$x,y=$y,statusz=2 where tulaj=".$aux['tulaj']);
	$y+=6;
}
mysql_query('update flottak set x=round(x/2)*2, y=round(y/2)*2');
//hexak meghatarozasa, utana annak fuggvenyeben moralvaltozas (lasd lentebb)
//left join, hogy a nagy hexakoron kivuli flottaknak 0 legyen a hexa_voronoi_bolygo_id-ja (es ne megmaradjon a korabbi)
mysql_query('update flottak f
inner join (select id,x,y,
@hx:=floor(x/217),
@hy:=floor(y/125),
@hatarparitas:=(abs(@hx%2)+abs(@hy%2))%2,
@xmar:=x-217*@hx,
@ymar:=y-125*@hy,
@m:=if(@hatarparitas=0,-1,1)*217/125,
@N:=if(@ymar-125/2>@m*(@xmar-217/2),1,0),
@hx+if(@hatarparitas=1,1-@N,@N) as hexa_x,
if(@hy%2=0,round(@hy/2)+@N,round((@hy+1)/2)) as hexa_y
from flottak) t on f.id=t.id
left join hexak h on t.hexa_x=h.x and t.hexa_y=h.y
set f.hexa_x=t.hexa_x, f.hexa_y=t.hexa_y, f.hexa_voronoi_bolygo_id=coalesce(h.voronoi_bolygo_id,0)');
echo 'parbaj';exit;
*/


/*
$er=mysql_query('select f.id
from flottak f, flotta_hajo fh
where fh.flotta_id=f.id and fh.hajo_id>0
group by f.id
having sum(if(fh.hajo_id=206,fh.ossz_hp,0))>0 and sum(if(fh.hajo_id!=206,fh.ossz_hp,0))=0');
*/


/*
//nem tudom
$er=mysql_query('select * from flottak where tulaj=-1 and egyenertek<10000000 order by egyenertek desc');
while($aux=mysql_fetch_array($er)) {
	$b=mysql2row('select * from bolygok where tulaj=0 and letezik=1 and pow(x,2)+pow(y,2)<pow(5000,2) order by rand() limit 1');
	$x=$b['x']+50;
	$y=$b['y'];
	mysql_query("update flottak set x=$x,y=$y,statusz=7,cel_bolygo=".$b['id']." where id=".$aux['id']);
}
*/



echo $flottak_szama." flotta<br />\n";
echo number_format($flottak_egyenerteke,0,',',' ')." egyenertek<br />\n";
echo 'kesz';
?>