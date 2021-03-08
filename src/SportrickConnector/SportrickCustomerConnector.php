<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 18/02/2021
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\WhiteRabbit\WRClient;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use Cake\I18n\Time;
use Cake\Collection\Collection;
use Cake\Chronos\Date;

class SportrickCustomerConnector extends SportrickConnector
{

	use MultiSchemaTrait;

	function __construct($params)
	{
		parent::__construct($params);
	}

	/** Read Stream Customer Sportrick
	 * @param $customerId
	 * @param $params
	 * @return bool
	 * @add  14/02/2021  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function read($customerId = null, $params = null)
	{
		\Cake\Log\Log::debug('Sportrick SportrickCustomerConnector call read by $params ' . print_r($params, true));
		$viewlocationTable = TableRegistry::getTableLocator()->get('Crm.ViewLocation');
		if (empty($params['api_key'])) {
			\Cake\Log\Log::error('Sportrick SportrickCustomerConnector error empty api_key');
			return false;
		}
		/**/
		//return true;
		/**/
		$customers = $this->getCustomers($params);
		/*debug(count($customers));
			die;*/
		if (empty($customers)) {
			\Cake\Log\Log::debug('Sportrick SportrickCustomerConnector Customer NO customer for suite_customerId ' . $customerId);
			return true;
		}
		\Cake\Log\Log::debug('Sportrick SportrickCustomerConnector Customer found num ' . count($customers) . ' for suite_customerId ' . $customerId);

		foreach ($customers as $customer) {
			if (empty($customer->email)) {
				continue;
			}

			if (!empty($customer->addressCountry)) {
				$viewlocation = $viewlocationTable->getCountryFromProvince($customer->addressStateProv);
				if (!empty($viewlocation)) {
					$nation = $viewlocation->su_country;
				}
			}

			if ($customer->gender == "M") {
				$gender = "male";
			} elseif ($customer->gender = "F") {
				$gender = "female";
			}

			$data = [];
			$custom_variable = [];

			//aspettiamo che ci passano anche la data di creazione da usarea anche come data gdpr
			
			//$date_createdAt = date('Y-m-d H:i:s', strtotime($customer->metadata->createdAt));
			//$data['date'] = $date_createdAt;
			$data['contact_code'] = $this->notSetToEmptyString($customer->id);
			$data['name'] = $this->notSetToEmptyString($customer->firstName);
			$data['surname'] = $this->notSetToEmptyString($customer->lastName);
			$data['gender'] = $this->notSetToEmptyString($gender);
			$data['email'] = $this->notSetToEmptyString($customer->email);
			$data['site_name'] = $this->notSetToEmptyString($this->sportrick_source_name);
			$data['telephone1'] = $this->notSetToEmptyString($customer->phoneNumber);
			$data['address'] = $this->notSetToEmptyString($customer->addressStreet);
			$data['city'] = $this->notSetToEmptyString($customer->addressCity);
			$data['nation'] = $this->notSetToEmptyString($nation);
			$data['postalcode'] = $this->notSetToEmptyString($customer->addressZip);
			$data['province'] = $this->notSetToEmptyString($customer->addressStateProv);
			$data['fiscalcode'] = $this->notSetToEmptyString($customer->taxCode);
			$data['birthdaydate'] = Time::createFromFormat('Y-m-d', $customer->dateOfBirth)->toAtomString();
			$data['gdpr']['gdpr_marketing']['value'] = !empty($customer->marketingConsent) ? $customer->marketingConsent : false;
			$data['gdpr']['gdpr_marketing']['date'] = Time::now()->toAtomString();
			\Cake\Log\Log::debug('Sportrick SportrickConnector call ActivityEcommerceAddUserBean for customer ' . $data['email'] . ' for suite_customerId' . $customerId);
			//debug($this->sportrick_custom_variables);
			//debug($customer->defaultBranch->id);
			//debug($customer->defaultBranch->name);
			//		debug($customer);

			$index = 0;
			foreach ($this->sportrick_custom_variables as $id => $sportrick_custom_variable) {
				$customer_array = json_decode(json_encode($customer), true);
				$app = explode("_", $id);
				foreach ($app as $val) {
					$customer_array = $customer_array[$val];
				}
				$custom_variable[$index]['name'] = strtolower($sportrick_custom_variable);
				$custom_variable[$index]['value'] = $customer_array;
				$index++;
			}

			$data['custom_variables'] = !empty($custom_variable) ? $custom_variable : null;

			try {
				$this->createCrmConnection($customerId);
				$contactBean = new ActivityEcommerceAddUserBean();
				$contactBean->setCustomer($customerId)
					->setSource($this->sportrick_source_name)
					->setToken($this->sportrick_source_name)
					->setDataRaw($data);
				$contactBean->setTypeIdentities('email');

				ActionsManager::pushActivity($contactBean);

			} catch (\Exception $e) {
				\Cake\Log\Log::error('Sportrick SportrickConnector NO ActivityEcommerceAddUserBean for $data ' . print_r($data, true) . '. Error ' . $e->getMessage() . ' for suite_customerId' . $customerId);
			}

		}
		\Cake\Log\Log::debug('Sportrick SportrickConnector END INSERT Customer num ' . count($customers) . ' and updatedAt >= ' . $params['sportrickapi_lastdate_call'] . ' for suite_customerId' . $customerId);
		return true;
	}


	/**
	 * @param null $objectId
	 *
	 * @return array
	 */


	private function notSetToEmptyString(&$myString)
	{
		return (!isset($myString)) ? '' : $myString;
	}


	/** get Customer Sportrick
	 * @param $params
	 * @return array
	 * @add  14/02/2021  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function getCustomers($params)
	{
		try {
			$http = new WRClient();
			$data['lastModifiedDateTimeFrom'] = $params['sportrickapi_lastdate_call'];

			$response = $http->post($this->sportrick_end_point . $this->sportrick_api_url_customer_search, json_encode($data), $this->sportrick_api_headers);
			$res = json_decode($response->body);
			return ($res);

		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_customer_search . ' error ' . $e->getMessage());
			return null;
		}
	}


	/** Add Customer beta
	 * @param $params
	 * @return mixed|null
	 */
	public function addCustomer($params)
	{
		try {
			$http = new WRClient();
			$data['lastModifiedDateTimeFrom'] = $params['sportrickapi_lastdate_call'];

			$response = $http->post($this->sportrick_end_point . $this->sportrick_api_url_customer_search, json_encode($data), $this->sportrick_api_headers);
			$res = json_decode($response->body);
			return ($res);

		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_customer_search . ' error ' . $e->getMessage());
			return null;
		}
	}


	public function write($customer)
	{
		\Cake\Log\Log::debug('Sportrick SportrickCustomerConnector call write by $content ' . print_r($customer, true));
		try {
			$http = new WRClient();
			$response = $http->post($this->sportrick_end_point . $this->sportrick_api_url_customer_add, $customer, $this->sportrick_api_headers);

			$res = json_decode($response->body);

			//debug($response);
			debug($res);
			die;
			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . 'customVariable/add' . ' error ' . $e->getMessage());
			return null;
			// Log error
		}

	}


}
