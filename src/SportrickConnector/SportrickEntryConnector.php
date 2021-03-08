<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 22/02/2021
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivitySiteActionBean;
use App\Lib\WhiteRabbit\WRClient;
use Cake\Chronos\Date;
use Cake\Datasource\ConnectionManager;
use koolreport\inputs\Select;
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



	/** Read Strem Entry
	 * @param $customerId
	 * @param $params
	 * @return bool
	 * @add  02/03/2021  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function read($customerId = null, $params = null)
	{
		\Cake\Log\Log::debug('Sportrick SportrickEntryConnector call read by $params ' . print_r($params, true));
		$viewlocationTable = TableRegistry::getTableLocator()->get('Crm.ViewLocation');
		if (empty($params['api_key'])) {
			\Cake\Log\Log::error('Sportrick SportrickEntryConnector error empty api_key');
			return false;
		}
		/**/
		//return true;
		/**/

		$entries = $this->getEntries($params);

		if (empty($entries)) {
			\Cake\Log\Log::debug('Sportrick SportrickEntryConnector NO Entries for suite_customerId ' . $customerId);
			return true;
		}
		\Cake\Log\Log::debug('Sportrick Entries found num ' . count($entries) . ' for suite_customerId ' . $customerId);

		$contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
		$branches = $this->connect();
		$branches_list = [];

		foreach ($branches as $branch) {
			$branches_list[$branch->id] = $branch->name;
		}

		foreach ($entries as $entry) {
			$contact = $contactsTable->getContactsFromContactCode($entry->customerId);
			if (empty($contact)) {
				\Cake\Log\Log::debug('Sportrick SportrickEntryConnector contact not found  ' . $entry->customerId) . ' for suite_customerId ' . $customerId;
				continue;
			}

			switch ($entry->direction) {
				case 'CheckIn':
					$typeAction = strtolower('CheckIn');
					break;
				case 'CheckOut':
					$typeAction = strtolower('CheckOut');
					break;
			}

			/*debug($contact->id);
			debug($contact->email_1);*/

			try {
				$data = (array)$entry;
				$data['source'] = $this->sportrick_source_name;
				$data['email1'] = $contact->email_1;
				$data['date'] = date("Y-m-d\TH:i:s.000\Z", strtotime($entry->datetime));

				$branchName = !empty($branches_list[$entry->branchId]) ? $branches_list[$entry->branchId] : '';
				$data['actionDetails'] = $entry->direction . ' on branchId ' . $entry->branchId . ' - branch name ' . $branchName;
				$data['properties'] = $entry;

				$this->createCrmConnection($customerId);

				$a = new ActivitySiteActionBean();
				$a->setCustomer($customerId);
				$a->setSource($this->sportrick_source_name);
				//$a->setToken($data['note']); //serve a far funzionare le automazioni
				$a->setActionId($typeAction);
				$a->setDataRaw($data);
				$a->setTypeIdentities('email');

				ActionsManager::pushActivity($a);

			} catch (\Exception $e) {
				\Cake\Log\Log::error('Sportrick SportrickEntryConnector NO ActivitySiteActionBean for $data ' . print_r($data, true) . '. Error ' . $e->getMessage() . ' for suite_customerId' . $customerId);
			}

		}

		\Cake\Log\Log::debug('Sportrick SportrickEntryConnector END INSERT Entries num ' . count($entries) . ' and updatedAt >= ' . $params['sportrickapi_lastdate_call'] . ' for suite_customerId' . $customerId);

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


	/** Get Sportrick Entries
	 * @param $params
	 * @return mixed|null
	 * @add  02/03/2021  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function getEntries($params)
	{
		try {
			$http = new WRClient();
			$date = new Date($params['sportrickapi_lastdate_call']);
			$data['fromDate'] = $date->format('Y-m-d');
			$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_entries, $data, $this->sportrick_api_headers);
			$res = json_decode($response->body);
			return ($res);

		} catch (\Exception $e) {
			debug($e->getMessage());
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_entries . ' error ' . $e->getMessage());
			return null;
		}
	}

}
