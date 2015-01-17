<?php
 class NP{
 	
 	/* API ключ */	 
	 public static $api_key='7510f188648af018c441782bae73c0f0';	 
	
	/* ѓород отправителЯ 
	*  city sender
	*/
	 public static $citySender = 'db5c88f0-391c-11dd-90d9-001a92567626'; 
	 public static $serviceType = 'WarehouseWarehouse';
	
	
	//„ата заказа и дата отправки совпадают
	const isDateTimeDeliveryToday = 1;
	const valuesDemand = 'PayerType,PaymentMethod,DateTime,
		CargoType,VolumeGeneral,Weight,
		ServiceType,SeatsAmount,Description,Cost,
		CitySender,SenderAddress,ContactSender,
		SendersPhone,CityRecipient,Recipient,
		RecipientAddress,ContactRecipient,RecipientsPhone,Sender';
	 /**
	  * Function sends query on server NP
	  * ”ункциЯ отправлЯет запрос на сервер
	  * $request - массив параметров, которые будут отправлены
	  */
	 
	 
	 
	 
	 /* „ЂЌЌ›… „‹џ ‘Ћ‡„ЂЌ€џ ЌЂЉ‹Ђ„ЌЋ‰ */
	 public $send_data = array();
	 
	 /* Љонструктор */ 
	 function __construct()
	 {
	 
	 
	 }
	  
	public function __set($name, $val)
	{
		$this->send_data[$name] = $val; 
	}
	
	public function __get($name)
	{
		return (isset($this->send_data[$name]) ? $this->send_data[$name] : FALSE);
	}
	  
	 public static function send($request){
		$request['apiKey'] = self::$api_key;
		$request = json_encode($request);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.novaposhta.ua/v2.0/json/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/json"));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode($response);
	 }

	 /**
	  * Returned the list warehouses in city, where have NP
	  * ‚озвращает список отделений города, где есть новаЯ почта
	  */
	public static function getWarehouses($city)
	{
		$request = self::createQueryAPI('Address','getWarehouses', array('CityRef' => $city));
		$data = NP::send($request);
		$warehouses = array();
		if(!self::getStatusQuery($data))
			return FALSE;//:(
		foreach($data->data as $val)
		{
			$warehouses[] = array('name' => $val->DescriptionRu, 'id' => $val->Ref);
		}
		return $warehouses;
	}
	
	//Return status query
	public static function getStatusQuery($arr)
	{
		return $arr->success;
	}
	
	/**
	 * ‡адаЮт метод и модель и возвращает массив-заготовку
	 * Sets model and method for query. Returned blank-array
	 */
	public static function createQueryAPI($modelName, $calledMethod, $methodProperties = 0)
	{
		$arr = array('modelName' => $modelName, 'calledMethod' => $calledMethod,'methodProperties'=>$methodProperties);
		if(!$methodProperties)
			unset($arr['methodProperties']);
		return $arr;
	}
	
	 /**
	  * ‚озвращает список городов, в которых есть новаЯ почта
	  * Returned the list cities, in which have branch NP.
	  */	
	public static function getCities(){
		$request = self::createQueryAPI('Address', 'getCities', array('Page'=>1));
		$data = self::send($request);
		if(!self::getStatusQuery($data))
			return FALSE;
		$cities = array();
		foreach($data->data as $val)
		{
			$cities[] = array('name' => $val->DescriptionRu, 'id'=> $val->Ref);
		}
		
		return $cities;
	}
	/**
	* Calculates the price delivery
	* ђасчитывает стоимость доставки
	* citySender - город отправителЯ
	* cityRecipient - город получателЯ
	* serviceType - тип сервиса
	* weight - вес посылки
	* cost - стоимость
	*/
	public static function getPriceDelivery($citySender = null,$cityRecipient = null,$serviceType = null,$weight, $cost)
	{
		if(empty($citySender))
			$citySender = self::$citySender;
		if(empty($serviceType))
			$serviceType = self::$serviceType;
		
		//формируем запрос && we form query
		$request = self::createQueryAPI('InternetDocument', 'getDocumentPrice', array(
			'CitySender' => $citySender,
			'CityRecipient' => $cityRecipient,
			'ServiceType' => $serviceType,
			'Weight' => $weight,
			'Cost' => $cost
		));
		
		//ЋтправлЯем запрос
		$data = self::send($request);
		
		if(!self::getStatusQuery($data))
			return FALSE;
		
		return $data->data[0]->Cost;
	}
	
	/*
		Function returned the date delivery
		”ункциЯ возвращает дату доставки груза
	*/
	public static function getDocumentDeliveryData($citySender, $cityRecipient, $serviceType, $dateTime)
	{
		if(empty($citySender))
			$citySender = self::$citySender;
		if(empty($serviceType))
			$serviceType = self::$serviceType;
			
		if(!$dateTime && self::isDateTimeDeliveryToday)	
			$dateTime = date('d.m.Y', time());
		
		//”ормируем запрос && we form query
		$request = self::createQueryAPI('InternetDocument', 'getDocumentDeliveryDate', array(
			'CitySender' => $citySender,
			'CityRecipient' => $cityRecipient,
			'ServiceType' => $serviceType,
			'DateTime' => $dateTime
		));
		
		
		
		$data = self::send($request);
		
		if(!self::getStatusQuery($data))
			return FALSE;
		
		return ($data->data[0]->DeliveryDate->date);
	}
	
	/* 
	* 	Function adds counterparty
	*   ”ункциЯ добавлЯет нового контрагента
	*   ‚се аргументы должны быть понЯтны, кроме counterpartyProperty - Recipient(получатель) или Sender(отправитель)
	*	Returned the ref id
	*/
	public static function addCounterparty($firstName, $middleName,$lastName, $phone, $cityRef, $counterpartyProperty, $counterpartyType = 'PrivatePerson', $OCPO = 0, $shipForm = 0)
	{
		if(!$OCPO)
		$params = array(
			'CityRef' => $cityRef,
			'Phone' => $phone,
			'FirstName' => $firstName,
			'MiddleName' => $middleName,
			'LastName' => $lastName,
			'CounterpartyProperty' => $counterpartyProperty,
			'CounterpartyType' => $counterpartyType
		);
		else
		$params = array(
			'CityRef' => $cityRef,
			'Phone' => $phone,
			'FirstName' => $firstName,
			'EDRPOU' => $OCPO,
			'OwnershipForm' => $shipForm,
			'CounterpartyProperty' => $counterpartyProperty,
			'CounterpartyType' => $counterpartyType
		);
		
				
		//create query
		$request = self::createQueryAPI('Counterparty', 'save', $params);
		
		//send
		$data = self::send($request);
		
		$contactPersonRef = $data->data[0]->ContactPerson->data[0]->Ref;
		
		if(!self::getStatusQuery($data))
			return FALSE;
		
		return array('CounterpartyRef' => $data->data[0]->Ref, 'ContactRef' => $contactPersonRef);
	}
	
	
	
	/*
	* Function adds contact face
	* ”ункциЯ добавлЯет контактное лицо
	*/
	public static function addContactPerson($firstName, $middleName,$lastName, $phone, $counterpartyRef)
	{
		//create query
		$request = self::createQueryAPI('ContactPerson', 'save', array(
			'Phone' => $phone,
			'FirstName' => $firstName,
			'MiddleName' => $middleName,
			'LastName' => $lastName,
			'CounterpartyRef' => $counterpartyRef,
		));
		
		//send
		$data = self::send($request);
		
		
		if(!self::getStatusQuery($data))
			return FALSE;
		return $data->data[0]->Owner;
	}
	
	/*
	*	Returned the all payment forms
	*	‚озвращает все формы оплаты
	*/
	public static function getPaymentForms()
	{
		$request = self::createQueryAPI('Common', 'getPaymentForms');
		$request = self::setLanguage($request, 'ru');
		$data = self::send($request);
		return $data->data;
	}
	
	public static function setLanguage($request, $language)
	{
		$request['language'] = $language;
		return $request;
	}
	
	/*
	* Returned the all streets
	* ‚озвращает все улицы
	*/
	public static function getStreets ($city)
	{
		$request = self::createQueryAPI('Address', 'getStreet', array(
			'CityRef' => $city
		));
		$request = self::setLanguage($request, 'ru');
		$data = self::send($request);
		if(!self::getStatusQuery($data))
			return FALSE;
		$streets = array();
		foreach($data->data as $val)
		{
			$streets[] = array('name' => $val->Description,'id' => $val->Ref);
		}
		return $streets;
	}
	
	
	/*
	* Add invoice. For use this function necessary to call __construct
	* ‘оздаЮт накладную . „лЯ использованиЯ этой функции необходимо вызвать __construct
	* https://my.novaposhta.ua/data/API2-161214-1644-22.pdf - looking value arguments
	*/
	
	public function saveInternetDocument($data)
	{
		$valuesDemand = preg_replace('/([^A-z,]+)/', '',  self::valuesDemand);
		$demandData = explode(',', $valuesDemand);
		foreach($demandData as $value)
		{
			if(isset($data[$value]))
				continue;
			if($this->__get($value))
				$data[$value] = $this->__get($value);
			//else
		//		var_dump($value);
		}
		$request = self::createQueryAPI('InternetDocument', 'save', $data);
		$data = self::send($request);
		
		if(!self::getStatusQuery($data))
			return FALSE;
			
		//var_dump($data);
	}
	
	/*
	*	Function register address
	*	ђегистрирует адресс
	*/
	public static function saveAddress($counterpartyRef, $streetRef, $buildingNumber, $flat)
	{
		$request = self::createQueryAPI('Address', 'save', array(
			'CounterpartyRef' => $counterpartyRef,
			'StreetRef' => $streetRef,
			'BuildingNumber' => $buildingNumber,
			'Flat' => $flat
		));
		
		$data = self::send($request);
		
		if(!self::getStatusQuery($data))
			return FALSE;
		
		return ($data->data[0]->Ref);
	}
	
}
	
