<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class ShopifyCustomerConnector extends ShopifyConnector
{

	use MultiSchemaTrait;

	function __construct($params)
	{
		parent::__construct($params);
	}


	/**
	 * @param null $objectId
	 * @return array
	 */
	public function read($customerId = null)
	{

		$customers = $this->shopify->Customer->get();

		foreach($customers as $customer) {

			$data = [];
			$data['date'] = date('Y-m-d H:i:s', strtotime($customer['created_at']));
			$data['externalid'] = $this->notSetToEmptyString($customer['id']);
			$data['name'] = $this->notSetToEmptyString($customer['first_name']);
			$data['surname'] = $this->notSetToEmptyString($customer['last_name']);
			$data['email'] =  $this->notSetToEmptyString($customer['email']);
			$data['note'] = $this->notSetToEmptyString($customer['note']);
			$data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
			$data['telephone1'] = $this->notSetToEmptyString($customer['phone']);
			$data['tags'] = explode(',', $customer['tags']);
			$data['address'] = $this->notSetToEmptyString($customer['addresses'][0]['address1']);
			$data['city'] = $this->notSetToEmptyString($customer['addresses'][0]['city']);
			$data['nation'] = $this->notSetToEmptyString($customer['addresses'][0]['country_code']);
			$data['province'] = $this->notSetToEmptyString($customer['addresses'][0]['province_code']);
			$data['gdpr']['gdpr_marketing']['date'] = $this->notSetToEmptyString($customer['accepts_marketing_updated_at']);
			$data['gdpr']['gdpr_marketing']['value'] = ($customer['accepts_marketing'] == true) ? true : false;

			try {

				$this->createCrmConnection($customerId);
				$contactBean = new ActivityEcommerceAddUserBean();

				$contactBean->setCustomer($customerId)
					->setSource($this->shopUrl)
					->setToken($this->shopUrl)
					->setDataRaw($data);

				ActionsManager::pushActivity($contactBean);

			} catch (\Exception $e) {
				// Log error
			}

		}
	}

	private function notSetToEmptyString (&$myString) {
		return (!isset($myString)) ? '' : $myString;
	}

}