<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Controller\Component\UtilitiesComponent;
use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivityEcommerceChangeStatusBean;
use App\Lib\WhiteRabbit\WRClient;
use Cake\I18n\Time;
use Cake\Utility\Security;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use Cake\Chronos\Date;
use DateTime;
use DateTimeZone;
use Cake\I18n\FrozenTime;

class SportrickOrderConnector extends SportrickConnector
{
	use MultiSchemaTrait;

	function __construct($params)
	{
		parent::__construct($params);
	}

	/**
	 * @param $customerId
	 * @param $params
	 * @return bool
	 * @add  28/05/2020  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2020, WhiteRabbit srl
	 */
	public function read($customerId = null, $params = null)
	{
		\Cake\Log\Log::debug('Sportrick SportrickOrderConnector call read by $params ' . print_r($params, true));
		if (empty($params['api_key'])) {
			\Cake\Log\Log::error('Sportrick SportrickOrderConnector error empty api_key');
			return false;
		}
		$contactsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Crm.Contacts');
		$documents = $this->getDocuments($params);

		foreach ($documents as $document) {

			$customer = $contactsTable->getContactsFromContactCode($document->customerId);
			if (empty($customer)) {
				continue;
			}
			$data['source'] = $this->sportrick_source_name;
			$data['email'] = $this->notSetToEmptyString($customer->email_1);
			$data['number'] = $this->notSetToEmptyString($document->number);
			$data['orderdate'] = date(\DateTime::ATOM, strtotime($document->issuedDate));
			//$data['order_status'] = $this->notSetToEmptyString($order->fulfillmentStatus);
			//$data['currency'] = $this->notSetToEmptyString($order->currency);
			//$data['subtotal'] = $this->notSetToEmptyString($order->taxAmount);
			//$data['cart_discount'] = $this->notSetToEmptyString($order->discount->value);

			/*
			 * 	$data['shipping_firstname'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->fullName->firstName);
			$data['shipping_lastname'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->fullName->lastName);
			$data['shipping_address'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->addressLine1);
			$data['shipping_postalcode'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->zipCode);
			$data['shipping_city'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->city);
			$data['shipping_country'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->country);
			$data['shipping_phone'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->phone);*/
			$data['total'] = $this->notSetToEmptyString($document->grossAmount);
			$data['tax_total'] = $this->notSetToEmptyString($document->taxAmount);
			//$data['payment_method'] = $this->notSetToEmptyString($order->billingInfo->paymentMethod);
			$data['products'] = array();
			$data['tags'] = array();
			foreach ($document->lines as $id => $product) {
				$data['products'][$id]['product_id'] = Security::hash(trim($product->description), 'sha256', true);
				$data['products'][$id]['name'] = $product->description;
				$data['products'][$id]['qty'] = $product->quantity;
				$data['products'][$id]['price'] = $product->unitPrice;
				//$data['products'][$id]['discount'] = $product->discount;
				/*new*/
				//$data['products'][$id]['sku'] = $this->notSetToEmptyString($product->sku);
				//  $data['products'][$id]['description'] = $this->notSetToEmptyString($product->discount);
				$data['products'][$id]['tax'] = $this->notSetToEmptyString($product->taxAmount);
				// date solo se recurring
				if (!empty($product->competenceStartDate && !empty($product->competenceEndDate))) {

					$data['products'][$id]['type'] = $product->competenceStartDate == $product->competenceEndDate ? $this->suite_subscription_one_time_label : $this->suite_subscription_recurring_label;
					$data['products'][$id]['period_start'] = $product->competenceStartDate == $product->competenceEndDate ? null : (new Date($product->competenceStartDate))->getTimestamp();
					$data['products'][$id]['period_end'] = $product->competenceStartDate == $product->competenceEndDate ? null : (new Date($product->competenceEndDate))->getTimestamp();
					$data['products'][$id]['auto_renew'] = false;
				} else {
					debug($document);
					die;
				}
			}
			try {
				$this->createCrmConnection($customerId);
				$changeStatusBean = new ActivityEcommerceChangeStatusBean();
				$changeStatusBean->setCustomer($customerId)
					->setSource($this->sportrick_source_name)
					->setToken($this->sportrick_source_name)
					->setDataRaw($data);
				$changeStatusBean->setTypeIdentities('email');
				ActionsManager::pushOrder($changeStatusBean);


			} catch (\Exception $e) {
				return false;
			}
		}

		return true;
	}


	private function notSetToEmptyString(&$myString)
	{
		return (!isset($myString)) ? '' : $myString;
	}

	/**
	 * @param $api_key
	 * @return mixed|null
	 */

	public function getDocuments($params)
	{
		try {
			$result = array();
			$http = new WRClient();
			$time = new DateTime;
			/*$data['forDigitalInvoicing'] = 'false';
			$dateStart = new FrozenTime('2010-10-01 00:00:00');
			$dateStep = new Time('2010-10-01 00:00:00');*/
			$dateStart = new FrozenTime($params['sportrickapi_lastdate_call']);
			$dateStep = new Time($params['sportrickapi_lastdate_call']);
			$dataEnd = new FrozenTime(Time::now()->format('Y-m-d\TH:i:s.000\Z'));
			while ($dateStep < $dataEnd) {
				$data['fromDate'] = (new FrozenTime($dateStep))->format('Y-m-d\TH:i:s.000\Z');
				$data['toDate'] = (new FrozenTime($dateStep->addMonths(1)))->format('Y-m-d\TH:i:s.000\Z');
				$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_paymentDocuments, $data, $this->sportrick_api_headers);
				$res = json_decode($response->body);
				\Cake\Log\Log::debug('Sportrick SportrickConnector getDocument fromDate ' . $data['fromDate'] . ' toDate ' . $data['toDate'] . ' count result  ' . count($res));
				$result = array_merge($result, $res);
			}

			\Cake\Log\Log::debug('Sportrick SportrickConnector getDocument FINAL count ' . count($result));

			return ($result);
		} catch (\Exception $e) {
			debug($e);
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}


}
