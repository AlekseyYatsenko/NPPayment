<?php
if (!defined('_PS_VERSION_'))
  exit;

//Подключаем файл API Нової Пошти
require_once('NP.php');


class paymentA extends PaymentModuleCore
{}

 if(isset($_REQUEST['ajax']))
{
	if(isset($_REQUEST['getWarehouses']) && isset($_REQUEST['ref']))
	{
		$warehouses = NP::getWarehouses($_REQUEST['ref']);
		$ret = array('<option value="0">Не выбрано</option>');
		foreach($warehouses as $val)
		{
			$ret[] = ('<option value="'.$val['id'].'">'.$val['name'].'</option>');
		}
		die(join($ret));
	}
	if(isset($_REQUEST['getCostDelivery']) && isset($_REQUEST['city']) && isset($_REQUEST['weight']) && isset($_REQUEST['cost']))
	{
		$ret = array();
		$price = NP::getPriceDelivery(null,$_REQUEST['city'], null, $_REQUEST['weight'], $_REQUEST['cost']);
		echo ($price);
		die;
	}
	if(isset($_REQUEST['getDocumentDeliveryData']) && isset($_REQUEST['city']))
	{
		$val = (NP::getDocumentDeliveryData(null, $_REQUEST['city'],0,0));
		$tmp = explode(' ', $val);
		$val = $tmp[0];
		unset($tmp);
		echo ($val);
		die;
	}
	
	if(isset($_REQUEST['addCounterparty']) && isset($_REQUEST['firstName']) && isset($_REQUEST['middleName']) && isset($_REQUEST['lastName']) && isset($_REQUEST['phone']) && isset($_REQUEST['cityRef']) && 
	isset($_REQUEST['counterpartyProperty']))
	{
		echo NP::addCounterparty($_REQUEST['firstName'], $_REQUEST['middleName'], $_REQUEST['lastName'], $_REQUEST['phone'], $_REQUEST['cityRef'], $_REQUEST['counterpartyProperty']);
		exit;
	}
	if(isset($_REQUEST['addContactPerson']) && isset($_REQUEST['firstName']) && isset($_REQUEST['middleName']) && isset($_REQUEST['lastName']) && isset($_REQUEST['phone']) && 
	isset($_REQUEST['counterpartyRef']))
	{
		NP::addContactPerson($_REQUEST['firstName'], $_REQUEST['middleName'], $_REQUEST['lastName'], $_REQUEST['phone'], $_REQUEST['counterpartyRef']);
	}
	if(isset($_REQUEST['getStreets']) && isset($_REQUEST['ref']))
	{
		$warehouses = NP::getStreets($_REQUEST['ref']);
		$ret = array('<option value="0">Не выбрано</option>');
		foreach($warehouses as $val)
		{
			$ret[] = ('<option value="'.$val['id'].'">'.$val['name'].'</option>');
		}
		die(join($ret));
	}
	
	if(
		isset($_REQUEST['addCounterparty']) &&
		isset($_REQUEST['city']) &&
		isset($_REQUEST['streets']) &&
		isset($_REQUEST['HomeNumber']) &&
		isset($_REQUEST['Flat']) &&
		isset($_REQUEST['LastName']) &&
		isset($_REQUEST['MiddleName']) &&
		isset($_REQUEST['FirstName']) &&
		isset($_REQUEST['tel']) //&&
		//isset($_REQUEST['cost']) &&
		//isset($_REQUEST['weight'])
		
	)
	{
		/* ПРОВЕРКА ДАННЫХ */
		global $cookie;
		//var_dump($cookie->id_customer);
		//$cart = db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."cart` WHERE `id_cart` = '".$_REQUEST['id_cart']."' && `id_customer` = '".Context::getContext()->customer->id."' LIMIT 1");
		//$cart = new Cart();
		//var_dump($cart->getTotalWeight());
		$NP = new NP();
		$Recipient = NP::addCounterparty($_REQUEST['FirstName'], $_REQUEST['MiddleName'], $_REQUEST['LastName'], $_REQUEST['tel'], $_REQUEST['city'], 'Recipient');
		$NP->Recipient = $Recipient['CounterpartyRef'];
		$NP->ContactRecipient = $Recipient['ContactRef'];
		//'7f0f3519-2519-11df-be9a-000c291af1b3'
		//$NP->Sender = NP::addCounterparty('Ирина','Николаевна', 'Деньгина' , '380675234133', 'db5c88f0-391c-11dd-90d9-001a92567626', 'Sender');
		$NP->RecipientAddress = NP::saveAddress($NP->Recipient, $_REQUEST['streets'], $_REQUEST['HomeNumber'], $_REQUEST['Flat']);
		$NP->SenderAddress = NP::saveAddress($NP->Sender, 'e57e14fe-d532-11de-8cc8-000c2965ae0e', 3,4);
		$data = array(
			'PayerType' => 'Sender',
			'PaymentMethod' => 'Cash',
			'DateTime' => date('d.m.Y', time() + 86400),
			'CargoType' => 'Cargo',
			'VolumeGeneral' => '0.0010',// Объём м. куб
			'Weight' => $_REQUEST['weight'],
			'ServiceType' => 'WarehouseWarehouse',
			'SeatsAmount' => 1 ,//кол-во мест
			'Description' => 'Аксессуары к одежде',
			'Cost' => $_REQUEST['cost'],
			'CitySender' => NP::$citySender,
			'SendersPhone' => '+380675234133',
			'CityRecipient' => $_REQUEST['city'],
			'RecipientsPhone' => $_REQUEST['tel']
		);
		var_dump($NP->Sender);
		$NP->saveInternetDocument($data);
	//	$order = new Order();
	//	$order->addOrderPayment();
		$payment = new PaymentA();
		//var_dump(Configuration::get('PS_OS_MYMODULE'));
	//	$payment->validateOrder($_REQUEST['id_cart'], 0, $_REQUEST['cost'], 'bankwire', NULL, array(), Currency::getIdByIsoCode(980), false, Context::getContext()->customer->id);
	//	var_dump(Context::getContext());
	}
	
	if(isset($_REQUEST['saveAddress']) && isset($_REQUEST['counterpartyRef']) && isset($_REQUEST['streetRef']) && isset($_REQUEST['buildingNumber']))
	{
		NP::saveAddress($_REQUEST['counterpartyRef'], $_REQUEST['streetRef'], $_REQUEST['buildingNumber']);
	}
} 

class paymentOrderModuleFrontController extends ModuleFrontController
{

	//Продукты
	public static $products = array();

  public function initContent()
  {
    parent::initContent();
	
	//Задаём список продуктов
	self::$products = $this->context->cart->getProducts();
	
	$cities = NP::getCities();
	array_unshift($cities, array('id'=>0, 'name' => 'Не выбрано'));
	
	$this->context->smarty->assign(array(
     'payment_name' => Configuration::get('MOD_ATUTORIAL_NAME'),
      'payment_color' => Configuration::get('MOD_ATUTORIAL_COLOR'),
	  'products' => self::$products,
	  'total_cart' => $this->context->cart->getOrderTotal(),
	  'default_currency' => Currency::getDefaultCurrency(),
	  'cities' => $cities,
	  'weight' => $this->context->cart->getTotalWeight(),
	  'paymentForms' => NP::getPaymentForms(),
	  'id_cart' => $this->context->cart->id
    ));
	
	//$order = new Order();
	//$order->addOrderPayment(8);
    $this->setTemplate('order.tpl');
  }
}
