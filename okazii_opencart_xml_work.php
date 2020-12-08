<?php
//if($_SERVER['REMOTE_ADDR']!='217.156.103.4')
	//die();
	
##################################################################################################
######################### DATAFEED OKAZII OPENCART ( 2013 ) ######################################
##################################################################################################

//-------------------------------------------------------------------------------------------------------------------------------
set_time_limit(0);
ignore_user_abort();
error_reporting(E_ALL ^ E_NOTICE);
	
//-------------------------------------------------------------------------------------------------------------------------------
		$id_option_marime = array(); // '14','15'
		$id_option_culoare = array(); // '13'
	//	$id_grup_attr = '9';
	//	$id_attr = '49';

//--------------------------------------------- include configuration files -----------------------------------------------------
if(file_exists('./config.php'))
{
	require_once('./config.php');
	require_once(DIR_SYSTEM . 'startup.php');
}
else die('Configuration files not found! Please make sure the script is placed in the root directory.');

/*
	error_reporting(0);
	ini_set("display_errors",0);
	error_reporting(0);
/**/
//*
	error_reporting(E_ALL);
	ini_set("display_errors",1);
	error_reporting(E_ALL);
/**/
header("Content-Type: text/xml; charset=utf-8",1);
header("Cache-Control: no-cache, must-revalidate",1);

//-------------------------------------------------------------------------------------------------------------------------------

//------------------------------------------------ connection with the database -------------------------------------------------
$conn = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die('Connection to MySql failed!');
$db = mysql_select_db(DB_DATABASE, $conn) or die('Database not found!');
//-------------------------------------------------------------------------------------------------------------------------------

//------------------------------------------------- define column separator -----------------------------------------------------
$okazii_Column_Separator = ';';
$image_Path = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/image/';
//-------------------------------------------------------------------------------------------------------------------------------

//-------------------------------------------------- define tables --------------------------------------------------------------
$table_setting               			= DB_PREFIX.'setting';
$table_currency               			= DB_PREFIX.'currency';
$table_language              			= DB_PREFIX.'language';
$table_category              			= DB_PREFIX.'category';
$table_category_description  			= DB_PREFIX.'category_description';
$table_product_to_category	 			= DB_PREFIX.'product_to_category';
$table_manufacturer			 			= DB_PREFIX.'manufacturer';
$table_product				 			= DB_PREFIX.'product';
$table_product_description	 			= DB_PREFIX.'product_description';
$table_tax_rate				 			= DB_PREFIX.'tax_rate';
$table_product_image 		 			= DB_PREFIX.'product_image';
$table_product_special		 			= DB_PREFIX.'product_special';
$table_option_value_description			= DB_PREFIX.'option_value_description';
$table_product_option_value				= DB_PREFIX.'product_option_value';

$tabel_product_attribute				= DB_PREFIX.'product_attribute';
$tabel_attribute						= DB_PREFIX.'attribute';
$tabel_attribute_group					= DB_PREFIX.'attribute_group';
$tabel_attribute_group_description		= DB_PREFIX.'attribute_group_description';
//-------------------------------------------------------------------------------------------------------------------------------
mysql_query("SET NAMES utf8");
mysql_query("set CHARACTER SET utf8");
//---------------------------------------------- extract language ID ------------------------------------------------------------
$sql =  "
		SELECT	language_id 
		FROM 	$table_language
		WHERE 	code = (
							SELECT 	value 
							FROM 	$table_setting 
							WHERE 	$table_setting.key='config_language'
							)
		";
$res = mysql_query($sql);
$default_Language_ID = mysql_result($res, 0);
//-------------------------------------------------------------------------------------------------------------------------------

//-------------------------------------------------- extract currency -----------------------------------------------------------
$sql =  "
		SELECT	code, $table_currency.value as rate
		FROM 	$table_setting  LEFT JOIN $table_currency ON $table_setting.value = code
		WHERE 	$table_setting.key='config_currency'
		";
$res = mysql_query($sql);
$row = mysql_fetch_assoc($res);
$default_Currency = $row['code'];
$currency_rate = $row['rate'];
unset($res);unset($row);
//-------------------------------------------------------------------------------------------------------------------------------

//--------------------------------------------- extract categories from db ------------------------------------------------------
$sql =  "
		SELECT 		$table_category.category_id, $table_category.parent_id, $table_category_description.name
		FROM 		$table_category 
		LEFT JOIN 	$table_category_description ON ($table_category .category_id = $table_category_description.category_id) 
		WHERE 		language_id = $default_Language_ID
		";
$res = mysql_query($sql);

while ( $field = mysql_fetch_assoc($res) ){
	$category_Array[$field['category_id']] = $field;
}
//-------------------------------------------------------------------------------------------------------------------------------

//------------------------------ extract the peoducts with discounts-------------------------------------------------------------
$sql =  "
		SELECT product_id, price 
		FROM $table_product_special
		WHERE (date_start <= DATE_FORMAT(NOW(), '%Y-%m-%d') OR date_start = '0000-00-00') 
		AND (date_end >= DATE_FORMAT(NOW(), '%Y-%m-%d') OR date_end = '0000-00-00')
		ORDER BY customer_group_id DESC
		";

$res = mysql_query($sql);
if ($res){
	while ($field = mysql_fetch_assoc($res)){
		$product_Price_with_Discount[$field['product_id']] = $field['price'];
	}
}
//-------------------------------------------------------------------------------------------------------------------------------

//--------------------------------------------- extract products ----------------------------------------------------------------

$sql = "

		SELECT 		
					$table_product_description.name AS name, 
					$table_product_to_category.category_id,
					$table_product_description.description AS description, 
					$table_manufacturer.name AS manufacturer,
					$table_product.product_id AS product_id, 
					$table_product.price AS price,
					$table_product.quantity AS stock,
					$table_product.image AS image_url,
					'0' AS VAT
		FROM 		$table_product
		LEFT JOIN 	$table_product_description ON ($table_product.product_id = $table_product_description.product_id AND $table_product_description.language_id = $default_Language_ID )
		LEFT JOIN 	$table_manufacturer ON $table_product.manufacturer_id =$table_manufacturer.manufacturer_id
		LEFT JOIN 	$table_product_to_category ON $table_product.product_id = $table_product_to_category.product_id
		WHERE 		$table_product.status = '1'
		ORDER BY 	$table_product.product_id ASC, $table_product_to_category.category_id DESC
		";
$res = mysql_query($sql);
if (!$res) {
    echo "Could not successfully run query () from DB: " . mysql_error();
    exit;
}
//$check_ID = -1; //need it in order to check for duplicates

$xmlFieldsTemplate = <<<XML
<AUCTION>
    <UNIQUEID>{uniqueId}</UNIQUEID>
    <TITLE><![CDATA[{title}]]></TITLE>
    <CATEGORY><![CDATA[{category}]]></CATEGORY>
    <DESCRIPTION><![CDATA[{description}]]></DESCRIPTION>
    <PRICE>{price}</PRICE>
	{discount_price}
    <CURRENCY>{currency}</CURRENCY>
    <AMOUNT>{amount}</AMOUNT>
    <PHOTOS>
        {productPhotos}
    </PHOTOS>
	{stocks}
	{altele}
</AUCTION>
XML;
$xmlItemF   = array('{uniqueId}', '{title}', '{category}', '{description}', '{price}', '{discount_price}', '{currency}', '{amount}', '{productPhotos}','{stocks}', '{altele}');

echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
echo "<OKAZII>"."\r\n";
$produse_check = array();
$i = 0;
while ( $product = mysql_fetch_assoc($res)) {
	//--------------- skip duplicates (ignored) --------------------------
	if(isset($produse_check[$product['product_id']])) continue;
	$produse_check[$product['product_id']]=$product['product_id'];
	//$check_ID = ($check_ID == $product['product_id']) ? 'duplicate' : $product['product_id'] ;
	//if($check_ID == 'duplicate') continue ;
	//---------------------------------------------------------------------------------------------------------------------------

	if(!isset($_GET['skipcond'])) if($product['stock'] < 1) continue;

	//------------------------------------------- build category tree for each product ------------------------------------------
	$product['category_array'] = array();
	if($product['category_id'] >= 0){
		$category = $product['category_id'];
		do
		{
			$product['category_array'][] = @$category_Array[$category]['name'];
			$category = @$category_Array[$category]['parent_id'];
		}	while($category > 0) ;
	}
	$product['category'] = okazii_clean_category(html_entity_decode(join(' > ', array_reverse($product['category_array']))));
	//---------------------------------------------------------------------------------------------------------------------------
	
	//------------------------------ extract all images associated with the product ---------------------------------------------
	$product['image_url'] = (!empty($product['image_url']))  ? "<URL>".$image_Path . $product['image_url']."</URL>"."\r\n" : "" ; //default image
	$sql_images =   "	SELECT 		$table_product_image.image
						FROM 		$table_product_image 
						WHERE 		product_id = '" . $product['product_id'] . "'";
	$res_images = mysql_query($sql_images);
	while ( $image = mysql_fetch_assoc($res_images) ) {
		$product['image_url'] .= "<URL>" . $image_Path . $image['image'] . "</URL>" . "\r\n" ;
	}
	//---------------------------------------------------------------------------------------------------------------------------
	$product['currency'] = $default_Currency;
	if (!empty($product_Price_with_Discount[$product['product_id']])){
		$product['discount_price'] = $product_Price_with_Discount[$product['product_id']];
		$product['discount_price'] *= (1 + ($product['VAT']/100));
		$product['discount_price'] = number_format( round( ($product['discount_price'] * $currency_rate) ,2), 2, '.', '' );
	}
	//---------------------------------------------------------------------------------------------------------------------------

	$product['price'] *= (1 + ($product['VAT']/100));
	$product['price'] = number_format( round( ( $product['price'] * $currency_rate ),2), 2, '.', '' );
	if(!isset($_GET['skipcond'])) if($product['price'] <= 0) continue;
	//---------------------------------------------------------------------------------------------------------------------------
	
	//------------------------------ extract attributes for product -------------------------------------------------------------
	if(count($id_option_marime)>0){
		$attr_marimiS = "
						SELECT $table_option_value_description.name, $table_product_option_value.quantity
						FROM $table_option_value_description
						LEFT JOIN $table_product_option_value ON $table_option_value_description.option_value_id = $table_product_option_value.option_value_id
						WHERE $table_product_option_value.product_id = " . $product['product_id'] . "
						AND $table_option_value_description.language_id = $default_Language_ID
						AND $table_option_value_description.option_id IN (".implode(",",$id_option_marime).")
							";
		if(!isset($_GET['skipcond'])) $attr_marimiS .= "AND $table_product_option_value.quantity > 0";
		$attr_marimiQ = mysql_query($attr_marimiS);
	}
	else { $attr_marimiQ = false; }
	if(isset($arr_marimi)) unset($arr_marimi);
	$arr_marimi	= array();
	$i_marimi = 0;
	if($attr_marimiQ){
		if(mysql_num_rows($attr_marimiQ)>0){
			while($attr_marimiF = mysql_fetch_assoc($attr_marimiQ)){
				$arr_marimi[$i_marimi]['valoare'] = $attr_marimiF['name'] ;
				$arr_marimi[$i_marimi]['amount'] = $attr_marimiF['quantity'];
				$i_marimi++;
			}
		}
	}
	if(count($id_option_culoare)>0){
		$attr_culoriS = "
						SELECT $table_option_value_description.name, $table_product_option_value.quantity
						FROM $table_option_value_description
						LEFT JOIN $table_product_option_value ON $table_option_value_description.option_value_id = $table_product_option_value.option_value_id
						WHERE $table_product_option_value.product_id = " . $product['product_id'] . "
						AND $table_option_value_description.language_id = $default_Language_ID
						AND $table_option_value_description.option_id = IN (".implode(",",$id_option_culoare).")
							";
		if(!isset($_GET['skipcond'])) $attr_culoriS .= "AND $table_product_option_value.quantity > 0";
		$attr_culoriQ = mysql_query($attr_culoriS);
	}
	else { $attr_culoriQ = false; }
	if(isset($arr_culori)) unset($arr_culori);
	$arr_culori	= array();
	$i_culori = 0;
	if($attr_culoriQ){
		if(mysql_num_rows($attr_culoriQ)>0){
			while($attr_culoriF = mysql_fetch_assoc($attr_culoriQ)){
				$arr_culori[$i_culori]['valoare'] = $attr_culoriF['name'] ;
				$arr_culori[$i_culori]['amount'] = $attr_culoriF['quantity'] ;
				$i_culori++;
			}
		}
	}
	
	$stocks = '';
	$stocks_i = 0;
	$product_attributes_output = NULL;
	if($i_marimi > 0 || $i_culori > 0){
		if($i_marimi > 0){
			foreach($arr_marimi as $i_marime => $marime){
				if($i_culori > 0){
					foreach($arr_culori as $i_culoare => $culoare){
						$stocks .= "<STOCK><MARIME>".$marime['valoare']."</MARIME>"."<CULOARE>".$culoare['valoare']."</CULOARE>"."<AMOUNT>".(min($marime['amount'],$culoare['amount']))."</AMOUNT></STOCK>";
					}
				}
				else
					$stocks .= "<STOCK><MARIME>".$marime['valoare']."</MARIME>"."<AMOUNT>".$marime['amount']."</AMOUNT></STOCK>";
			}
		}
		elseif($i_culori > 0){
			foreach($arr_culori as $i_culoare => $culoare){
				$stocks .= "<STOCK><CULOARE>".$culoare['valoare']."</CULOARE>"."<AMOUNT>".$culoare['amount']."</AMOUNT></STOCK>";
			}
		}
		if($stocks!='')
			$stocks = "<STOCKS>".$stocks."</STOCKS>";
	}
	//---------------------------------------------------------------------------------------------------------------------------
	
	//----------------------------------- clean output data ---------------------------------------------------------------------
	$product['name'] = okazii_clean_name(html_entity_decode($product['name']));
	//$product['category'] = 
	$product['description'] = okazii_clean_description(html_entity_decode($product['description']), ($product['manufacturer']), ($product_attributes_output));
	$product['description'] = preg_replace("/\x1E/imUs", "", $product['description']);
	$product['description'] = preg_replace("/\x1D/imUs", "", $product['description']);
	$product['description'] = preg_replace("/\x1C/imUs", "", $product['description']);
	$product['description'] = preg_replace("/\x1F/imUs", "", $product['description']);
//	$product['description']=html_entity_decode($product['description']);
	if( strlen($product['description']) <= 3) $product['description'] = $product['name']."<br/>".$product['description'];
	//---------------------------------------------------------------------------------------------------------------------------
	
	//------------------------------ format currency ( allowed RON and EUR )-----------------------------------------------------
	if (($product['currency']!='RON')&&($product['currency']!='EUR')) $product['currency'] ='RON';
	else $product['currency'] = $product['currency'];
	//---------------------------------------------------------------------------------------------------------------------------

	//-------------------------------------- output products --------------------------------------------------------------------
	
	//$brand = html_entity_decode($product['manufacturer']);
	//$product['color']='alta';
	
	$uniqueId = $product['product_id'];
	$title = $product['name'];
	if(trim($product['category'])==''){
		$title_tmp = explode(" ",$title);
		$product['category'] = "categ > ".$title_tmp[0];
	}
	$title_len = strlen($title);
	if($title_len <= 5)
		$title .= " ( cod produs: ".$uniqueId." )";
	if($title_len >= 145){
		$product['description'] = $title . "<br/>" . $product['description'];
		$title = substr($title."...", 0, 145);
	}
	$category = $product['category'];
	
/*
	if(stripos($category, "Parfumuri si Cosmetice")!==false && trim($product['manufacturer'])!=''){
		$category .= " > ".$product['manufacturer'];
	}
	
	if(stripos($category, "PARFUMURI SI COSMETICE >")!==false)
		$category = str_ireplace("PARFUMURI SI COSMETICE >", "Parfumuri si Cosmetice >", $category);
*/

	$product['description'] = nl2br($product['description']);
	$description = $product['description'];
	$price = $product['price'] * 1.19;
	//$price = $product['price'];
	$discount_price = "";
/*
	if(in_arrayi(trim($category), array('FEMEI > Incaltaminte', 'FEMEI > Incaltaminte > Balerini', 'FEMEI > Incaltaminte > Botine', 
	'FEMEI > Incaltaminte > Cizme', 'FEMEI > Incaltaminte > Ghete', 'FEMEI > Incaltaminte > Pantofi', 'FEMEI > Incaltaminte > Pantofi Sport', 
	'FEMEI > Incaltaminte > Sandale', 'FEMEI > Incaltaminte > Slapi', 'FEMEI > Incaltaminte > Tenisi', 'FEMEI > UGG'))){
		if(isset($product['discount_price']))
			$product['discount_price'] = $product['discount_price'] - (20/100*$product['discount_price']);
		else
			$product['discount_price'] = ($price - (20/100*$price));
	}
*/
	if(isset($product['discount_price']) && $product['discount_price']!=$product['price'])
		$discount_price = "<DISCOUNT_PRICE>".$product['discount_price']."</DISCOUNT_PRICE>";
	$currency = $product['currency'];
	$amount = $product['stock'];
	$productPhotos = $product['image_url'];
	$altele = '';
	if(trim($product['manufacturer'])!='')
		$altele .= "<BRAND><![CDATA[".$product['manufacturer']."]]></BRAND>";
		
	$xmlContent = str_replace($xmlItemF, array($uniqueId, $title, $category, $description, $price, $discount_price, $currency, $amount, $productPhotos, $stocks, $altele), $xmlFieldsTemplate);

	libxml_use_internal_errors(true);
	$show = true;
	try{
		$xml = new SimpleXMLElement($xmlContent);
	} catch (Exception $e){
		try{
			unset($xml);
			//$description=$title;
			$xmlContent = str_replace($xmlItemF, array($uniqueId, $title, $category, $description, $price, $discount_price, $currency, $amount, $productPhotos, $stocks, $altele), $xmlFieldsTemplate);
			$xml = new SimpleXMLElement($xmlContent);
		} catch (Exception $e){
			$show = false;
			unset($xml);
			//continue;
		}
	}
	if($show){
		if(isset($xml) && (
			$xml->TITLE[0]==''
			|| strlen($xml->TITLE[0])<=5
			|| strlen($xml->TITLE[0])>=150
			|| $xml->CATEGORY[0]==''
			|| strlen($xml->CATEGORY[0])<=3
			|| strlen($xml->DESCRIPTION[0])<=3
			|| strlen($xml->PRICE[0])<1
			|| ((float)$xml->PRICE[0])==0
			|| !is_numeric((int)$xml->AMOUNT[0])
			|| $xml->AMOUNT[0]<= 0
		)){
			$show = false;
		}
	}
	//$show=true;
	if($show || isset($_GET['skipcond'])) {
//		$i++;
	//	if($i<100)
			echo $xmlContent."\r\n";
	}
	else {
		echo $xmlContent."\r\n";
		//error_log($xmlContent,3,'okazii_log.log');
	}
	//---------------------------------------------------------------------------------------------------------------------------
}

echo "</OKAZII>";

//-------------------------------------------------------------------------------------------------------------------------------
/*
function okazii_diacritice( $string ) {
	$diacritice = array( "'Ă'", "'ă'", "'Â|&Acirc;'", "'â|&acirc;'", "'Î|&Icirc;'", "'î|&icirc;'", "'Ș'", "'ș'", "'Ş'", "'ş'","'Ț'", "'ț'", "'Ţ'", "'ţ'" );
	$diacritice_corecte = array("&#258;", "&#259;", "&#194;", "&#226;", "&#206;", "&#238;", "&#x218;", "&#x219;", "&#350;", "&#351;", "&#354;", "&#355;", "&#354;", "&#355;");
	return preg_replace($diacritice, $diacritice_corecte, $string);
	//return $string;
}*/
function okazii_diacritice( $string ) {
	$diacritice = array( 0=>"'Ă'", 1=>"'ă'", 2=>"'Â|&Acirc;'", 3=>"'â|&acirc;'", 4=>"'Î|&Icirc;'", 5=>"'î|&icirc;'", /*6=> "'?'", 7=>"'?'",*/ 8=>"'Ş|Ș'", 9=>"'ş|ș'", /*10=>"'?'", 11=>"'?'",*/ 12=>"'Ţ'", 13=>"'ţ'" );
	$diacritice_corecte = array(0=>"&#258;", 1=>"&#259;", 2=>"&#194;", 3=>"&#226;", 4=>"&#206;", 5=>"&#238;", /*6=>"&#x218;", 7=>"&#x219;",*/ 8=>"&#350;", 9=>"&#351;", /*10=>"&#354;", 11=>"&#355;",*/ 12=>"&#354;", 13=>"&#355;");
	return preg_replace($diacritice, $diacritice_corecte, $string);
}

//---------------------------------------------------------------------------------------------------------------------------
//----------------------------------------- elimina dicariticele-------------------------------------------------------------
function okazii_fara_diacritice( $string ) {
	$diacritice = array( "'Ă|&#258;'", "'ă|&#259;'", "'Â|&Acirc;|&#194;'", "'â|&acirc;|&#226;'", "'Î|&Icirc;|&#206;'", "'î|&icirc;|&#238;'", "'Ș|Ş|&#x218;'", "'ș|ş|&#x219;'", "'Ț|Ţ|&#351;'", "'ț|ţ|&#355;'");
	$diacritice_corecte = array("A", "a", "A", "a", "I", "i", "S", "s", "T", "t");
	return preg_replace($diacritice, $diacritice_corecte, $string);

}
//---------------------------------------------------------------------------------------------------------------------------
//--------------------------------- clean product name ---------------------------------------------------------------------
function okazii_clean_name( $name ){
	$name = strip_tags($name);
	$name = okazii_diacritice( $name );
	//$name = okazii_escape_csv( $name );
	return $name;
}
//---------------------------------------------------------------------------------------------------------------------------

//----------------------------------- clean category ------------------------------------------------------------------------
function okazii_clean_category( $category ){
	$category = strip_tags($category);
	$category = okazii_fara_diacritice( $category );
	//$category = okazii_escape_csv($category);
	return $category;
}
//---------------------------------------------------------------------------------------------------------------------------
//------------------------------------- clean description -------------------------------------------------------------------
function okazii_clean_description( $description, $manufacturer, $attributes ){
	$product['attributes'] = $attributes;
	$description = strip_tags($description, '<b><br /><em><h1><h2><h3><h4><h5><h6><hr /><i><img /><li><ol><p><small><table><td><th><tr><ul>');
	$description .= (!empty($manufacturer)) ? "<br/><b>Producator</b> : $manufacturer" : "" ;
	$description .= (!empty($attributes)) ? '<br/><br/>Masuri disponibile : <b style="font-size: 22px; color: red; font-weight: 600; font-family: sans-serif;">' . $attributes . "</b><br/>" :	"";
	$description = get_correct_utf8_string( $description );
	//$description = okazii_escape_csv($description);
	return $description;
}
//---------------------------------------------------------------------------------------------------------------------------
//----------------------------------- escape fields ( csv format ) ----------------------------------------------------------
function okazii_escape_csv ( $string){
	$string = '"'.str_replace('"', '""', $string).'"';
	return $string;
}
//---------------------------------------------------------------------------------------------------------------------------
function in_arrayi($needle, $haystack) {
	return in_array(strtolower($needle), array_map('strtolower', $haystack));
}
//---------------------------------------------------------------------------------------------------------------------------
function get_correct_utf8_string($string) {
	$strText = okazii_diacritice($string);

	$strEncoding = mb_detect_encoding($strText, "ASCII, UTF-8, ISO-8859-2, ISO-8859-1, CP1252, CP1251");
	//var_dump($strEncoding);
	if ($strEncoding == 'ISO-8859-2') {
		$strEncoding = 'CP1252';
	}
	return mb_convert_encoding($strText, "UTF-8", $strEncoding);
}
exit;
?>