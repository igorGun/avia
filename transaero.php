<?php
header('Content-type: text/html;charset=utf-8');
define('DS', DIRECTORY_SEPARATOR);
$startime = microtime(1);
$mem_start = memory_get_usage();
set_time_limit(0);
require dirname(__FILE__).DS.'Classes'.DS.'phpQuery-onefile.php';
include dirname(__FILE__).DS.'Classes'.DS.'useragents.lib.php';
include dirname(__FILE__).DS.'Classes'.DS.'simplexlsx.class.php';


//имя файла
$date5 = date("d_m_Y_H_i_"); 
$namecsv = $date5.'transaero.csv';

function get_page($url,$uagent,$post=0,$head=0,$setcooc=0){
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);    //прикидываемся браузером
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).DS.'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).DS.'cookies.txt');
	if(!empty($post)){
	    curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
	}
	if(!empty($setcooc)){
		//curl_setopt($ch, CURLOPT_COOKIE, $setcooc);
	}
	if(!empty($head)){
		curl_setopt($ch, CURLOPT_HTTPHEADER,$head);
	}
	$content = curl_exec( $ch );
    curl_close($ch);
	 
    return $content;
}


//функция транслитерации
function rus2translit($string) {

    $converter = array(

        'а' => 'a',   'б' => 'b',   'в' => 'v',

        'г' => 'g',   'д' => 'd',   'е' => 'e',

        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',

        'и' => 'i',   'й' => 'y',   'к' => 'k',

        'л' => 'l',   'м' => 'm',   'н' => 'n',

        'о' => 'o',   'п' => 'p',   'р' => 'r',

        'с' => 's',   'т' => 't',   'у' => 'u',

        'ф' => 'f',   'х' => 'h',   'ц' => 'c',

        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',

        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',

        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        

        'А' => 'A',   'Б' => 'B',   'В' => 'V',

        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',

        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',

        'И' => 'I',   'Й' => 'Y',   'К' => 'K',

        'Л' => 'L',   'М' => 'M',   'Н' => 'N',

        'О' => 'O',   'П' => 'P',   'Р' => 'R',

        'С' => 'S',   'Т' => 'T',   'У' => 'U',

        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',

        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',

        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',

        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',

    );

    return strtr($string, $converter);

}

//функция переформатирования даты
function get_date($peremen){
	$maf = $mag = array();
	$mag = explode('.',trim($peremen));
	$daf = date("Y").'-'.$mag[1].'-'.$mag[0];
	return $daf;
}

if (isset($_POST['send'])) {

if(empty($_POST['dayyy'])){
	echo 'Не указан плюс количество дней';
	exit;
}

$page = glob(dirname(__FILE__).DS.'*.csv');
@unlink($page[0]);


$dateplus = $_POST['dayyy'];

//сегодняшняя дата
$dayot = date("d.m.Y");      //29.03.2014

//дата плюс количество дней
$daytyda = date("d.m.Y",strtotime("+".$dateplus." days"));


$mass = array(	'592|39:Москва-Тель-Авив','592|991:Москва-Анталия','592|642:Москва-Бангкок','592|1018:Москва-Бодрум','592|452:Москва-Бургас',
'592|583:Москва-Варна','592|2680:Москва-Венеция','592|1145:Москва-Джерба','592|976:Москва-Канкун','592|1453:Москва-Корфу',
'592|640:Москва-Мале','592|2500:Москва-Милан','592|1077:Москва-Монастир','592|2051:Москва-Монтего Бэй','592|2474:Москва-Неаполь',
'592|188:Москва-о.Маврикий','592|3074:Москва-Палермо','592|1626:Москва-Пардубице','592|853:Москва-Париж','592|100:Москва-Пекин',
'592|153:Москва-Пунта Кана','592|643:Москва-Пхукет','592|2530:Москва-Римини','592|2470:Москва-Рим','592|1066:Москва-Родос',
'592|1033:Москва-Салоники','592|958:Москва-Санья','592|18:Москва-Тенерифе (Юг)','592|1144:Москва-Тунис','592|1078:Москва-Хельсинки',
'592|71:Москва-Эйлат','592|5010:Москва-Энфида','1085|853:Прага-Париж','642|643:Бангкок-Пхукет','134|16:Гонконг-Сингапур',
'1519|268:Дубай-Коломбо','1519|640:Дубай-Мале','609|642:Екатеринбург-Бангкок','609|452:Екатеринбург-Бургас','609|583:Екатеринбург-Варна',
'609|643:Екатеринбург-Пхукет','609|2530:Екатеринбург-Римини','609|2470:Екатеринбург-Рим','609|958:Екатеринбург-Санья',
'609|1084:Екатеринбург-Тиват','609|1232:Екатеринбург-Шарджа','611|452:Казань-Бургас','710|642:Красноярск-Бангкок',
'1380|627:Лос Анджелес-Нью-Йорк','723|1734:Нижний Новгород-Ираклион','615|991:Новосибирск-Анталия','615|642:Новосибирск-Бангкок',
'615|639:Новосибирск-Денпасар','615|643:Новосибирск-Пхукет','615|2530:Новосибирск-Римини','123|944:о.Лангкави-Куала Лумпур',
'727|2530:Омск-Римини','727|989:Омск-Стамбул','737|1084:Ростов-на-Дону-Тиват','594|452:Санкт-Петербург-Бургас',
'594|583:Санкт-Петербург-Варна','594|153:Санкт-Петербург-Пунта Кана','594|1084:Санкт-Петербург-Тиват','16|2352:Сингапур-Янгон',
'989|853:Стамбул-Париж','747|452:Сургут-Бургас','747|997:Сургут-Хургада','597|452:Уфа-Бургас','597|1519:Уфа-Дубай','597|2296:Уфа-Подгорица',
'770|160:Хабаровск-Ньячанг','773|452:Челябинск-Бургас','1232|268:Шарджа-Коломбо');


//создание csv и запись название столбцов
$exelname =  array('id'=>'id','aircompanycode'=>'AirCompanyCode','departureiata'=>'DepartureIATA','arrivaliata'=>'ArrivalIATA',
'departureterminal'=>'DepartureTerminal','arrivalterminal'=>'ArrivalTerminal','aircraft'=>'AirCraft','flightnum'=>'FlightNum',
'departuredate'=>'DepartureDate','arrivaldate'=>'ArrivalDate','price'=>'Price','currency'=>'Currency','class'=>'Class','seats'=>'Seats',
'costusd'=>'Cost USD','costrub'=>'Cost RUB','costeur'=>'Cost EUR','orderlimit'=>'Order limit','returnway'=>'ReturnWay',
'aircompanycodereturn'=>'AirCompanyCode Return','departureiatareturn'=>'DepartureIATA Return','arrivaliatareturn'=>'ArrivalIATA Return',
'departureterminalreturn'=>'DepartureTerminal Return','arrivalterminalreturn'=>'ArrivalTerminal Return','aircraftreturn'=>'AirCraft Return',
'flightnumreturn'=>'FlightNum Return','departuredatereturn'=>'DepartureDate Return','arrivaldatereturn'=>'ArrivalDate Return',
'seatsreturn'=>'Seats Return','costreturnusd'=>'Cost Return USD','costreturnrub'=>'Cost Return RUB','costreturneur'=>'Cost Return EUR');
$erty = '';

foreach($exelname as $veve){
	$erty .= $veve.';';
}
$fk = fopen(dirname(__FILE__).DS.$namecsv,'a+');
chmod(dirname(__FILE__).DS.$namecsv,0755);
fwrite( $fk,$erty."\r\n");fclose($fk);




//получаем сессию
$uagent = chooseBrowser();
$url1 = 'http://www.transaerotour.com/page/nalichie-mest-na-aviareisah-284/';
$conte = get_page($url1,$uagent,$post=0,$head=0,$setcooc=0);
$conte = phpQuery::newDocument($conte);
$sese = trim((string)$conte->find('input#viewStateHidenFieldId')->attr('value'));


$url = 'http://www.transaerotour.com/page/nalichie-mest-na-aviareisah-284/';

//проходим в цикле сообщений
$kol = count($mass);
for($i = 0; $i < $kol; $i++){
//for($i = 2; $i < 3; $i++){
	$arra = array();
	$citynumber = $cityname = $reis = '';
	
	//получаем юзерагент
	$uagent = chooseBrowser();
	
	
	$arra = explode(':',$mass[$i]);
	$citynumber = $arra[0]; //id направления рейса
	$cityname = rus2translit($arra[1]);  //название направления рейса
	
		
	$reis = str_replace('|','%7C',$citynumber);//рейс

	$post = 'ctl00%24ScriptManager=ctl00%24ScriptManager%7Cctl00%24generalContent%24BtnShowQuotes&ctl00_ScriptManager_HiddenField=%3B%3BAjaxControlToolkit%2C%20Version%3D3.5.50401.0%2C%20Culture%3Dneutral%2C%20PublicKeyToken%3D28f01b0e84b6d53e%3Aru%3Abeac0bd6-6280-4a04-80bd-83d08f77c177%3A475a4ef5%3A5546a2b%3A497ef277%3Aeffe2a26%3Aa43b07eb%3A1d3ed089%3A751cdd15%3Adfad98a5%3A3cf12cf1&__EVENTTARGET=&__EVENTARGUMENT=&viewStateHidenFieldId='.$sese.'&__VIEWSTATE=&__EVENTVALIDATION=%2FwEWWAL%2BraDpAgLvyvGCBALsyvGCBALtyvGCBAKSxPr0AwLVzrefDwKim4rbDgLKspTOBAKqm8bUDQLIspjOBALkqcr1CgLou5%2BQCwKliZnLCgLz34ibDALeoIPdBAK07NC1CAKw7JTGDQKU9abCDQLBoMvjAwKX9aKvBALpu%2FfPBwKW9dKvBALHsuSmBwL5qb71CgKx7MC1CALgqb71CgLnqbr1CgLjqf6HDgKX9fLQBwL735RyAuCp%2FtoHAuG7q88HAtPOs58PAqWJ7e8DApT1groBAtnOl58PAq6bpu0DAuH3gqEJApiphv0KAu2Tt74LAsOXo6EMApmk95ANAt6Wva0NAsCWua0NAqT%2F74cHAqX%2Fn4YHAqn%2F28gMArXSpr8DAqvt7q0KAqPtks0MAqj%2Fv4INAti9vPYHArr%2F3JgLAqLXyZ8JAqXZ2dELAsqUku4PAqL9mPsJAreiubgNAonG%2BtUDAo3GvuYIAvrOiKYMAoyZ0lcCi73M3gECvNSF4AkC8r%2Bh4g4C7taSiAMC6tbGiQMC%2F5uX9goCv5S2ZQKe87u%2FBwKnsNi2AQKF%2FPH3AwKRt9SuAQK9y%2F7HBgLEvZiuBAL5jcC8BwLb8LOuDwLNnIyECAKU8sfjDAKikuHjBALq4IWTAQKztszwCQKB3%2FK3CgKklrjkDwK9wZDHBwKRs%2BXmDQKdvPaNBgK%2B4Yn4CTVCGH3miUJZGuR%2FKgjwZsxpXCxfQ5RIKeWNbONv11UM&ctl00%24Login%24ctl01=&ctl00%24Login%24ctl02=&ctl00%24generalContent%24DdlDirections='.$reis.'&ctl00%24generalContent%24MultiTwinDatepicker%24TxtMultiDatepickerFrom='.$dayot.'&ctl00%24generalContent%24MultiTwinDatepicker%24TxtMultiDatepickerTo='.$daytyda.'&ctl00%24generalContent%24MultiTwinDatepicker%24DaysShift=7&ctl00%24generalContent%24MultiTwinDatepicker%24hidErrDate=%D0%9D%D0%B0%D1%87%D0%B0%D0%BB%D1%8C%D0%BD%D0%B0%D1%8F%20%D0%B4%D0%B0%D1%82%D0%B0%20%D0%BD%D0%B5%20%D0%BC%D0%BE%D0%B6%D0%B5%D1%82%20%D0%B1%D1%8B%D1%82%D1%8C%20%D0%B1%D0%BE%D0%BB%D1%8C%D1%88%D0%B5%20%D0%BA%D0%BE%D0%BD%D0%B5%D1%87%D0%BD%D0%BE%D0%B9!&ctl00%24pageMessenger%24hidMessage=&ctl00%24pageMessenger%24hidRedirect=&__ASYNCPOST=true&ctl00%24generalContent%24BtnShowQuotes=%D0%9F%D0%BE%D0%BA%D0%B0%D0%B7%D0%B0%D1%82%D1%8C';
	

	$content = $table = '';
	$content = get_page($url,$uagent,$post,$head=0,$setcooc=0);
	$content = phpQuery::newDocument($content);
	$table = trim((string)$content->find('table#Table2')->html());
	
	$table = preg_replace('~<table class="QuotesInnerTable".*?>.*?</table>~si', '', $table);
	
	$blok_tr = '';
	preg_match_all('~<table class="tbl_1"(.*)<\/table>~isU',$table,$blok_tr);
	$blok_tr = $blok_tr[0];
	if(!empty($blok_tr)){
		
		//записываем сообщение рейса при смене пары городов
		$resu =  array('id'=>'','aircompanycode'=>'','departureiata'=>'','arrivaliata'=>'','departureterminal'=>'','arrivalterminal'=>'','aircraft'=>'','flightnum'=>'','departuredate'=>'','arrivaldate'=>'','price'=>'','currency'=>'','class'=>'','seats'=>'','costusd'=>'','costrub'=>'','costeur'=>'','orderlimit'=>'','returnway'=>'','aircompanycodereturn'=>'','departureiatareturn'=>'','arrivaliatareturn'=>'','departureterminalreturn'=>'','arrivalterminalreturn'=>'','aircraftreturn'=>'','flightnumreturn'=>'','departuredatereturn'=>'','arrivaldatereturn'=>'','seatsreturn'=>'','costreturnusd'=>'','costreturnrub'=>'','costreturneur'=>'');
	
		$resu['seats'] = $cityname;
	
		$fdsd = '';
		foreach($resu as $key_kd => $val_kd){
			$fdsd .= $val_kd.';';
		}
		$fkd = fopen(dirname(__FILE__).DS.$namecsv,'a+');fwrite( $fkd,$fdsd."\r\n");fclose($fkd);
		
		//проходим по таблицам направлений (всего два)
		for($q = 0; $q <= 1; $q++){
		//for($q = 0; $q <= 0; $q++){
			$bltr = '';
			
			preg_match_all('~<tr(.*)<\/tr>~isU',$blok_tr[$q],$bltr);
			$bltr = $bltr[0];

			//проходим по строкам рейсов
			$kol_tr = count($bltr);
			
			//определяем номер столбца экономического класса
			$v_class = $key_class = '';
			$v_class = phpQuery::newDocument($bltr[0]);
			for($e = 3; $e <= 7; $e++){
				$class_vr = '';
				$class_vr = trim((string)$v_class->find('td:eq('.$e.')')->text());
				 
				if (strripos($class_vr, 'Эконом класс (Uch)') !== false){
					$key_class = $e;
					break;
				}elseif(strripos($class_vr, 'Экономический класс( U)') !== false){
					$key_class = $e;
					//break;
				}
			}

			//проходим по рейсам
			for($w = 1; $w < $kol_tr; $w++){
			//for($w = 1; $w < 2; $w++){
				$resu =  array('id'=>'','aircompanycode'=>'','departureiata'=>'','arrivaliata'=>'','departureterminal'=>'','arrivalterminal'=>'','aircraft'=>'','flightnum'=>'','departuredate'=>'','arrivaldate'=>'','price'=>'','currency'=>'','class'=>'','seats'=>'','costusd'=>'','costrub'=>'','costeur'=>'','orderlimit'=>'','returnway'=>'','aircompanycodereturn'=>'','departureiatareturn'=>'','arrivaliatareturn'=>'','departureterminalreturn'=>'','arrivalterminalreturn'=>'','aircraftreturn'=>'','flightnumreturn'=>'','departuredatereturn'=>'','arrivaldatereturn'=>'','seatsreturn'=>'','costreturnusd'=>'','costreturnrub'=>'','costreturneur'=>'');
				
				$con_tr = '';
				$con_tr = phpQuery::newDocument($bltr[$w]);
				
				//получаем дату
				$datereiz = '';
				$datereiz = trim((string)$con_tr->find('td:eq(0)')->html());
				$datereiz = preg_replace("~[^0-9.,]~","",$datereiz);

				
				//получаем номер рейса и код авиокомпании
				$kod = '';
				$arra1 = $arra2 = array();
				$kod = trim((string)$con_tr->find('td:eq(1)')->html());
				$arra1 = explode('<br>',$kod);
				
				$arra2 = explode("\xC2\xA0",$arra1[0]);
				$resu['aircompanycode'] = strip_tags(trim($arra2[0]));//код авиокомпании
				$resu['flightnum']      = strip_tags(trim($arra2[1]));//номер рейса
				
				$arra4 = array();
				$arra4 = explode('-',$arra1[1]);
				
				$resu['departuredate'] = get_date($datereiz).' '.trim($arra4[0]).':00.0000000';//дата и время вылета
				
				//определяем даты прилета путем сравнения времени прилета с временем вылета
				$time_t = '';
				$sed  = $sed1 = array();
				$sed  = explode(':',$arra4[0]); //время вылета
				$sed1 = explode(':',$arra4[1]); //время прилета
				if( $sed[0] > $sed1[0] ){
					$sed3 = array();
					$sed3 = explode('-',get_date($datereiz));
					//$time_t = date($sed3[2].".".$sed3[1].".".$sed3[0],strtotime("+1 days"));
					$time_t =  date( "Y-m-d", strtotime( $sed3[2]."-".$sed3[1]."-".$sed3[0]."+1 days" ) );
				}else{
					$time_t = get_date($datereiz);
				}

				$resu['arrivaldate'] = $time_t.' '.trim($arra4[1]).':00.0000000';//дата и время прилета
				
				//получаем кода мест вылета и прилета
				$mesto = '';
				$arra3 = array();
				$mesto = trim((string)$con_tr->find('td:eq(2)')->html());
				$arra3 = explode('-',$mesto);

				$tudu = '';
				$tudu = strlen(trim($arra3[0]));
				if($tudu > 3){
					
					$resu['departureiata']     = substr(trim($arra3[0]), 0, -1); //код места вылета 
					//$resu['departureiata']     = rus2translit(substr(trim($arra3[0]), 0, -1));
					$resu['departureterminal'] = substr(trim($arra3[0]), -1);	 //код терминала
				}else{
					$resu['departureiata'] = trim($arra3[0]);//код места вылета
					//$resu['departureiata'] = rus2translit(trim($arra3[0]));
				}
				$sudu = '';
				$sudu = strlen(trim($arra3[1]));
				if($sudu > 3){
					
					$resu['arrivaliata']   = substr(trim($arra3[1]), 0, -1);//код места прилета
					//$resu['arrivaliata']     = rus2translit(substr(trim($arra3[1]), 0, -1));
					$resu['arrivalterminal']   = substr(trim($arra3[1]), -1);   //код терминала
				}else{
					$resu['arrivaliata']   = trim($arra3[1]);//код места прилета
					//$resu['arrivaliata']     = rus2translit(trim($arra3[1]));
				}
				
				
				
				//получаем класс полета
				if(!empty($key_class)){
					$class = '';
					$class = trim((string)$con_tr->find('td:eq('.$key_class.')')->text());
					//$class = trim((string)$con_tr->find('td.quoteYes')->text());
					
					if($class == '+'){
						$resu['seats'] = 'many';
					}elseif($class == 'МЕСТ НЕТ'){
						$resu['seats'] = 'none';
					}elseif($class == 'МАЛО'){
						$resu['seats'] = 'few';
					}elseif($class == 'ПОД ЗАПРОС'){
						$resu['seats'] = 'request';
					}else{
						$resu['seats'] = 'none';
					}
				}else{
					$resu['seats'] = 'none';
					
				}
				
				$resu['returnway'] = 0;
				$resu['orderlimit'] = 3;
				$resu['class'] = 1;
				
				$fds = '';
				foreach($resu as $key_k => $val_k){
					$fds .= $val_k.';';
				}
				//$fds = rtrim($fds,';');
				$fk = fopen(dirname(__FILE__).DS.$namecsv,'a+');fwrite( $fk,$fds."\r\n");fclose($fk);
			}
			
		}
	}
}

$pikm = 'Пиковые затраты памяти: '. preg_replace('/(?<=\d)(?=(\d{3})+(?!\d))/', ' ', memory_get_peak_usage(true)).' Байт <br>';
$finishtime = microtime(1); 
$totaltime = $finishtime - $startime; 
$vremya = 'Время: '.((float)(round($totaltime)*1000)/1000).' секунд <br>'; 
$mem = 'Всего затраты памяти: '. preg_replace('/(?<=\d)(?=(\d{3})+(?!\d))/', ' ', (memory_get_usage() - $mem_start)).' Байт';

$blok_xar = array();
$blok_xar = explode($_SERVER['SERVER_NAME'],dirname(__FILE__).DS.$namecsv);


echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title></title>
	<link rel="stylesheet" type="text/css" href="Classes/au.css">
</head>
<body>
<form id="form" name="form" action="" method="post">
	<div><BR>
	<p>Источник http://online.transaerotour.com/</p>
	'.$pikm.$vremya.$mem.'
	<p></p>
	<p>Ссылка на скачивание:<a href="'.$blok_xar[1].'">Скачать</a><br></p>
	</div>	
	</form>
</body>
</html>';


}else{
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title></title>
	<link rel="stylesheet" type="text/css" href="Classes/au.css">
</head>
<body>
<form id="form" name="form" action="" method="post">
	<div><BR>
	<p>Источник http://online.transaerotour.com/</p>
		<input type="text" name="dayyy" value="" placeholder="300"/><br />
		<button type="submit" name="send" value="send">Пуск</button>
	<p></p>
	</div>	
	</form>
</body>
</html>';


}








?>