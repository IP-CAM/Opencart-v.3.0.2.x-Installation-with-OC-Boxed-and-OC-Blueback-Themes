<?php

Okazii_Connector_Service::Run();

final class Okazii_Connector_Service
{
	/**
	 * Public key used to check signature
	 * @var string
	 */
	protected $strPublicKey = '';
	
	/**
	 * IPs allowed to use the service
	 * @var string
	 */
	protected $arrAllowedIPs = array();
	
	/**
	 * Info: Platform, Adapters from service host
	 * @var array
	 */
	protected $arrInfo = array();
	
	/**
	 * List of remote procedure calls
	 * @var array
	 */
	protected $RPCs = array();
	
	/**
	 * Collected debug Info
	 * @var array
	 */
	protected $DebugInfo = array();
	
	/**
	 * Collected errors
	 * @var array
	 */
	protected $Errors = array();

	/**
	 * Safe Mode state
	 * @var boolean
	 */
	protected $SafeMode = false;
	
	/**
	 * Debug state
	 * @var boolean
	 */
	protected $Debug = false;
	
	/**
	 * Constructor - service setup
	 * @param string $strPublicKey
	 * @param string $strAllowedIP
	 */
	protected function __construct($strPublicKey, $arrAllowedIPs)
	{
		$this->strPublicKey = $strPublicKey;
		$this->arrAllowedIPs = $arrAllowedIPs;
		set_error_handler($error_handler = array($this, 'Error_Handler'), $error_types = E_ALL | E_STRICT);
	}

	/**
	 * Check if request is valid and read request data
	 * @param string $strRequestData
	 * @param string $strRequestSignature
	 * @return boolean
	 * @throws Okazii_Connector_Exception
	 */
	protected function Request($strRequestData, $strRequestSignature)
	{
		try {
			if($this->IsValidRequest($strRequestData, $strRequestSignature)) {
				$objRequestData = json_decode($strRequestData);
				$this->RPCs = $objRequestData->RPCs;
				$this->Debug($objRequestData->Debug);
				$this->SafeMode = $objRequestData->SafeMode;
				return true;
			} else return false;
		} catch (Okazii_Connector_Exception $Exception) {
			$this->AddError($Exception);
			return false;
		}
	}

	/**
	 * Collect error
	 * @param Exception $Exception
	 */
	protected function AddError(Exception $Exception)
	{
		$Error = array(
				'Type'		=>	get_class($Exception),
				'Code'		=>	$Exception->getCode(),
				'Message'	=>	$Exception->getMessage(),
				'File'		=>	$Exception->getFile(),
				'Line'		=>	$Exception->getLine()
		);

		if($this->Debug) {
			$Error['Trace'] = $Exception->getTraceAsString();
		}

		$this->Errors[] = $Error;
	}

	protected function Error_Handler($errno, $errstr, $errfile, $errline)
	{
		$Error = array(
			'Type'		=>	'PHP',
			'Code'		=>	$errno,
			'Message'	=>	$errstr,
			'File'		=>	$errfile,
			'Line'		=>	$errline
		);

		$this->Errors[] = $Error;
	}
	
	/**
	 * Gather some info
	 * @return NULL
	 */
	protected function LoadInfo()
	{
		$this->arrInfo['PHPVersion'] = PHP_VERSION;
		$this->arrInfo['OpenSSL'] = extension_loaded('openssl') ? 'Enabled' : 'Disabled';
		$this->arrInfo['ServiceMethods'] = array();
		$this->arrInfo['RemoteIP'] = $_SERVER['REMOTE_ADDR'];
		$this->arrInfo['Platforms'] = array();
		$strConnectorCode = file_get_contents(__FILE__);
		$strConnectorCode = preg_replace("/[P]ublicKey = '(.*)'/iUs","PublicKey = ''", $strConnectorCode);
		$this->arrInfo['BuildHash'] = md5($strConnectorCode);
		
		$ServiceReflection = new ReflectionClass(__CLASS__);
		foreach ($ServiceReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $objServiceMethod) {
			if(!in_array($objServiceMethod, $ServiceReflection->getMethods(ReflectionMethod::IS_STATIC))) {
				$this->arrInfo['ServiceMethods'][] = $objServiceMethod->name;
			}
		}
	}
	
	/**
	 * Check if request is OK - signature, client ip, request data format
	 * @param string $strRequestData
	 * @param string $strRequestSignature
	 * @return boolean
	 * @throws Okazii_Connector_Exception
	 */
	protected function IsValidRequest($strRequestData, $strRequestSignature) //AuthenticateRequest
	{
		if(extension_loaded('openssl')) {
			if(!openssl_public_decrypt(base64_decode($strRequestSignature), $strDataHash, $this->strPublicKey)) {
				throw new Okazii_Connector_Exception('Bad request - could not verify signature', Okazii_Connector_Exception::SERVICE_REQUEST_SIGNATURE_DECRYPT_ERROR);
			}
			if (md5($strRequestData) != $strDataHash) {
				throw new Okazii_Connector_Exception('Bad request - invalid signature', Okazii_Connector_Exception::SERVICE_REQUEST_SIGNATURE_INVALID);
			}
		}
		
		if(!in_array($_SERVER['REMOTE_ADDR'], $this->arrAllowedIPs) && !in_array(substr($_SERVER['REMOTE_ADDR'], 0,  strrpos($_SERVER['REMOTE_ADDR'], '.')), $this->arrAllowedIPs)) {
			throw new Okazii_Connector_Exception('Bad request - IP ' . $_SERVER['REMOTE_ADDR'] . ' not allowed', Okazii_Connector_Exception::SERVICE_REQUEST_IP_DENIED);
		}
		
		if(!is_string($strRequestData) || (!is_object(json_decode($strRequestData)) && !is_array(json_decode($strRequestData)))) {
			throw new Okazii_Connector_Exception('Bad request - Invalid JSON', Okazii_Connector_Exception::SERVICE_REQUEST_INVALID_JSON);
		}
		
		return true;
	}
	
	/**
	 * Load Adapter
	 * @param string $strPlatform
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function RegisterAdapter($strPlatform)
	{
		$strClassName = 'Okazii_Connector_Adapter_' . $strPlatform;
		
		try {
			$this->IsValidAdapter($strClassName);
			
			$objAdapter = new $strClassName();
			
			$AdapterInfo = $objAdapter->GetAdapterInfo();
			
			$this->arrInfo['Adapters'][$AdapterInfo['Name']]['Version'] = $AdapterInfo['Version'];
			$this->arrInfo['Adapters'][$AdapterInfo['Name']]['AdapterClassName'] = $strClassName;
			
			$AdapterReflection = new ReflectionClass($strClassName);
			foreach ($AdapterReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $objAdapterMethod) {
				if(!in_array($objAdapterMethod->name, array('__construct', 'InitPlatform')) && !in_array($objAdapterMethod, $AdapterReflection->getMethods(ReflectionMethod::IS_STATIC))) {
					$this->arrInfo['Adapters'][$AdapterInfo['Name']]['Methods'][] = $objAdapterMethod->name;
				}
			}
			
			if ($objAdapter->InitPlatform()) {
				$this->arrInfo['Platforms'][$strPlatform] = $objAdapter->getPlatformInfo();
				return true;
			} else return false;

		} catch (Okazii_Connector_Exception $Exception) {
			$this->AddError($Exception);
		}

	}
	
	/**
	 * Check if class is Adapter
	 * @param string $strClassName
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function IsValidAdapter($strClassName)
	{
		if (class_exists($strClassName) && is_subclass_of($strClassName, 'Okazii_Connector_Adapter')) {
			return true;
		}
		
		throw new Okazii_Connector_Exception('Adapter ' . $strClassName . ' does not exist', Okazii_Connector_Exception::SERVICE_ADAPTER_NOT_FOUND); //nu exista adaptorul
	}
	
	/**
	 * Check if adapters detected platform
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function HasDetectedPlatform()
	{
		try {
			if(count($this->arrInfo['Platforms']) < 1) {
				throw new Okazii_Connector_Exception('Platform not detected', Okazii_Connector_Exception::SERVICE_PLATFORM_NOT_DETECTED); // nu poate fi detectata plaforma
			}
			
			if(count($this->arrInfo['Platforms']) > 1) {
				throw new Okazii_Connector_Exception("Platform can't be detected reliable", Okazii_Connector_Exception::SERVICE_PLATFORM_NOT_DETECTED_RELIABLE); // nu poate fi detectata plaforma
			}
			
			foreach ($this->arrInfo['Platforms'] as $PlatformName => $Info) {
				$this->arrInfo['Platform'] = $PlatformName;
				$this->arrInfo['PlatformInfo'] = $Info;
			}
			
			unset($this->arrInfo['Platforms']);
			return true;
		} catch (Okazii_Connector_Exception $Exception) {
			$this->AddError($Exception);
		}
		
	}
	
	/**
	 * Run RPCs
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function Process()
	{
		if(!$this->SafeMode && !$this->HasDetectedPlatform()) {
			return false;
		}
		
		foreach ($this->RPCs as $Index => $RPC) {
			try {
				if (is_array($RPC->CallBack) && $this->IsValidAdapterRPC($RPC)) {
					$AdapterClassName = $this->arrInfo['Adapters'][$RPC->CallBack[0]]['AdapterClassName'];
					$CallBack = array(new $AdapterClassName($this->Debug), $RPC->CallBack[1]);
					$this->RPCs[$Index]->Response = call_user_func_array($CallBack, $RPC->Parameters);
					#exit();
				} elseif (is_string($RPC->CallBack) && $this->IsValidServiceRPC($RPC)) {
					$CallBack = array($this, $RPC->CallBack);
					$this->RPCs[$Index]->Response = call_user_func_array($CallBack, $RPC->Parameters);
				} else {
					throw new Okazii_Connector_Exception('Invalid callback format.', Okazii_Connector_Exception::SERVICE_RPC_INVALID_CALLBACK);
				}
			} catch (Okazii_Connector_Exception $Exception) {
				$this->AddError($Exception);
			}
		}
		
		return true;
	}
	
	/**
	 * Check if Service Rpc is ok - method exists, rpc is allowed, rpc parameters format
	 * @param stdClass $RPC
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function IsValidServiceRPC($RPC)
	{
		if(!method_exists($this, $RPC->CallBack)) {
			throw new Okazii_Connector_Exception('Method not found: ' . __CLASS__ . '::' . $RPC->CallBack . '()', Okazii_Connector_Exception::SERVICE_RPC_METHOD_NOT_FOUND);
		}
		
		if(!in_array($RPC->CallBack, $this->arrInfo['ServiceMethods'])) { // is registred
			throw new Okazii_Connector_Exception('Access denied for RPC: ' . __CLASS__ . '::' . $RPC->CallBack . '()', Okazii_Connector_Exception::SERVICE_RPC_ACCESS_DENIED);
		}
		
		if(!is_array($RPC->Parameters)) {
			throw new Okazii_Connector_Exception('Parameters must be sent as array', Okazii_Connector_Exception::SERVICE_RPC_INVALID_PARAMETERS);
		}
		
		return true;
	}
	
	/**
	 * Check if Adapter Rpc is ok - callback format, method exists, rpc is allowed, adapter is loaded, platform is installed, rpc parameters format
	 * @param stdClass $RPC
	 * @throws Okazii_Connector_Exception
	 * @return boolean
	 */
	protected function IsValidAdapterRPC($RPC) // IsValidAdapterRPC
	{
		if(!isset($RPC->CallBack[0]) || !isset($RPC->CallBack[1]) || !is_string($RPC->CallBack[0]) || !is_string($RPC->CallBack[1]) || count($RPC->CallBack) != 2) { // check if RPC is in format: array(Adapter, Method)
			throw new Okazii_Connector_Exception('Invalid adapter callback format.', Okazii_Connector_Exception::SERVICE_RPC_INVALID_CALLBACK);
		}
		
		if(!isset($this->arrInfo['Adapters'][$RPC->CallBack[0]])) { //check if adapter exists
			throw new Okazii_Connector_Exception('Adapter ' . $RPC->CallBack[0] . ' does not exists or is not registered.', Okazii_Connector_Exception::SERVICE_RPC_ADAPTER_NOT_LOADED);
		}
		
		if($this->arrInfo['Platform'] !== $RPC->CallBack[0]) { //IsPlatform
			throw new Okazii_Connector_Exception('RPC for ' . $RPC->CallBack[0] . '. Installed Platform: '. $this->arrInfo['Platform'], Okazii_Connector_Exception::SERVICE_RPC_PLATFORM_NOT_INSTALLED);
		}
		
		if(!is_callable(array($this->arrInfo['Adapters'][$RPC->CallBack[0]]['AdapterClassName'], $RPC->CallBack[1]))) { //IsAdapterCallableMethod
			throw new Okazii_Connector_Exception('Method not found: ' . $RPC->CallBack[0] . '::' . $RPC->CallBack[1] . '()', Okazii_Connector_Exception::SERVICE_RPC_METHOD_NOT_FOUND);
		}

		if(!in_array($RPC->CallBack[1], $this->arrInfo['Adapters'][$RPC->CallBack[0]]['Methods'])) { //IsAdapterRegistredMethod
			throw new Okazii_Connector_Exception('Access denied for RPC: ' . $RPC->CallBack[0] . '::' . $RPC->CallBack[1] . '()', Okazii_Connector_Exception::SERVICE_RPC_ACCESS_DENIED);
		}
		
		if(!is_array($RPC->Parameters)) {
			throw new Okazii_Connector_Exception('Parameters must be sent as array', Okazii_Connector_Exception::SERVICE_RPC_INVALID_PARAMETERS);
		}
		
		return true;
	}

	/**
	 * Change debug state
	 * @param boolean $State
	 */
	protected function Debug($State = false)
	{
		$this->Debug = ($State === true) ? true : false;
		$this->arrInfo['Debug'] = ($State === true) ? 'Enabled' : 'Disabled';
	}
	
	/**
	 * Change safemode state
	 * @param boolean $State
	 */
	protected function SafeMode($State = false)
	{
		$this->SafeMode = ($State === true) ? true : false;
		$this->arrInfo['SafeMode'] = ($State === true) ? 'Enabled' : 'Disabled';
	}

	/**
	 * Update Okazii_Connector
	 * @param string $ConnectorCode
	 * @param string $md5FileHash
	 * @return boolean
	 */
	public function Update($ConnectorCode, $md5FileHash)
	{
		if(md5($ConnectorCode) != $md5FileHash)
			return false;
		
		return file_put_contents(__FILE__, $ConnectorCode);
	}

	/**
	 * Create service json response
	 * @return string
	 */
	protected function Response()
	{
		$Response = array();
		$Response['Info'] = $this->arrInfo;
		$Response['RPC'] = $this->RPCs;
		$Response['Errors'] = $this->Errors;
		if($this->Debug) {
			$Response['DebugInfo'] = $this->DebugInfo;
		}
		return json_encode($Response);
	}
	
	/**
	 * Add debug info
	 * @param string $Debug
	 */
	protected function AddDebugInfo($DebugLabel, $DebugText = null)
	{
		if($this->Debug) {
			if($DebugText === null) {
				$DebugText = print_r($this, true);
			}
			$this->DebugInfo[$DebugLabel] = $DebugText;
		}
	}
	
	/**
	 * "Controller"
	 */
	public static function Run()
	{
		if(($_SERVER['REQUEST_METHOD'] !== 'POST') || !isset($_POST['Request']) || !isset($_POST['Signature'])) {
			header('HTTP/1.0 403 Forbidden');
			echo '<h1>Forbidden</h1>';
			exit();
		}
		
		$Service = new Okazii_Connector_Service(Okazii_Connector_Config::PublicKey, Okazii_Connector_Config::$AllowedIPs);

		if($Service->Request($_POST['Request'], $_POST['Signature'])) {
			$Service->LoadInfo();
			if(!$Service->SafeMode) {
				$Service->RegisterAdapter('OpenCart');
				$Service->RegisterAdapter('Magento');
				$Service->RegisterAdapter('WooCommerce');
				$Service->RegisterAdapter('Sample');
			}
			
			$Service->AddDebugInfo('before_Process');
			$Service->Process();
			$Service->AddDebugInfo('after_Process');

		}
		
		echo $Service->Response();
		exit();
	}
}

class Okazii_Connector_PlatformInfo{

	public $Name = '';
	public $Version = '';


}

class Okazii_Connector_Exception extends Exception
{
	const ADAPTER_PROPERTY_NOT_DEFINED = 51;
	
	const SERVICE_REQUEST_SIGNATURE_DECRYPT_ERROR	= 4001;
	const SERVICE_REQUEST_SIGNATURE_INVALID			= 4002;
	const SERVICE_REQUEST_IP_DENIED					= 4003;
	const SERVICE_REQUEST_INVALID_JSON				= 4004;
	
	const SERVICE_RPC_INVALID_CALLBACK				= 5001;
	const SERVICE_RPC_ADAPTER_NOT_LOADED			= 5002;
	const SERVICE_RPC_PLATFORM_NOT_INSTALLED		= 5003;
	const SERVICE_RPC_METHOD_NOT_FOUND				= 5004;
	const SERVICE_RPC_ACCESS_DENIED					= 5005;
	const SERVICE_RPC_INVALID_PARAMETERS			= 5006;
	
	const SERVICE_ADAPTER_NOT_FOUND					= 5015;
	
	const SERVICE_PLATFORM_NOT_DETECTED				= 5021;
	const SERVICE_PLATFORM_NOT_DETECTED_RELIABLE	= 5022;
}

class Okazii_Connector_Config
{
	const PublicKey = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAuwoFsjGnSB1NqMGZVFFp
ajSOY92CS/b//ji53PNVRDSGphb3ZEWzG5Ifme8uHw6GMM4j/zr3d4Jzr+2lVdVH
kLR45Mh5atiFMKI4DvgjvVjTjhfpRM+XsMwwyI3DzTYu3LUh1FVjx5m90yEKx+NG
nX0jvEKkhRje3PPC6Uz17S0TWcny+/56Tbz3N/UMRlxMs15gz+DltvWLB80lP4r6
wtK4mhe/lE3dmuQKuK/zccrLW/6ytJ7yZKGUVu+Q80+veOOgZnbnTGZ5gOvhoLab
p/FXhr/AIZxeUQE7sCepglQyT3ZxrtNVMtIx5KAg2AQkn/2ZGBHQUBUPYZcTZjb5
0KHlrtDVK+2rSQ358eG+It6SHexqJjOYkMPm95m51C2XM7MHTZLA3fbRkE7+dF9v
35AsSUVdkhGZnQMd+CDW318hMZds5Ax4c1UldZzKAp3NVAAeqZkHAvF1muaJ6Htu
jHsQA3ptG919QOUEFYuTDt0etjoHEcZgdloANPq3eh48+YbCc1Mwn4Q4I7SN66OG
Rtzy7paOc+xthT9i77wOvn70YRPa7h23YUUw36acMQOu56/2M1kdjW/j8l66PKnQ
r+syguHTB5TbK8KBv/ydapDhyyUJim3tDus8BDaIG2aP4ZKXb2QG6YmYq4e+dkj1
wgsjd43+QUZbyXapLWM0tWUCAwEAAQ==
-----END PUBLIC KEY-----
';

	public static $AllowedIPs = array(
		'192.168.101.131'
		,'86.104.214.1'
		,'86.104.214.2'
		,'86.104.214.3'
		,'86.104.214.4'
		,'172.27.101'
		,'217.156.103.4'
		,'188.214.17.38'
	);
}

class Okazii_Connector_Product
{
	public $ID;
	public $UniqueID;
	public $Title;
	public $Category;
	public $Description;
	/**
	 * @property $Price
	 * @property $DiscountPrice
	 */
	protected $Price;
	protected $DiscountPrice;
	public $Currency;
	public $Amount;
	public $Brand;
	/**
	 * @property $Photos
	 * @property $Attributes
	 * @property $Stocks
	 */
	protected $Photos = array();
	protected $Attributes = array();
	protected $Stocks = array();

	public function AddPhoto(Okazii_Connector_Product_Photo $Photo) {
		$this->Photos[] = $Photo;
	}

	public function AddAttribute(Okazii_Connector_Product_Attribute $Attribute) {
		$this->Attributes[] = $Attribute;
	}
	
	public function AddStock(Okazii_Connector_Product_Stock $Stock)
	{
		$this->Stocks[] = $Stock;
	}
	
	public function __set($Property, $Value)
	{
		$SetterName = "Set{$Property}";
		if(method_exists($this, $SetterName)) {
			$this->$SetterName($Value);
			return true;
		} else return false;
	}
	
	public function __get($Property)
	{
		return $this->$Property;
	}
	
	protected function SetPrice($Price)
	{
		$this->Price = number_format((float)$Price, $decimals = 2, $decimalSep = '.', $thousandSep = '');
	}
	
	protected function SetDiscountPrice($Price)
	{
		$this->DiscountPrice = number_format((float)$Price, $decimals = 2, $decimalSep = '.', $thousandSep = '');
	}
	
}

class Okazii_Connector_Product_Photo
{
	public $URL;

	public function __construct($URL)
	{
		$this->URL = $URL;
	}
}

class Okazii_Connector_Product_Attribute
{
	public $Name;
	public $Value;
	
	public function __construct($AttributeName, $AttributeValue)
	{
		$this->Name = $AttributeName;
		$this->Value = $AttributeValue;
	}
}

class Okazii_Connector_Product_Stock
{
	public $Attributes = array();
	public $Amount;
	
	public function __construct($Amount)
	{
		$this->Amount = $Amount;
	}
	
	public function AddAttribute(Okazii_Connector_Product_Attribute $Attribute)
	{
		$this->Attributes[] = $Attribute;
	}
}


class Okazii_Connector_Output
{
	/**
	 * @var DOMDocument
	 */
	protected $XMLDocument;

	public function __construct()
	{
		$this->XMLDocument = new DOMDocument('1.0', 'UTF-8');
		#$this->XMLDocument->appendChild($this->XMLProducts = $this->XMLDocument->createElement('AUCTIONS'));
	}

	/*
		Functie folosita pentru a crea noduri care contin caractere speciale gen &
		pentru a nu primi eroarea : unterminated entity reference
	*/
	public function CreateElement($name, $value = null){
		$Element = $this->XMLDocument->createElement($name);
		$Element = $this->XMLDocument->importNode($Element);
		if( $value!==null ){
			$Element->appendChild(new DOMText($value));
		}
		return $Element;
	}
	public function AddItem(Okazii_Connector_Product $Product)
	{
		$XMLProduct = $this->CreateElement('AUCTION');

		if(strlen($Product->Description) < 3) {
			$Product->Description = $Product->Title;
		}

		if(!$Product->Category) {
			$Product->Category = '-';
		}

		$XMLProduct->appendChild($this->CreateElement('UNIQUEID', $Product->UniqueID));
		$XMLProduct->appendChild($this->CreateElement('TITLE', $Product->Title));
		$XMLProduct->appendChild($this->CreateElement('CATEGORY', $Product->Category));
		$XMLProduct->appendChild($this->CreateElement('DESCRIPTION'))->appendChild($this->XMLDocument->createCDATASection($Product->Description));
		$XMLProduct->appendChild($this->CreateElement('PRICE', $Product->Price));

		if((int)$Product->DiscountPrice) {
			$XMLProduct->appendChild($this->CreateElement('DISCOUNT_PRICE', $Product->DiscountPrice));
		}

		$XMLProduct->appendChild($this->CreateElement('CURRENCY', $Product->Currency));
		$XMLProduct->appendChild($this->CreateElement('AMOUNT', $Product->Amount));

		if($Product->Brand) {
			$XMLProduct->appendChild($this->CreateElement('BRAND', $Product->Brand));
		}


		if($Product->Photos) {
			$XMLProduct->appendChild($XMLProductPhotos = $this->CreateElement('PHOTOS'));
			foreach ($Product->Photos as $Photo) {
				$XMLProductPhotos->appendChild($this->CreateElement('URL', $Photo->URL));
			}
		}

		if($Product->Attributes) {
			$XMLProduct->appendChild($XMLProductAttributes = $this->CreateElement('ATTRIBUTES'));
			foreach ($Product->Attributes as $Attribute) {
				$XMLProductAttributes->appendChild($this->CreateElement(strtoupper($this->Slugify($Attribute->Name)), $Attribute->Value));
			}
		}

		if($Product->Stocks) {
			$XMLProduct->appendChild($XMLProductStocks = $this->CreateElement('STOCKS'));
			foreach ($Product->Stocks as $Stock) {
				$XMLProductStocks->appendChild($XMLProductStock = $this->CreateElement('STOCK'));
				$XMLProductStock->appendChild($this->CreateElement('AMOUNT', $Stock->Amount));
				foreach ($Stock->Attributes as $Attribute) {
					$XMLProductStock->appendChild($this->CreateElement(strtoupper($Attribute->Name), $Attribute->Value));
				}
			}
		}


		#$this->XMLProducts->appendChild($XMLProduct);

		echo $this->XMLDocument->saveXML($XMLProduct);
	}

	public function AddCategory()
	{

	}

	public function StartTag($Tag)
	{
		header('Content-type: text/xml');
		echo $this->XMLDocument->saveXML();
		echo '<OKAZII>';
		#echo '<OKAZII><' . strtoupper($Tag) . '>';
	}

	public function EndTag($Tag)
	{
		echo '</OKAZII>';
		#echo '</' . strtoupper($Tag) . '></OKAZII>';
		exit();
	}

	public function __destruct()
	{
		#echo $this->XMLDocument->saveXML();
	}

	protected function Slugify($String)
	{
		//Lower case everything
		$String = strtolower($String);
		//Make alphanumeric (removes all other characters)
		$String = preg_replace("/[^a-z0-9_\s-]/", "", $String);
		//Clean up multiple dashes or whitespaces
		$String = preg_replace("/[\s-]+/", " ", $String);
		//Convert whitespaces and underscore to dash
		$String = preg_replace("/[\s_]/", "-", $String);

		return $String;
	}
}


abstract class Okazii_Connector_Adapter
{
	protected $PlatformInitialized = false;
	protected $PlatformDirectory = '';
	protected $DEBUG = false;

	abstract protected function FetchProducts();

	abstract protected function FetchCategories();

	abstract protected function IsPlatformInstalled();

	abstract protected function LoadPlatform();

	abstract public function GetPlatformInfo();

	final public function __construct($DEBUG = false)
	{
		if ($DEBUG === true) {
			$this->DEBUG = true;
		} else {
			$this->DEBUG = false;
		}

		if(!isset($this->AdapterName))
			throw new Okazii_Connector_Exception('Invalid adapter - property AdapterName not defined', Okazii_Connector_Exception::ADAPTER_PROPERTY_NOT_DEFINED);

		if(!isset($this->AdapterVersion))
			throw new Okazii_Connector_Exception('Invalid adapter - property AdapterVersion not defined', Okazii_Connector_Exception::ADAPTER_PROPERTY_NOT_DEFINED);

		if (!$this->InitPlatform(realpath('.')) && !$this->InitPlatform(realpath('..'))) {
			return false;
		}
	}

	final public function InitPlatform($PlatformDirectory = '')
	{
		if($this->PlatformInitialized === true) {
			return true;
		}

		$this->PlatformDirectory = $PlatformDirectory;

		if($this->IsPlatformInstalled()) {
			$this->PlatformInitialized = $this->LoadPlatform();
		} else {
			$this->PlatformDirectory = '';
		}

		return $this->PlatformInitialized;
	}

	final public function ListProducts()
	{
		if(!$this->DEBUG) {
			$this->OutputXML = new Okazii_Connector_Output();
			$this->OutputXML->StartTag('AUCTIONS');
			$this->FetchProducts();
			$this->OutputXML->EndTag('AUCTIONS');
		} else {
			$this->FetchProducts();
			return print_r($this->Products, true);
		}
	}

	final public function ListCategories()
	{
		if(!$this->DEBUG) {
			$this->OutputXML = new Okazii_Connector_Output();
			$this->OutputXML->StartTag('CATEGORIES');
			$this->FetchCategories();
			$this->OutputXML->EndTag('CATEGORIES');
		} else {
			$this->FetchCategories();
			return print_r($this->Products, true);
		}
	}

	final protected function AddProduct(Okazii_Connector_Product $Product)
	{
		if (!$this->DEBUG)
			$this->OutputXML->AddItem($Product);
		else
			$this->Products[] = $Product;
	}

	final protected function AddCategory(Okazii_Connector_Product $Category)
	{
		if (!$this->DEBUG)
			$this->OutputXML->AddItem($Category);
		else
			$this->Categories[] = $Category;
	}

	final public function GetAdapterInfo()
	{
		return array(
			'Name'		=>	$this->AdapterName,
			'Version'	=>	$this->AdapterVersion
		);
	}

	final public static function Run()
	{
		if(isset($_GET['debug'])) {
			$DEBUG = true;
		} else {
			$DEBUG = false;
		}

		//Decomenteaza linia urmatoare pentru a fi debugging tot timpul activat
		//$DEBUG = true;

		$Adapter = new static($DEBUG);

		if(!$Adapter->InitPlatform()) {
			echo "Platform " . $Adapter->AdapterName . "not installed.";
			exit();
		}

		if(isset($_SERVER['PATH_INFO'])) {
			switch ($_SERVER['PATH_INFO']) {
				case '/PlatformInfo':
					echo "<pre><strong>";
					print_r($Adapter->GetPlatformInfo());
					exit();
					break;
				case '/AdapterInfo':
					echo "<pre><strong>";
					print_r($Adapter->GetAdapterInfo());
					exit();
					break;
				case '/PHPInfo':
					echo "<pre><strong>";
					print_r(phpversion());
					exit();
					break;
				case '/DebugInfo':
					if(method_exists($Adapter, 'GetDebugInfo')){
						return $Adapter->GetDebugInfo();
					}
					else {
						return "Error. NonExistent";
					}
					exit();
					break;
			}
		}
		//se face echo pentru a se afisa si debugging info, daca este setat
		//DEBUG
		echo $Adapter->ListProducts();
	}
}


/**
 * clasa OkaziiLoaderOpencart contine functionalitatile pentru conectarea la opencart
 * si exportarea datelor. In plus, exista functii pentru testare, afisare nume script, nume platforma,
 * versiune script, gasire poze in functie de ID, afisare categorii
 * OBS: incarcarea claselor depinde de OKZ_BASE_DIR setat anterior includerii acestui fisier in import_okazii.php
 */
class Okazii_Connector_Adapter_OpenCart extends Okazii_Connector_Adapter
{
	protected $AdapterName = 'OpenCart';
	protected $AdapterVersion = '10.2';

	private $Tables = array();
	private $DB;
	private $Config;

	protected function IsPlatformInstalled()
	{
		if(file_exists($this->PlatformDirectory . "/config.php") && file_exists($this->PlatformDirectory . "/system/startup.php")){
			return true;
		}
		return false;
	}

	protected function LoadPlatform()
	{

		if (is_file($this->PlatformDirectory.'/config.php')) {
			require_once($this->PlatformDirectory.'/config.php');
		}
		else return false;

		if (is_file(DIR_SYSTEM. 'startup.php'))		require_once(DIR_SYSTEM . 'startup.php');
		else return false;
		// Database
		$this->DB = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);


		// Config
		$this->Config = new Config();


		$this->Tables['attribute'] = DB_PREFIX.'attribute';
		$this->Tables['attribute_description'] = DB_PREFIX.'attribute_description';
		$this->Tables['attribute_group'] = DB_PREFIX.'attribute_group';
		$this->Tables['attribute_group_description'] = DB_PREFIX.'attribute_group_description';

		$this->Tables['currency'] = DB_PREFIX.'currency';
		$this->Tables['category'] = DB_PREFIX.'category';
		$this->Tables['category_description'] = DB_PREFIX.'category_description';
		$this->Tables['language'] = DB_PREFIX.'language';
		$this->Tables['length_class_description'] = DB_PREFIX.'length_class_description';
		$this->Tables['manufacturer'] = DB_PREFIX.'manufacturer';
		$this->Tables['option_value_description'] = DB_PREFIX.'option_value_description';
		$this->Tables['product_related'] = DB_PREFIX.'product_related';
		$this->Tables['product_image'] = DB_PREFIX.'product_image';
		$this->Tables['product_special'] = DB_PREFIX.'product_special';
		$this->Tables['product_to_category'] =  DB_PREFIX.'product_to_category';
		$this->Tables['product_option_value'] = DB_PREFIX.'product_option_value';
		$this->Tables['product_tag'] = DB_PREFIX.'product_tag';
		$this->Tables['product_attribute'] = DB_PREFIX.'product_attribute';
		$this->Tables['product'] = DB_PREFIX.'product';
		$this->Tables['product_description'] = DB_PREFIX.'product_description';
		$this->Tables['setting'] = DB_PREFIX.'setting';
		$this->Tables['tax_rate'] = DB_PREFIX.'tax_rate';
		$this->Tables['tax_rule'] = DB_PREFIX.'tax_rule';
		$this->Tables['tax_class'] = DB_PREFIX.'tax_class';
		$this->Tables['url_alias'] = DB_PREFIX.'url_alias';
		$this->Tables['weight_class_description'] = DB_PREFIX.'weight_class_description';

		return true;
	}

	public function GetPlatformInfo()
	{
		//in Opencart versiunea este hardcodata in index.php si in admin/index.php
		$File = fopen($this->PlatformDirectory . '/index.php', 'r');
		while (($Line = fgets($File, 4096)) !== false) {
			if (strstr($Line, 'VERSION')){
				$TmpVersionArray = explode("'", $Line);
				$Version = $TmpVersionArray[3];
				break;
			}
		}

		if(!isset($Version)) $Version = 'UNKNOWN';

		$ObjPlatformInfo = new Okazii_Connector_PlatformInfo();
		$ObjPlatformInfo->Name = 'OpenCart';
		$ObjPlatformInfo->Version = $Version;

		return $ObjPlatformInfo;
	}

	protected function FetchProducts($Parameters = null){
		$DefaultLanguageIdArray = ($this->GetDefaultLanguageID());
		$Languages = $this->GetLanguages();
		$LanguagesLite = $this->GetLanguagesLite($Languages);
		if (! isset($Parameters['lang']) || !in_array($Parameters['lang'], $LanguagesLite))	$DefaultLanguageName = reset($DefaultLanguageIdArray);
		else $DefaultLanguageName = $Parameters['lang'];
		$DefaultLanguageID = key($DefaultLanguageIdArray);
		$CategoriesFull = $this->GetCategories($Languages);
		$Categories = $this->GetCategoriesLite($CategoriesFull, $DefaultLanguageName);
		$Products = $this->GetProducts($Languages, $DefaultLanguageID);
		$ProductsAttributes = $this->GetProductsWithAttributes($Languages);

		$DefaultCurrency = $this->GetDefaultCurrency();

		foreach($Products as $OpenCartProduct){

			$Product = new Okazii_Connector_Product();
			$Product->ID = $OpenCartProduct['product_id'];
			$ProductID = $OpenCartProduct['product_id'];

			$Product->UniqueID = $OpenCartProduct['sku'] ? $OpenCartProduct['sku'] : $ProductID;
			$Product->Title = $OpenCartProduct['name'][$DefaultLanguageName];

			$CategoryProductArray = explode(",", $OpenCartProduct['categories']);
			if (count ($CategoryProductArray) > 1) {
				$FirstCategoryIDForProduct = $CategoryProductArray[0];
			}
			else {
				$FirstCategoryIDForProduct = $OpenCartProduct['categories'];
			}


			$Product->Category = $Categories[$FirstCategoryIDForProduct];

			$Product->Description = html_entity_decode($OpenCartProduct['description'][$DefaultLanguageName]);

			$Price = $OpenCartProduct['price'];

			$TaxForProduct =  (float) $this->GetTaxRateByTaxClass($OpenCartProduct['tax_class_id']);

			$Product->Price = $Price * (1 + $TaxForProduct);

			$Product->DiscountPrice = $this->GetSpecialPrice($ProductID);

			$Product->Currency = $DefaultCurrency;

			$Product->Amount = $OpenCartProduct['quantity'];

			$Product->Brand = $OpenCartProduct['manufacturer'] ? $OpenCartProduct['manufacturer'] : '';

			$Photos = $this->GetPhotosForProduct($ProductID, $AdditionalMainImage = $OpenCartProduct['image_name']);
			if($Photos)
				foreach ($Photos as $Photo) {
					$Product->AddPhoto(new Okazii_Connector_Product_Photo($Photo));
				}

			$Attributes = $this->getProductAttributeForProductFromAttributes($ProductsAttributes, $ProductID, $DefaultLanguageName);
			if($Attributes) {
				foreach ($Attributes as $AttributeName => $AttributeValue) {
					$Product->AddAttribute(new Okazii_Connector_Product_Attribute($AttributeName, $AttributeValue));
				}
			}

			$Product->Stocks = array();

			$this->AddProduct($Product);
		}
	}

	protected function FetchCategories()
	{
		echo 'categories ' . __CLASS__;
	}

	/* public GetLanguages() {{{ */
	/*Opencart are mai multe limbi care functioneaza ca si vitrine de magazin
	 * fiecare cu propria categorie*
	 * GetLanguages
	 *
	 * @access public
	 * @return array languages
	 */
	private function GetLanguages($LanguageID = null){

		$Query = $this->DB->query( "SELECT * FROM `".$this->Tables["language"]."` WHERE `status`=1 ORDER BY `code`"  );
		$Languages = $Query->rows;

		$Found = false;
		foreach($Languages as $Language){
			if(is_numeric($LanguageID)){
				if(@$Language['language_id'] == $LanguageID){
					$Found = true;
					$LanguagesReturnVariable[$LanguageID] = $Language;

				}
			}
			$LanguagesWithIndexes[$Language['language_id']] = $Language;
		}

		if ($Found == false ){
			$LanguagesReturnVariable = $LanguagesWithIndexes;
		}

		if (! is_numeric($LanguageID) && ($LanguageID !== null))
			$LanguagesReturnVariable = null;

		return $LanguagesReturnVariable;
	}
	/* }}} */

	private function GetLanguagesLite($Languages){
		$LanguagesLite = null;
		foreach($Languages as $Language){
			$LanguagesLite[$Language['language_id']] = $Language['code'];
		}

		return $LanguagesLite;
	}
	/* }}} */

	private function GetDefaultLanguageID($ReturnID = false) {

		$SqlConfig = "SELECT value FROM `".$this->Tables["setting"]."` WHERE `code` LIKE  'config' AND  `key` LIKE 'config_language' LIMIT 1";
		$LanguageValueResults = $this->DB->query($SqlConfig);
		if ($LanguageValueResults->row) {
			$LanguageCode = $LanguageValueResults->row['value'];

		}

		$Sql = "SELECT language_id FROM `".$this->Tables["language"]."` WHERE code = '$LanguageCode'";
		$LanguageIDResult = $this->DB->query( $Sql );
		$LanguageID = 1;
		if ($LanguageIDResult->rows) {
			foreach ($LanguageIDResult->rows as $Row) {
				$LanguageID = $Row['language_id'];
				break;
			}
		}
		if ($ReturnID == true) return $LanguageID;
		return array($LanguageID => $LanguageCode);
	}

	private function GetCategoryDescriptions( $Languages) {
		// luam descrierile din category_description
		$CategoryDescriptions = array();
		foreach ($Languages as $Language) {
			$LanguageID = $Language['language_id'];
			$LanguageCode = $Language['code'];
			$Sql  = "SELECT c.category_id, cd.* ";
			$Sql .= "FROM `".$this->Tables["category"]."` c ";
			$Sql .= "LEFT JOIN `".$this->Tables["category_description"]."` cd ON cd.category_id=c.category_id AND cd.language_id='".(int)$LanguageID."' ";

			$Sql .= "GROUP BY c.`category_id` ";
			$Sql .= "ORDER BY c.`category_id` ASC ";

			$Query = $this->DB->query( $Sql );
			$CategoryDescriptions[$LanguageCode] = $Query->rows;
		}
		return $CategoryDescriptions;
	}

	private function GetCategories($Languages){
		$Sql  = "SELECT c.*, ua.keyword FROM `".$this->Tables["category"]."` c ";
		$Sql .= "LEFT JOIN `".$this->Tables["url_alias"]."` ua ON ua.query=CONCAT('category_id=',c.category_id) ";

		$Sql .= "GROUP BY c.`category_id` ";
		$Sql .= "ORDER BY c.`category_id` ASC ";

		$Results = $this->DB->query( $Sql );
		$CategoryDescriptions = $this->GetCategoryDescriptions( $Languages );
		foreach ($Languages as $Language) {
			$LanguageCode = $Language['code'];
			foreach ($Results->rows as $Key => $Row) {
				if (isset($CategoryDescriptions[$LanguageCode][$Key])) {
					$Results->rows[$Key]['name'][$LanguageCode] = $CategoryDescriptions[$LanguageCode][$Key]['name'];
					$Results->rows[$Key]['description'][$LanguageCode] = $CategoryDescriptions[$LanguageCode][$Key]['description'];
					$Results->rows[$Key]['meta_title'][$LanguageCode] = $CategoryDescriptions[$LanguageCode][$Key]['meta_title'];
					$Results->rows[$Key]['meta_description'][$LanguageCode] = $CategoryDescriptions[$LanguageCode][$Key]['meta_description'];
					$Results->rows[$Key]['meta_keyword'][$LanguageCode] = $CategoryDescriptions[$LanguageCode][$Key]['meta_keyword'];
				} else {
					$Results->rows[$Key]['name'][$LanguageCode] = '';
					$Results->rows[$Key]['description'][$LanguageCode] = '';
					$Results->rows[$Key]['meta_title'][$LanguageCode] = '';
					$Results->rows[$Key]['meta_description'][$LanguageCode] = '';
					$Results->rows[$Key]['meta_keyword'][$LanguageCode] = '';
				}
			}
		}
		return $Results->rows;

	}

	private function GetCategoriesLite($Categories  = null, $DefaultLanguageName = 'en'){
		if ($Categories == null) return null;
		$CategoriesLite = null;
		foreach($Categories as $Category){
			$CategoriesLite[$Category['category_id']] = $Category['name'][$DefaultLanguageName];
		}
		return $CategoriesLite;
	}

	private function GetProductDescriptions( $Languages , $ProductIDsSearchArray = null) {
		// versiuni mai vechi folosesc tabelul 'product_tag'
		$ProductTagTableExists = false;
		$Query = $this->DB->query( "SHOW TABLES LIKE '".$this->Tables["product_tag"]."'" );
		$ProductTagTableExists = ($Query->num_rows > 0);

		// query the product_description table for each language
		$ProductDescriptions = array();
		foreach ($Languages as $Language) {
			$LanguageID = $Language['language_id'];
			$LanguageCode = $Language['code'];
			$Sql  = "SELECT p.product_id, ".(($ProductTagTableExists) ? "GROUP_CONCAT(pt.tag SEPARATOR \",\") AS tag, " : "")."pd.* ";
			$Sql .= "FROM `".$this->Tables["product"]."` p ";
			$Sql .= "LEFT JOIN `".$this->Tables["product_description"]."` pd ON pd.product_id=p.product_id AND pd.language_id='".(int)$LanguageID."' ";
			if ($ProductTagTableExists) {
				$Sql .= "LEFT JOIN `".$this->Tables["product_tag"]."` pt ON pt.product_id=p.product_id AND pt.language_id='".(int)$LanguageID."' ";
			}

			$SearchSql = $this->GetSearchSqlForProductIdsArray("p.product_id", $ProductIDsSearchArray);
			$Sql .= $SearchSql;

			$Sql .= "GROUP BY p.product_id ";
			$Sql .= "ORDER BY p.product_id ";

			$Query = $this->DB->query( $Sql );
			$ProductDescriptions[$LanguageCode] = $Query->rows;
		}
		return $ProductDescriptions;
	}

	private function GetProducts( $Languages, $DefaultLanguageID, $ProductIDsSearchArray = null) {
		$Sql  = "SELECT ";
		$Sql .= "  p.product_id,";
		$Sql .= "  GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categories,";
		$Sql .= "  p.sku,";
		$Sql .= "  p.upc,";
		$Sql .= "  p.location,";
		$Sql .= "  p.quantity,";
		$Sql .= "  p.model,";
		$Sql .= "  m.name AS manufacturer,";
		$Sql .= "  p.image AS image_name,";
		$Sql .= "  p.shipping,";
		$Sql .= "  p.price,";
		$Sql .= "  p.points,";
		$Sql .= "  p.date_added,";
		$Sql .= "  p.date_modified,";
		$Sql .= "  p.date_available,";
		$Sql .= "  p.weight,";
		$Sql .= "  wc.unit AS weight_unit,";
		$Sql .= "  p.length,";
		$Sql .= "  p.width,";
		$Sql .= "  p.height,";
		$Sql .= "  p.status,";
		$Sql .= "  p.tax_class_id,";
		$Sql .= "  p.sort_order,";
		$Sql .= "  ua.keyword,";
		$Sql .= "  p.stock_status_id, ";
		$Sql .= "  mc.unit AS length_unit, ";
		$Sql .= "  p.subtract, ";
		$Sql .= "  p.minimum, ";
		$Sql .= "  GROUP_CONCAT( DISTINCT CAST(pr.related_id AS CHAR(11)) SEPARATOR \",\" ) AS related ";
		$Sql .= "FROM `".$this->Tables["product"]."` p ";
		$Sql .= "LEFT JOIN `".$this->Tables["product_to_category"]."` pc ON p.product_id=pc.product_id ";
		$Sql .= "LEFT JOIN `".$this->Tables["url_alias"]."` ua ON ua.query=CONCAT('product_id=',p.product_id) ";
		$Sql .= "LEFT JOIN `".$this->Tables["manufacturer"]."` m ON m.manufacturer_id = p.manufacturer_id ";
		$Sql .= "LEFT JOIN `".$this->Tables["weight_class_description"]."` wc ON wc.weight_class_id = p.weight_class_id ";
		$Sql .= "  AND wc.language_id= " .(int) $DefaultLanguageID. " ";
		$Sql .= "LEFT JOIN `".$this->Tables["length_class_description"]."` mc ON mc.length_class_id=p.length_class_id ";
		$Sql .= "  AND mc.language_id= " . (int) $DefaultLanguageID . " ";
		$Sql .= "LEFT JOIN `".$this->Tables["product_related"]."` pr ON pr.product_id=p.product_id ";

		//se limiteaza cautarile dupa product ID
		$SearchSql = $this->GetSearchSqlForProductIdsArray("p.product_id", $ProductIDsSearchArray);

		$Sql .= $SearchSql;
		$Sql .= "GROUP BY p.product_id ";
		$Sql .= "ORDER BY p.product_id ";
		$Results = $this->DB->query( $Sql );
		$ProductDescriptions = $this->GetProductDescriptions( $Languages);
		foreach ($Languages as $Language) {
			$LanguageCode = $Language['code'];
			if (isset($Results->rows) && (count($Results->rows) > 0 ) ){
				foreach ($Results->rows as $Key => $Row) {
					if (isset($ProductDescriptions[$LanguageCode][$Key])) {
						$Results->rows[$Key]['name'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['name'];
						$Results->rows[$Key]['description'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['description'];
						$Results->rows[$Key]['meta_title'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['meta_title'];
						$Results->rows[$Key]['meta_description'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['meta_description'];
						$Results->rows[$Key]['meta_keyword'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['meta_keyword'];
						$Results->rows[$Key]['tag'][$LanguageCode] = $ProductDescriptions[$LanguageCode][$Key]['tag'];
					} else {
						$Results->rows[$Key]['name'][$LanguageCode] = '';
						$Results->rows[$Key]['description'][$LanguageCode] = '';
						$Results->rows[$Key]['meta_title'][$LanguageCode] = '';
						$Results->rows[$Key]['meta_description'][$LanguageCode] = '';
						$Results->rows[$Key]['meta_keyword'][$LanguageCode] = '';
						$Results->rows[$Key]['tag'][$LanguageCode] = '';
					}
				}
			}
		}
		if ($Results->rows != null ) return $Results->rows;

		return null;
	}

	private function GetSearchSqlForProductIdsArray($TableColumn, $ProductIDsSearchArray= null){
		$SearchSql = "";
		//$TableColumn = p.product_id, pa.product_id etc
		if ($TableColumn == null) return;

		if ($ProductIDsSearchArray != null){
			if(is_numeric($ProductIDsSearchArray)){
				$SearchSql = " WHERE $TableColumn IN ( $ProductIDsSearchArray  ) ";
			}
			else if (is_array($ProductIDsSearchArray)){
				$filtered_array = array_filter($ProductIDsSearchArray,'is_numeric');
				$SearchSql = " WHERE $TableColumn IN (" . implode(",", $filtered_array).") ";
			}
		}
		return $SearchSql;

	}

	private function GetProductsWithAttributes( $Languages, $ProductIDsSearchArray = null) {
		$Sql  = "SELECT pa.product_id, ag.attribute_group_id, pa.attribute_id, pa.language_id, pa.text, ad.name ";
		$Sql .= "FROM `".$this->Tables["product_attribute"]."` pa ";
		$Sql .= "INNER JOIN `".$this->Tables["attribute"]."` a ON a.attribute_id=pa.attribute_id ";
		$Sql .= "INNER JOIN `".$this->Tables["attribute_group"]."` ag ON ag.attribute_group_id=a.attribute_group_id ";
		$Sql .= "INNER JOIN `".$this->Tables["attribute_description"]."` ad ON pa.attribute_id=ad.attribute_id AND pa.language_id = ad.language_id ";

		$SearchSql = $this->GetSearchSqlForProductIdsArray("pa.product_id",$ProductIDsSearchArray);

		$Sql .= "ORDER BY pa.product_id ASC, ag.attribute_group_id ASC, pa.attribute_id ASC";
		$Query = $this->DB->query( $Sql );
		$Texts = array();

		$Attributes = null;
		$LanguagesLite = $this->GetLanguagesLite($Languages);
		if (isset($Query->rows) && (count($Query->rows) > 0 ) ){
			foreach ($Query->rows as $Row) {
				$ProductID = $Row['product_id'];
				$AttributeID = $Row['attribute_id'];
				$LanguageID = $Row['language_id'];


				$Item["attribute_id"] = $AttributeID;
				$LanguageCode  = $LanguagesLite[$LanguageID];
				$Item["text"][$LanguageCode] = $Row['text'];
				$Item["name"][$LanguageCode] = $Row['name'];
				$Attributes[$ProductID][$AttributeID] = $Item;
			}
		}
		return $Attributes;
	}

	private function getProductAttributeForProductFromAttributes($Attributes = null, $ProductIDSearch = null, $DefaultLanguageCode = "en"){
		if ( !is_array ($Attributes) || ($Attributes == null) || ($ProductIDSearch == null ) ) return null;

		if (! isset($Attributes[$ProductIDSearch] ) || ! is_array($Attributes[$ProductIDSearch])) return null;
		$AttributesSearch = $Attributes[$ProductIDSearch];
		$AttributesParsed = null;
		foreach ($AttributesSearch as $AttributeID => $AttributeArray) {
			$AttributesParsed[$AttributeArray['name'][$DefaultLanguageCode]] = $AttributeArray['text'][$DefaultLanguageCode];
		}
		return $AttributesParsed;
	}

	private function GetPhotosForProduct($ProductID = null, $AdditionalMainImage = null) {
		if (!is_numeric($ProductID) ) return '';
		$Sql  = "SELECT image FROM `".$this->Tables["product_image"]."` ";
		$Sql .= "WHERE product_id = ".$this->DB->escape($ProductID);

		$Query = $this->DB->query( $Sql );

		$Images = null;
		//default is image/
		$ImagesDirectory = basename(DIR_IMAGE);

		if ($AdditionalMainImage != null) $Images[] =  HTTP_SERVER . $ImagesDirectory."/". $AdditionalMainImage;
		if (is_array($Query->rows) && ($Query->rows != array() ) ){

			foreach ($Query->rows as $Row) {
				$Images[] = HTTP_SERVER . $ImagesDirectory."/". $Row['image'];
			}
		}
		return $Images;

	}

	private function GetTaxRateByTaxClass($TaxClassId = null){
		if (!is_numeric($TaxClassId)) return null;
		$Sql  = "SELECT rate FROM `".$this->Tables["tax_class"]."` tc ";
		$Sql .= "INNER JOIN `".$this->Tables["tax_rule"]."` trl ON trl.tax_class_id = tc.tax_class_id ";
		$Sql .= "INNER JOIN `".$this->Tables["tax_rate"]."` trt ON trt.tax_rate_id = trl.tax_rate_id ";
		$Sql .= "WHERE tc.tax_class_id = ". $this->DB->escape($TaxClassId). " ";
		$Sql .= " AND priority = 1";

		$Result = $this->DB->query( $Sql );
		$Rate = 0;
		if (isset($Result->rows) && (count($Result->rows) > 0 ) ){
			foreach ($Result->rows as $Row) {
				$Rate = $Row['rate'];
				break;
			}
		}
		if ($Rate > 1) $Rate = (float) $Rate/100;

		return $Rate;

	}

	private function GetSpecialPrice($ProductID = null){
		if (! is_numeric($ProductID) ) return null;
		$Sql  = "SELECT price FROM `".$this->Tables["product_special"]."` psp ";
		$Sql .= "WHERE psp.product_id = ". $this->DB->escape($ProductID). " ";
		$Sql .= " AND priority = 1";

		$Result = $this->DB->query( $Sql );
		$Price = null;
		if (isset($Result->rows) && (count($Result->rows) > 0 ) ){
			foreach ($Result->rows as $Row) {
				$Price = $Row['price'];
				break;
			}
		}
		return $Price;

	}

	private function GetDefaultCurrency(){
		//default currency value has 1.000000
		$Sql  = "SELECT code FROM `".$this->Tables["currency"]."`  ";
		$Sql .= "WHERE value LIKE '1.00%' ";

		$CurrencyResult = $this->DB->query( $Sql );
		$CurrencyCode = null;
		if (isset($CurrencyResult->rows) && (count($CurrencyResult->rows) > 0 ) ){
			foreach ($CurrencyResult->rows as $Row) {
				$CurrencyCode = $Row['code'];
				break;
			}
		}
		return $CurrencyCode;
	}

	/*
	 * Functia returneaza toate categoriile de produse din Opencart
	 * @param string $Format json|array
	 * @param boolean $EchoResults return|echo $GlobalCategories
	 * @return $GlobalCategories {
	 *      @param integer $cat_id {
	 *          @param string name
	 *          @param string slug SEO link
	 *      }
	 * }
	 * */
	public function ShowCategories($Format= 'json', $EchoResults = false){

		$DefaultLanguageIdArray = ($this->GetDefaultLanguageID());
		$DefaultLanguageName = reset($DefaultLanguageIdArray);
		$Languages = $this->GetLanguages();
		$CategoriesFull = $this->GetCategories($Languages);
		$Categories = $this->GetCategoriesLite($CategoriesFull, $DefaultLanguageName);


		if ($this->testing)
			print_r($CategoriesFull);

		$GlobalCategories = array();
		foreach($Categories as $CategoryID => $CategoryName){

			$CategoryArray['name'] = $CategoryName;
			//not used for URLs
			$CategoryArray['slug'] = $this->slugify($CategoryName);
			$GlobalCategories[$CategoryID] = $CategoryArray;

		}
		if ($EchoResults == true) {
			if($Format == 'json'){
				print_r(json_encode($GlobalCategories));
			}
			else if ($Format == 'array'){
				print_r ($GlobalCategories);
			}

		}
		else {
			if($Format == 'json'){
				return json_encode($GlobalCategories);
			}
			else if ($Format == 'array'){
				return $GlobalCategories;
			}
		}
	}


	public function GetProductIDBySku($SKU = null){
		if ($SKU == null) return;
		$Sql = "SELECT product_id FROM " . $this->Tables["product"] ." WHERE sku = '" . (string)$SKU . "' LIMIT 1";

		$Query = $this->DB->query($Sql);
		$ProductID = $Query->row['product_id'];
		return $ProductID;

	}
	/*
	 * Functia afiseaza informatii de debugging in functie de parametrii GET
	 * Se foloseste cheia UNIQUEID si se interogheaza baza de date dupa product_id
	 * UNIQUEID integer|array
	 * Necesita DEBUG sa fie ON
	 * */
	public function GetDebugInfo(){
		if(!$this->DEBUG) return;
		echo "<PRE>";
		if (isset($_GET['UNIQUEID'])){
			$UniqueID = (int)$_GET['UNIQUEID'];
		}
		else if(isset($_GET['SKU'])){
			$SKU = $_GET['SKU'];
			$UniqueID = $this->GetProductIDBySku($SKU);
		}
		else return;

		$Languages = $this->GetLanguages();

		$DefaultLanguageIdArray = ($this->GetDefaultLanguageID());
		$DefaultLanguageID = key($DefaultLanguageIdArray);
		$Products = null;
		$Products = $this->GetProducts($Languages, $DefaultLanguageID, $UniqueID);
		if ($Products == null) return;
		$ProductsAttributes = $this->GetProductsWithAttributes($Languages, $UniqueID);
		$DefaultLanguageName = reset($DefaultLanguageIdArray);
		$DefaultCurrency = $this->GetDefaultCurrency();
		$Currency = $DefaultCurrency;

		foreach($Products as $OpenCartProduct){

			$OpenCartProduct = $Products[0];
			$ProductID = $OpenCartProduct['product_id'];
			$Photos = $this->GetPhotosForProduct($ProductID, $AdditionalMainImage = $OpenCartProduct['image_name']);
			$TaxForProduct =  (float) $this->GetTaxRateByTaxClass($OpenCartProduct['tax_class_id']);
			$Price = $OpenCartProduct['price'];
			$PriceWithVAT = $Price * (1 + $TaxForProduct);
			$DiscountPrice = $this->GetSpecialPrice($ProductID);

			$Attributes = $this->getProductAttributeForProductFromAttributes($ProductsAttributes, $ProductID, $DefaultLanguageName);
			$OpenCartProduct['Other'] = array( 'photos' => $Photos,
				'tax_for_product' => $TaxForProduct, 'price' => $Price, 'price_with_vat' => $PriceWithVAT,
				'discount_price' => $DiscountPrice,  'attributes' => $Attributes);
			$Output[] = $OpenCartProduct;
		}
		$Output['products_attributes'] = $ProductsAttributes;
		$Output['currency'] = $Currency;
		print_r($Output);
		exit();

	}




}

set_time_limit(1000);
class Okazii_Connector_Adapter_Magento extends Okazii_Connector_Adapter
{
	protected $AdapterName = 'Magento';
	protected $AdapterVersion = '1.0';


    protected function GetCategoriesForProduct($Product= null){
        if (! $Product instanceof Mage_Catalog_Model_Product) return null;
        $CategoryIds = $Product->getCategoryIds();
        $ProductCategories = array();
        foreach($CategoryIds as $categoryId) {
            $category= Mage::getModel('catalog/category')->load($categoryId);
            $ProductCategories[$Product->getSku()] = $category->getName();
        }

        return $ProductCategories;
    }
    protected function GetAttributes($Product = null){

        if (! $Product instanceof Mage_Catalog_Model_Product) return null;
        $Attributes = $Product->getAttributes();
        $VisibleAttributes = array();
        foreach ($Attributes as $Attribute) {
            if ($Attribute->getIsVisibleOnFront()) {
                $Value = $Attribute->getFrontend()->getValue($Product);
                $VisibleAttributes[] = array($Attribute->getAttributeCode()=> $Value);

            }
        }

        return $VisibleAttributes;


    }

    protected function GetImages($ProductId = null){
        if ($ProductId == null) return;

        $ImagesCollection = Mage::getModel('catalog/Product')->load($ProductId)->getMediaGalleryImages();
        $ImagesArray = array();
        if( $ImagesCollection  ){
            foreach ($ImagesCollection as $Image ){
                $ImagesArray[] = $Image->getUrl();

            }
        }
        return $ImagesArray;

    }
    protected function GetAttributesValuesForConfigurableProduct($ConfigurableProduct = null){

        if (! $ConfigurableProduct instanceof Mage_Catalog_Model_Product && ! $ConfigurableProduct->isConfigurable()) return null;
        $AttributeOptions = array();

        $ProductAttributeOptions = $ConfigurableProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($ConfigurableProduct);

        foreach ($ProductAttributeOptions as $ProductAttribute) {
            foreach ($ProductAttribute['values'] as $Attribute) {
                $AttributeOptions[$ProductAttribute['attribute_code']][$Attribute['value_index']] = $Attribute['store_label'];
            }
        }
        return $AttributeOptions;

    }
    protected function GetSubproductsWithOptions($ConfigurableProduct = null, $AttributeCodes = array()){

        if (! $ConfigurableProduct instanceof Mage_Catalog_Model_Product && ! $ConfigurableProduct->isConfigurable()) return null;

        $ChildrenIds=Mage::getResourceSingleton('catalog/product_type_configurable')
                    ->getChildrenIds($ConfigurableProduct->getId());
        $Subproducts = Mage::getModel('catalog/product')->getCollection() ->addIdFilter ($ChildrenIds)->addAttributeToSelect('price');

        if(is_array($AttributeCodes)){
            foreach ($AttributeCodes as $AttributeCode){
                $Subproducts->addAttributeToSelect($AttributeCode);
            }

        }
        return $Subproducts;

    }

    protected function BuildAttributeArrayForExport($ConfigurableProduct = null){


        if (! $ConfigurableProduct instanceof Mage_Catalog_Model_Product && ! $ConfigurableProduct->isConfigurable()) return null;
        $AttributeOptions = $this->GetAttributesValuesForConfigurableProduct($ConfigurableProduct);
        $AttributeCodes = array_keys($AttributeOptions);
        $Subproducts = $this->GetSubproductsWithOptions($ConfigurableProduct, $AttributeCodes);

        $Attributes = array();

        foreach ($Subproducts as $Subproduct) {
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($Subproduct);
            $Attribute_item['cantitate'] = $stock->getQty();
            foreach ($AttributeCodes as $attr) {
                //indicii sunt numerici, nu float cum vin din colectie
                $product_attribute_value = (int)$Subproduct->{$attr} ;
                $Attribute_item['atribute'][$attr] =  $AttributeOptions[$attr][$product_attribute_value  ];
            }
            $Attribute_item['pret'] = $Subproduct->getPrice();

            $Attributes[] = $Attribute_item;
        }
        return $Attributes;

    }

	protected function FetchProducts()
	{
        
        Mage::app( );
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $Products = array();
        $MagentoPath= $this->PlatformDirectory;
        $UrlPath = "";

        // pregatim colectia, doar cu elementele necesare
        $ProductCollection= Mage::getModel('catalog/Product')->getCollection();
        $ProductCollection->addAttributeToSelect( array('id', 'name','price','status','visibility','description','sku','type_id','price','special_price'));
        
        $i = 1;
        $EnabledStatus = 1;
        $DisabledStatus = 2;
        $NotVisibleIndividually = 1;
        $VisibleCatalog = 2;
        $VisibleSearch = 3;
        $VisibleCatalogAndSearch = 4;
        $Currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        foreach ( $ProductCollection as $Product ) {

            //daca nu este enabled, sare peste
            if ($Product->getStatus()!=$EnabledStatus) {
                continue;
            }
            //daca nu este vizibil, inseamna ca este atasat altui produs
            if ($Product->getVisibility()==$NotVisibleIndividually) {
                continue;
            }
            if ( !in_array($Product->getTypeId(), array( "simple", "configurable") )) {
                continue;
            }

            $ProductId = $Product->getId();
            $ProductName = $Product->getName();

            //anumite produse pot fi stocate partial, doar ca SKU, fara nume,
            //de aceea le omitem daca nu au numele setat
            if (trim($ProductName) == "") continue;


            if ($Product->isConfigurable() ) {
                $Stocks = $this->BuildAttributeArrayForExport($Product);
            }
            else {
                $Stocks = array();
            }

            // preluare categorii pentru produs
            $ProductCategories = $this->GetCategoriesForProduct($Product);
            $VisibleAttributes = $this->GetAttributes($ProductId);
            $ImagesArray = $this->GetImages($ProductId);
            $Producator = Mage::getModel('catalog/Product')->load($ProductId)->getAttributeText("manufacturer");


            $Product = array(
                'id'			=>	$Product->getSku(),
                'titlu'			=>	$ProductName,
                'categorie'		=>	$ProductCategories,
                'descriere'		=>	$Product->getDescription(),
                'pret'			=>	$Product->getPrice(),
                'pret_redus'	=>	$Product->getSpecialPrice(),
                'moneda'		=>	$Currency,
                'cantitate'		=>	$Product->getStockItem()->getQty(),
                'producator'	=>	trim( $Producator),
                'imagini'		=>	$ImagesArray,
                'atribute'      =>  $VisibleAttributes,
            );
            $Product['stocuri'] = $Stocks;

            $Products[] = $Product;
        }

        //adaugare Products ca Okazii_Connector_Product() si la colectia finala
        $this->AddProducts($Products);

		
	}



    protected function AddProducts($Products){
        if (!is_array($Products)) return;

        foreach($Products as $Product) {
			$OkaziiConnectorProduct = new Okazii_Connector_Product();

			$OkaziiConnectorProduct->UniqueID = $Product['id'];
			$OkaziiConnectorProduct->Title = $Product['titlu'];
			$OkaziiConnectorProduct->Category = implode(' / ', $Product['categorie']);
			$OkaziiConnectorProduct->Description = $Product['descriere'];
			$OkaziiConnectorProduct->Price = $Product['pret'];
			$OkaziiConnectorProduct->DiscountPrice = $Product['pret'];
			$OkaziiConnectorProduct->Currency = $Product['moneda'];
			$OkaziiConnectorProduct->Amount = $Product['cantitate'];
			$OkaziiConnectorProduct->Brand = $Product['producator'];

            if(is_array($Product['imagini'])){
                foreach ($Product['imagini'] as $PhotoURL) {
                    $OkaziiConnectorProduct->AddPhoto(new Okazii_Connector_Product_Photo($PhotoURL));
                }
            }

            if (is_array($Product['atribute'])){
                foreach ($Product['atribute'] as $Attribute) {
                    if (is_array($Attribute)){
                        foreach ($Attribute as $key => $value) {
                            $OkaziiConnectorProduct->AddAttribute(new Okazii_Connector_Product_Attribute($AttributeName = $key, $AttributeValue = $value));
                        }
                    }
                }

            }
            if (is_array($Product['stocuri'])){
                foreach ($Product['stocuri'] as $stoc) {
                    $Stock = new Okazii_Connector_Product_Stock($stoc['cantitate']);
                    if (is_array($stoc['atribute'])){
                        foreach ($stoc['atribute'] as $AttributeName => $AttributeValue) {
                            $Stock->AddAttribute(new Okazii_Connector_Product_Attribute($AttributeName, $AttributeValue));
                        }
                    }
                    $OkaziiConnectorProduct->AddStock($Stock);
                }
            }
            $this->AddProduct($OkaziiConnectorProduct, $Product['id']);
		}    
    }

	protected function FetchCategories(){}

    protected function IsPlatformInstalled(){
        if (file_exists( rtrim($this->PlatformDirectory , "/") . "/app/Mage.php")) return true;
        else return false;
    }

	protected function LoadPlatform()
	{
        if ($this->IsPlatformInstalled()){
            require_once rtrim($this->PlatformDirectory,"/")."/app/Mage.php";
            return true;
        }
        return false;
	}

	public function GetPlatformInfo()
	{
        if ($this->LoadPlatform()){
            $mage = new Mage();
            $version = $mage->getVersion();
            $platform = "Magento " . $mage->getEdition();

            $objPlatformInfo = new Okazii_Connector_PlatformInfo();
            $objPlatformInfo->Name = $platform;
            $objPlatformInfo->Version = $version;

            return $objPlatformInfo;

        }
        return false;
	}
}


class Okazii_Connector_Adapter_Sample extends Okazii_Connector_Adapter
{
	protected $AdapterName = 'Sample';
	protected $AdapterVersion = '1.0';

	protected function FetchProducts()
	{
		$Products = array();
		$Products[] = array(
			'id'			=>	1,
			'titlu'			=>	'',
			'categorie'		=>	array('cat nivel 1', 'cat nivel 2'),
			'descriere'		=>	'',
			'pret'			=>	100,
			'pret_redus'	=>	10.99,
			'moneda'		=>	'RON',
			'cantitate'		=>	1,
			'producator'	=>	'Samsung',
			'imagini'		=>	array('http://example.com/image1.jpg', 'http://example.com/image1.jpg'),
			'atribute'		=>	array(
										array('nume-atribut-1'=>'valoare_atribut_1'),
										array('nume-atribut-2'=>'valoare_atribut_2'),
										array('nume-atribut-3'=>'valoare_atribut_3')
			),
			'stocuri'		=> array(
				#stoc 1
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
										'atribut_1'	=>	123213123,
										'atribut_2'	=>	1,
										'atribut_3'	=>	'abc'
						)
					),
				#stoc 2
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
						'atribut_1'	=>	123213123,
						'atribut_2'	=>	1,
						'atribut_3'	=>	'abc'
					)
				)
			)

		);

		$Products[] = array(
			'id'			=>	2,
			'titlu'			=>	'',
			'categorie'		=>	array('cat nivel 1', 'cat nivel 2'),
			'descriere'		=>	'',
			'pret'			=>	100,
			'pret_redus'	=>	10.99,
			'moneda'		=>	'RON',
			'cantitate'		=>	1,
			'producator'	=>	'Samsung',
			'imagini'		=>	array('http://example.com/image1.jpg', 'http://example.com/image1.jpg'),
			'atribute'		=>	array(
				array('nume-atribut-1'=>'valoare_atribut_1'),
				array('nume-atribut-2'=>'valoare_atribut_2'),
				array('nume-atribut-3'=>'valoare_atribut_3')
			),
			'stocuri'		=> array(
				#stoc 1
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
										'atribut_1'	=>	123213123,
										'atribut_2'	=>	1,
										'atribut_3'	=>	'abc'
						)
					),
				#stoc 2
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
						'atribut_1'	=>	123213123,
						'atribut_2'	=>	1,
						'atribut_3'	=>	'abc'
					)
				)
			)

		);

		$Products[] = array(
			'id'			=>	3,
			'titlu'			=>	'',
			'categorie'		=>	array('cat nivel 1', 'cat nivel 2'),
			'descriere'		=>	'',
			'pret'			=>	100,
			'pret_redus'	=>	10.99,
			'moneda'		=>	'RON',
			'cantitate'		=>	1,
			'producator'	=>	'Samsung',
			'imagini'		=>	array('http://example.com/image1.jpg', 'http://example.com/image1.jpg'),
			'atribute'		=>	array(
				array('nume-atribut-1'=>'valoare_atribut_1'),
				array('nume-atribut-2'=>'valoare_atribut_2'),
				array('nume-atribut-3'=>'valoare_atribut_3')
			),
			'stocuri'		=> array(
				#stoc 1
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
										'atribut_1'	=>	123213123,
										'atribut_2'	=>	1,
										'atribut_3'	=>	'abc'
						)
					),
				#stoc 2
				array(
					'cantitate'	=>	5,
					'atribute'	=>	array(
						'atribut_1'	=>	123213123,
						'atribut_2'	=>	1,
						'atribut_3'	=>	'abc'
					)
				)
			)

		);

		foreach($Products as $Product) {
			$OkaziiConnectorProduct = new Okazii_Connector_Product();

			$OkaziiConnectorProduct->UniqueID = $Product['id'];
			$OkaziiConnectorProduct->Title = $Product['titlu'];
			$OkaziiConnectorProduct->Category = implode(' / ', $Product['categorie']);
			$OkaziiConnectorProduct->Description = $Product['descriere'];
			$OkaziiConnectorProduct->Price = $Product['pret'];
			$OkaziiConnectorProduct->DiscountPrice = $Product['pret'];
			$OkaziiConnectorProduct->Currency = $Product['moneda'];
			$OkaziiConnectorProduct->Amount = $Product['cantitate'];
			$OkaziiConnectorProduct->Brand = $Product['producator'];

			foreach ($Product['imagini'] as $PhotoURL) {
				$OkaziiConnectorProduct->AddPhoto(new Okazii_Connector_Product_Photo($PhotoURL));
			}

			foreach ($Product['atribute'] as $attribute) {
				foreach ($attribute as $key => $value) {
					$OkaziiConnectorProduct->AddAttribute(new Okazii_Connector_Product_Attribute($AttributeName = $key, $AttributeValue = $value));
				}
			}

			foreach ($Product['stocuri'] as $stoc) {
				$Stock = new Okazii_Connector_Product_Stock($stoc['cantitate']);
				foreach ($stoc['atribute'] as $AttributeName => $AttributeValue) {
					$Stock->AddAttribute(new Okazii_Connector_Product_Attribute($AttributeName, $AttributeValue));
				}
				$OkaziiConnectorProduct->AddStock($Stock);
			}
				
			$this->AddProduct($OkaziiConnectorProduct, $Product['id']);
		}
	}
	
	protected function FetchCategories(){}
	
	protected function IsPlatformInstalled(){}
	
	protected function LoadPlatform($InstallationDirectory = '')
	{
		return false;
	}

	public function GetPlatformInfo()
	{
		$objPlatformInfo = new Okazii_Connector_PlatformInfo();
		$objPlatformInfo->Name = 'sample_platform';
		$objPlatformInfo->Version = 'sample_platoform_version';

		return $objPlatformInfo;
	}
}
// todo:stocks
// todo:brands
// todo add security token

/**
 * clasa OkaziiLoaderWoocommerce contine functionalitatile pentru conectarea la woocommerce/wordpress
 * si exportarea datelor.
 * In plus, exista functii pentru testare, afisare nume script, nume platforma,
 * versiune script, gasire poze in functie de ID, afisare categorii
 * OBS: incarcarea claselor depinde de OKZ_BASE_DIR setat anterior includerii acestui fisier in import_okazii_woocommerce.php
 */
class Okazii_Connector_Adapter_WooCommerce extends Okazii_Connector_Adapter
{
	
	protected function FetchProducts()
	{
		$wp_woo_args = array(
			"post_type" => "product",
			"post_status" => "publish",
			"posts_per_page" => "-1"
		);
		$wp_woo_posts = new WP_Query($wp_woo_args);
	
		if ($this->DEBUG)
			print_r($wp_woo_posts);
	
		$currency = get_option('woocommerce_currency');
	
		while ($wp_woo_posts->have_posts()) {
			$wp_woo_posts->the_post();
			$ProductID = get_the_ID();
				
			$OkaziiConnectorProduct = new Okazii_Connector_Product();
			
			$OkaziiConnectorProduct->ID = $ProductID;
			$OkaziiConnectorProduct->UniqueID = $this->GetUniqueID($ProductID);
			$OkaziiConnectorProduct->Title = get_the_title();
			$OkaziiConnectorProduct->Category = $this->GetCategory($ProductID);
			$OkaziiConnectorProduct->Description = $this->GetDescription();
				
			$regular_price = get_post_meta($ProductID, '_regular_price', $return_only_one = true);
			$price = get_post_meta($ProductID, '_price', $return_only_one = true);
			$sale_price_check = (float) get_post_meta($ProductID, '_sale_price', $return_only_one = true);
			if ($sale_price_check > 0 && (trim($sale_price_check) != '')) {
				// _price este setat cu pretul curent
				// asa incat daca exista o promotie in aceasta perioada, atunci se schimba _price
				$sale_price = $price * (1 + $tax_for_product);
			} else {
				$sale_price = "";
			}
				
			if (($child_posts = $this->getChildPosts($ProductID, $post_type = "product_variation", $post_status = "publish")) != null) {
				// configurable product, regular price not set, instead sets _price
				// based on minimum price from variations
				$regular_price_check = $this->getPriceTypeForVariableProduct($ProductID, $child_posts, '_regular_price');
				$price_check = $this->getPriceTypeForVariableProduct($ProductID, $child_posts, '_price');
				$sale_price_check = $this->getPriceTypeForVariableProduct($ProductID, $child_posts, '_sale_price');
				// in cazul in care pe variatiuni sunt preturi nule, nu se modifica preturile precedente
	
				if ($regular_price_check != null)
					$regular_price = $regular_price_check;
				if ($price_check != null)
					$price = $price_check;
				if ($sale_price_check != null)
					$sale_price = $sale_price_check;
			}
			$tax_for_product = (float) $this->getTaxForWoocommerceProduct($ProductID);
				
			$OkaziiConnectorProduct->Price = $regular_price * (1 + $tax_for_product); // ##
			$OkaziiConnectorProduct->DiscountPrice = $sale_price; // ##
				
			$OkaziiConnectorProduct->Currency = $currency;
			$OkaziiConnectorProduct->Amount = $this->getStockForWoocommerceProduct($ProductID);
				
			$Photos = $this->getPhotosUrlsForWoocommerceProduct($ProductID);
			if($Photos) {
				// Photos
				foreach ($Photos as $PhotoURL) {
					$OkaziiConnectorProduct->AddPhoto(new Okazii_Connector_Product_Photo($PhotoURL));
				}
			}
				
			$Attributes = $this->getAttributesForWoocommerceProduct($ProductID);
			if ($Attributes) {
				// Brand
				if(isset($Attributes['brand']) && ($Attributes['brand']['is_visible'] == 1)) {
					$OkaziiConnectorProduct->Brand = $Attributes['brand']['value'];
				} elseif(isset($Attributes['pa_brand']) && ($Attributes['pa_brand']['is_visible'] == 1)) {
					// atributul se poate adauga atat ca si categorie, prefixat cu pa_nume
					// cat si direct din fiecare produs in parte, caz in care numele este acelasi in format SLUG
	
					$OkaziiConnectorProduct->Brand =  $Attributes['pa_brand']['value'];
				} else {
					$OkaziiConnectorProduct->Brand =  null;
				}
	
				// Attributes
				foreach ($Attributes as $attr) {
					if($attr['is_visible'] == 1)
						$OkaziiConnectorProduct->AddAttribute(new Okazii_Connector_Product_Attribute($attr['name'], $attr['value']));
				}
			}
				
			$OkaziiConnectorProduct->Stocks = array(); // TODO
				
			$this->AddProduct($OkaziiConnectorProduct);
		}
	}
	
	/*
	 * Functia returneaza codul unic pentru produs, SKU daca a fost setat si ID-ul postarii daca nu
	 * @param integer $ProductID
	 * @return string $UniqueID
	 */
	protected function GetUniqueID($ProductID)
	{
		$sku = get_post_meta($ProductID, '_sku', $return_only_one = true);
		return(trim($sku) == '') ? $ProductID : $sku;
	}

	protected function GetCategory($ProductID)
	{
		$category_objects = get_the_terms($ProductID, $term_name = 'product_cat');
		if ($category_objects && ! is_wp_error($category_objects)) {
			if (count($category_objects) > 1) {
				// se va lua categoria de nivel cel mai adanc sau ultima din lista daca sunt la acelasi nivel
				$category_id = $this->getChildCategoryIdFromCategories($category_objects);
				$term_data = get_term_by('id', $category_id, $taxonomy = 'product_cat');
				$category_array['name'] = $term_data->name;
			} else {
				// se considera doar prima categorie
				$category = reset($category_objects);
				$category_array['name'] = $category->name;
			}
		}
	
		return $category_array['name'];
	}
	
	protected function GetDescription()
	{
		global $more;
		$more = 1; // se sare peste <!--more--> tag ca sa se afiseze tot continutul
		return apply_filters('the_content', get_the_content());
	}
	
	/*
	 * Functia returneaza ID-urile postarilor copil (cazul produselor cu variatii)
	 * @param int $product_id
	 * return null|array $products
	 */
	protected function getChildPosts($product_id = null, $post_type = null, $post_status = null)
	{
		$wp_woo_args = array(
			"posts_per_page" => "-1",
			"post_parent" => $product_id
		);
		if (isset($post_type) && $post_type != null) {
			$wp_woo_args['post_type'] = $post_type;
		}
		if (isset($post_status) && $post_status != null) {
			$wp_woo_args['post_status'] = $post_status;
		}
		$wp_variation_posts = new WP_Query($wp_woo_args);
		$products = null;
		while ($wp_variation_posts->have_posts()) {
			$wp_variation_posts->the_post();
			$products[] = get_the_ID();
		}
		return $products;
	}
	
	/*
	 * Functia returneaza 0.00 daca variatiile nu au acelasi pret si pretul curent daca coincid
	 * @param int $product_id
	 * @param array $child_posts - pentru a face mai putine interogari
	 * @return null|float $price
	 */
	protected function getPriceTypeForVariableProduct($product_id = null, $child_posts = null, $price_type = '_price')
	{
		if ($child_posts == null)
			$child_posts = $this->getChildPosts($product_id, $post_type = "product_variation", $post_status = "publish");
		$prices_array_keys = array(
			"_price",
			"_regular_price",
			"_sale_price"
		);
		if (! in_array($price_type, $prices_array_keys)) {
			return null;
		}
	
		if (is_array($child_posts)) {
			if (count($child_posts) == 1) {
				$variation_post_id = reset($child_posts);
				$price = get_post_meta($variation_post_id, $price_type, $return_only_one = true);
				return $price;
			}
				
			foreach ($child_posts as $variation_post_id) {
				$prices[$variation_post_id] = get_post_meta($variation_post_id, $price_type, $return_only_one = true);
			}
			$first_price = reset($prices);
			foreach ($prices as $variation_post_id => $price) {
				if ($first_price != $price)
					return null;
			}
			$price = (float) $first_price;
			return $price;
		} else
			return null;
	}
	
	/*
	 * Functia returneaza procentul de TVA in functie de produs
	 * @param integer $product_id
	 * @return float $tax_value 0.00|0.24|0.09
	 */
	protected function getTaxForWoocommerceProduct($product_id)
	{
		// returnare setari generale de taxare
		$woocommerce_tax_settings = get_option('woocommerce_calc_taxes');
	
		if ($woocommerce_tax_settings != 'yes') {
			// daca nu exista bifa in admin, se considera ca preturile au inclus TVA
			$tax_value = 0.00;
		} else {
			$woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
			// daca preturile contin deja TVA, atunci nu se mai adauga taxa in plus
			if ($woocommerce_prices_include_tax == 'yes') {
				$tax_value = 0.00;
			} else {
	
				$is_taxable_product = get_post_meta($product_id, '_tax_class', $return_only_one = true);
				if ($is_taxable_product == 'none') {
					// daca produsul este marcat special ca netaxabil, se considera ca nu are taxe in plus
					$tax_value = 0.00;
					return $tax_value;
				}
	
				$tax_type = get_post_meta($product_id, '_tax_class', $return_only_one = true);
				$tax_rates = WC_Tax::get_base_tax_rates($tax_type);
				// luam primul element din arrayul returnat
				// $tax_rates = [ $tax_rate_id => $tax_rate_array];
				$tax_rate_array = reset($tax_rates);
				$tax_rate = (float) $tax_rate_array['rate'];
				$tax_value = $tax_rate / 100;
			}
		}
		return $tax_value;
	}
	
	/*
	 * Functia returneaza stocul existent sau numarul maxim suportat
	 * de catre Okazii API - 100, daca stocul este indefinit
	 * @param integer $product_id
	 * @return null|integer $stock
	 */
	protected function getStockForWoocommerceProduct($product_id = null)
	{
		if ($product_id == null)
			return;
		$stock_is_managed = get_post_meta($product_id, '_manage_stock', $return_only_one = true);
		if ($stock_is_managed == 'yes') {
			$stock = (int) get_post_meta($product_id, '_stock', $return_only_one = true);
		} else {
			$stock = 100;
		}
		return $stock;
	}
	
	/*
	 * Functia returneaza un array cu adresele pozelor produsului
	 * @param integer $product_id
	 * @return null|array $photos_urls
	 */
	protected function getPhotosUrlsForWoocommerceProduct($product_id)
	{
		if ($product_id == null)
			return null;
	
		$product_gallery_ids = get_post_meta($product_id, '_product_image_gallery', $return_only_one = true);
		// $product_gallery_ids = 45 sau 45,46,77
		$product_images_array = explode(",", $product_gallery_ids);
		if (! is_array($product_images_array)) {
			if (! is_numeric($product_gallery_ids)) {
				$product_thumbnail_id = get_post_meta($product_id, '_thumbnail_id', $return_only_one = true);
				if (! is_numeric($product_thumbnail_id))
					return null;
	
				$product_images_array = array(
					$product_thumbnail_id
				);
			} else {
				$product_images_array = array(
					$product_gallery_ids
				);
			}
		}
		$photos_urls = array();
		foreach ($product_images_array as $photo_id) {
			$image_array = wp_get_attachment_image_src($photo_id, $size = 'full');
				
			$photos_urls[$photo_id] = $image_array[$url_key = 0];
		}
		return $photos_urls;
	}
	
	/*
	 * Functia returneaza atributele existente
	 * Este de forma unui array cu chei specifice [key] sau [pa_key] ex: [brand] => [name => "Brand", value => "Nike", position=>1, is_visible=1, is_variation=0, is_taxonomy=0]
	 * @param integer $product_id
	 * @return null|array $attributes
	 */
	protected function getAttributesForWoocommerceProduct($product_id = null)
	{
		return $attributes = get_post_meta($product_id, '_product_attributes', $return_only_one = true);
	
		foreach ($attributes as $attribute_slug => $attribute_object) {
			// transformam numele in caractere alfanumerice separate de linie ( - )
			$attributes[$attribute_slug]['xml_name'] = $this->slugify(wc_attribute_label($attribute_slug));
		}
	
		#print_r($attributes);exit;
		return $attributes;
	}
	
	protected function getChildCategoryIdFromCategories($product_categories = null)
	{
		if ($product_categories == null)
			return null;
	
		$chain = null;
		foreach ($product_categories as $category) {
			$chain[$category->term_id] = $category->parent;
		}
		foreach ($chain as $item => $parent) {
			$this->recursiveIndex = 0;
			$count = $this->getRecursiveParentCount($chain, $parent, $stop_key = 0);
			$countCategories[$item] = (int) $count;
		}
		// sortare inversa categorie de cel mai adanc nivel
		arsort($countCategories);
	
		return (key($countCategories));
	}
	
	protected function getRecursiveParentCount($array, $key, $stop_key = 0)
	{
		if ($array == null or $key == null)
			return null;
		$this->recursiveIndex ++;
		if ($array[$key] == $stop_key) {
			return $this->recursiveIndex;
		} else {
			$nextkey = $array[$key];
			unset($array[$key]);
			return $this->getRecursiveParentCount($array, $nextkey);
		}
	}
	
	
	//-----------------end functii export produse
	
	
	protected $AdapterName = 'WooCommerce';
	protected $AdapterVersion = '2.0.89';
	
	private $recursiveIndex = 0;

	public function GetPlatformInfo()
	{
		// Daca functia get_plugins() nu este valabila, o includem
		if (! function_exists('get_plugins')) {
			require_once (ABSPATH . 'wp-admin/includes/plugin.php');
		}
			
		$PluginInfo = get_plugins('/woocommerce');
		if(isset($PluginInfo['woocommerce.php'])) {
			return array(
				'Name'				=>	$PluginInfo['woocommerce.php']['Name'],
				'Version'			=>	$PluginInfo['woocommerce.php']['Version'],
				'WordPressVersion'	=>	get_bloginfo('version')
			);
		} else {
			return null;
		}
	}

	protected function LoadPlatform()
	{
		require_once $this->PlatformDirectory . '/wp-load.php';
		
		$woocommerce_attributes_file = ABSPATH . 'wp-content/plugins/woocommerce/includes/wc-attribute-functions.php';
		if (file_exists($woocommerce_attributes_file)) {
			require_once $woocommerce_attributes_file;
		}
		// include woocommerce tax calculation
		$woocommerce_tax_class_file = ABSPATH . 'wp-content/plugins/woocommerce/includes/class-wc-tax.php';
		if (file_exists($woocommerce_tax_class_file)) {
			require_once $woocommerce_tax_class_file;
		}
		
		return true;
	}
	
	protected function IsPlatformInstalled()
	{
		return (file_exists($this->PlatformDirectory .  '/wp-config.php') && file_exists($this->PlatformDirectory .  '/wp-load.php') && file_exists($this->PlatformDirectory  . '/wp-content/plugins/woocommerce/woocommerce.php'));
	}
	
	
	
	public function FetchCategories()
	{}

	/*
	 * Functia returneaza toate categoriile de produse din Woocommerce
	 * @param string $format json|array
	 * @param boolean $echoResults return|echo $global_categories
	 * @return $global_categories {
	 * @param integer $cat_id {
	 * @param string name
	 * @param string slug SEO link
	 * }
	 * }
	 */
	public function showCategories($format = 'json', $echoResults = false)
	{
		$wp_woo_args = array(
			"post_type" => "product",
			"post_status" => "publish",
			"posts_per_page" => "-1"
		);
		$wp_woo_posts = new WP_Query($wp_woo_args);
		if ($this->testing)
			print_r($wp_woo_posts);
		$global_categories = array();
		while ($wp_woo_posts->have_posts()) {
			$wp_woo_posts->the_post();
			
			$category_objects = get_the_terms(get_the_ID(), $term_name = 'product_cat');
			if ($category_objects && ! is_wp_error($category_objects)) {
				foreach ($category_objects as $cat_id => $category_object) {
					$category_array['name'] = $category_object->name;
					$category_array['slug'] = $category_object->slug;
					$global_categories[$cat_id] = $category_array;
				}
			}
		}
		if ($echoResults == true) {
			
			if ($format == 'json') {
				print_r(json_encode($global_categories));
			} else 
				if ($format == 'array') {
					print_r($global_categories);
				}
		} else {
			if ($format == 'json') {
				return json_encode($global_categories);
			} else 
				if ($format == 'array') {
					return $global_categories;
				}
		}
	}
	
	// la Wordpress exista o functie in plus pentru platforma
	public function parseParameters(array $parameters)
	{
		$status = parent::parseParameters($parameters);
		if (! $status) {
			if (@$parameters['wordpress_version'] == true) {
				echo $this->getWordpressPlatformVersion();
			} else 
				if (@$parameters['testing'] === 'true' && @$parameters['hash'] == md5('okazii_import_!@#')) {
					if (is_numeric($parameters['post_id'])) {
						$product_id = $parameters['post_id'];
						$product = get_post($product_id, ARRAY_A);
						$product['custom_fields'] = get_post_custom($product_id);
						$product['post_meta'] = get_post_meta($product_id);
						$product['child_posts_attachments'] = $this->getChildPosts($product_id, $post_type = "attachment", $post_status = "inherit");
						$product['child_posts_variations'] = $this->getChildPosts($product_id, $post_type = "product_variation", $post_status = "publish");
						$product['custom_keys'] = get_post_custom_keys($product_id);
						$post_categories = wp_get_post_categories($product_id);
						$cats = array();
						foreach ($post_categories as $c) {
							$cat = get_category($c, ARRAY_A);
							$cats[] = $cat;
						}
						$product['categories'] = $cats;
						$all_taxonomies = get_taxonomies();
						foreach ($all_taxonomies as $taxonomy) {
							
							$product_terms = get_the_terms($product_id, $taxonomy);
							if ($product_terms != null)
								$product['terms'][$taxonomy] = $product_terms;
						}
					} else 
						if (@$parameters['terms'] === 'true') {
							
							$product['taxonomies'] = get_terms(get_taxonomies());
						}
					echo "<pre>";
					print_r($product);
					echo "</pre>";
				}
		}
	}
}

