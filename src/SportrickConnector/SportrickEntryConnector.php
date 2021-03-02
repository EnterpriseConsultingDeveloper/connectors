<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\WhiteRabbit\WRClient;
use Cake\Chronos\Date;
use Cake\Datasource\ConnectionManager;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use Cake\I18n\Time;
use Cake\Collection\Collection;

class SportrickEntryConnector extends SportrickConnector
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
	 * @add  02/03/2021  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2022, WhiteRabbit srl
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

		$entries = $this->getEntries($params);
		/*	debug(count($customers));
			die;*/
		if (empty($entries)) {
			\Cake\Log\Log::debug('Sportrick Customer NO customer for suite_customerId ' . $customerId);
			return true;
		}
		\Cake\Log\Log::debug('Sportrick Customer found num ' . count($customers) . ' for suite_customerId ' . $customerId);

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
				die;
				return false;
			}

		}
		\Cake\Log\Log::debug('Sportrick SportrickConnector END INSERT Customer num ' . count($customers) . " and updatedAt >" . $params['sportrickapi_lastdate_call']);

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


	/**
	 * @param $api_key
	 * @return mixed|null
	 */
	public function getEntries($params)
	{
		try {
			$http = new WRClient();
			$date = new Date($params['sportrickapi_lastdate_call']);
			$data['fromDate'] = $date->format('Y-m-d');
			debug($data);
			$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_entries, $data, $this->sportrick_api_headers);
			$res = json_decode($response->body);

			debug($res);
			die;
			return ($res);

		} catch (\Exception $e) {
			debug($e->getMessage());
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}

}
