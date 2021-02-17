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
use Cake\Datasource\ConnectionManager;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use Cake\I18n\Time;
use Cake\Collection\Collection;

class SportrickTagConnector extends SportrickConnector
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
	 * @add  20/05/2020  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @copyright (c) 2020, WhiteRabbit srl
	 */
	public function read($customerId = null, $params = null)
	{
		\Cake\Log\Log::debug('Sportrick SportrickTagConnector call read by $params ' . print_r($params, true));

		try {
			$connection = ConnectionManager::get('crm');
			$tag = $connection->transactional(function () use ($params) {
				$tagsTable = TableRegistry::getTableLocator()->get('Crm.Tags');
				$contactsTagsTable = TableRegistry::getTableLocator()->get('Crm.ContactsTags');
				$viewlocationTable = TableRegistry::getTableLocator()->get('Crm.ViewLocation');
				$contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
				if (empty($params['api_key'])) {
					\Cake\Log\Log::error('Sportrick SportrickTagConnector error empty api_key');
					return false;
				}
				//find all sportrik tag api call

				$sportrikgetTags = $this->getTags();
				foreach ($sportrikgetTags as $id => $tag) {
					$sportrikTagsList[$tag->id] = $this->sportrick_tag_prefix . trim($tag->name);
				}


				//find all suite tag
				$suiteTagsList = $tagsTable->listTags();
				$suiteSportrickTagsList = [];
				//find all suite tag only sportrick prefix
				foreach ($suiteTagsList as $id => $tag) {
					if (substr($tag['name'], 0, strlen($this->sportrick_tag_prefix)) == $this->sportrick_tag_prefix) {
						$suiteSportrickTagsList[$tag['id']] = $tag['name'];
					}
				}

				//delete contactsTags old
				foreach ($suiteSportrickTagsList as $id => $tag) {
					if (!in_array($tag, $sportrikTagsList)) {
						\Cake\Log\Log::debug('Sportrick SportrickTagConnector cancel suite tag  ' . $tag);
						$contactsTagsTable->deleteAllTagID($id);
					}
				}

				//create new tag

				foreach ($sportrikTagsList as $id => $tag) {
					if (!in_array($tag, $suiteSportrickTagsList)) {
						\Cake\Log\Log::debug('Sportrick SportrickTagConnector Insert suite tag  ' . $tag);
						$tagsEntity = $tagsTable->newEntity();
						$tagsEntity->name = $tag;
						$tagsEntity->note = $tag;
						$tagsEntity->hash_tag = md5($tag);
						$tagsTable->save($tagsEntity);
					}
				}

				$suiteTagsList = $tagsTable->listTags();
				$suiteSportrickTagsList = [];
				//find all suite tag only sportrick prefix
				foreach ($suiteTagsList as $id => $tag) {
					if (substr($tag['name'], 0, strlen($this->sportrick_tag_prefix)) == $this->sportrick_tag_prefix) {
						$suiteSportrickTagsList[$tag['id']] = $tag['name'];
					}
				}

				foreach ($sportrikTagsList as $id => $tag) {
					$customers = $this->getTagCustomers($id);
					$suite_tag_id = array_keys($suiteSportrickTagsList, $tag)[0];
					$contactsTagsTable->deleteAllTagID($suite_tag_id);

					foreach ($customers as $customer) {
						$contact_id = $contactsTable->getContactsIDFromEmail($customer->email);
						if (!empty($contact_id)) {
							$data['contact_id'] = $contact_id;
							$data['tag_id'] = $suite_tag_id;
							$data['created'] = Time::now();
							$contactsTagsTable->addContactTag($data);
						}
					}
				}
				return true;
			});

		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickTagConnector error try ' . print_r($e->getMessage(), true));
			return false;
		}
		return $tag;
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


	public function getTags()
	{
		try {
			\Cake\Log\Log::debug('Sportrick SportrickTagConnector call api ' . $this->sportrick_end_point . $this->sportrick_api_url_tag);
			$http = new WRClient();
			$data['category'] = $this->sportrick_tag_category;
			$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_tag, $data, $this->sportrick_api_headers);
			$res = json_decode($response->body);
			\Cake\Log\Log::debug('Sportrick SportrickTagConnector response getTags count ' . count($res));

			return ($res);
		} catch (\Exception $e) {
			debug($e);
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}


	/**
	 * @param $api_key
	 * @return mixed|null
	 */
	public function getTagCustomers($sportrick_tag_id)
	{
		try {
			\Cake\Log\Log::debug('Sportrick SportrickTagConnector call api getTagCustomers from tag_id ' . $sportrick_tag_id);
			$http = new WRClient();
			$data['tagId'] = $sportrick_tag_id;
			$response = $http->post($this->sportrick_end_point . $this->sportrick_api_url_customer_search, json_encode($data), $this->sportrick_api_headers);
			$res = json_decode($response->body);
			\Cake\Log\Log::debug('Sportrick SportrickTagConnector response getTagCustomers count ' . count($res));
			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}

}
