<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 18/02/2021
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Lib\WhiteRabbit\WRClient;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorManager;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use Cake\Collection\Collection;
use DateTimeZone;
use Date;
use DateTime;

class SportrickConnector extends Connector implements IConnector
{

	protected $api_key;
	protected $sportrick_start_import_date;
	protected $sportrick_end_point;
	protected $sportrick_source_name;
	protected $sportrick_api_url_branches;
	protected $sportrick_api_url_customer_add;
	protected $sportrick_api_url_customer_search;
	protected $sportrick_api_url_tag;
	protected $sportrick_api_url_entries;
	protected $sportrick_tag_category;
	protected $sportrick_payment_documents_attribute_filter;
	protected $sportrick_payment_documents_parameter_call;
	protected $sportrick_tag_prefix;
	protected $sportrick_api_headers;
	protected $sportrick_source;
	protected $sportrick_api_url_paymentDocuments;
	protected $sportrick_custom_variables;
	protected $suite_subscription_recurring_label;
	protected $suite_subscription_one_time_label;
	protected $suite_form_branch;


	function __construct($params)
	{
		$config = json_decode(file_get_contents('appdata.cfg', true), true);
		$this->api_key = $params['api_key'];
		$this->sportrick_source_name = $config['sportrick_source_name'];
		$this->sportrick_start_import_date = $config['sportrick_start_import_date'];
		$this->sportrick_end_point = $config['sportrick_end_point'];
		$this->sportrick_api_url_branches = $config['sportrick_api_url_branches'];
		$this->sportrick_api_url_customer_add = $config['sportrick_api_url_customer_add'];
		$this->sportrick_api_url_customer_search = $config['sportrick_api_url_customer_search'];
		$this->sportrick_api_url_tag = $config['sportrick_api_url_tag'];
		$this->sportrick_api_url_entries = $config['sportrick_api_url_entries'];
		$this->sportrick_payment_documents_attribute_filter = $config['sportrick_payment_documents_attribute_filter'];
		$this->sportrick_payment_documents_parameter_call = $config['sportrick_payment_documents_parameter_call'];
		$this->sportrick_tag_prefix = $config['sportrick_tag_prefix'];
		$this->sportrick_tag_category = $config['sportrick_tag_category'];
		$this->sportrick_api_url_paymentDocuments = $config['sportrick_api_url_paymentDocuments'];
		$this->sportrick_custom_variables = $config['sportrick_custom_variables'];
		$this->suite_subscription_recurring_label = $config['suite_subscription_recurring_label'];
		$this->suite_subscription_one_time_label = $config['suite_subscription_one_time_label'];
		$this->suite_form_branch = $config['suite_form_branch'];
		$this->sportrick_api_headers = [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-sprk-apikey' => $this->api_key
			]
		];
	}

	/** Connect Sportrick
	 * @param null $api_key
	 * @return mixed|array|null
	 * @author  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @add: 13/02/2021
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function connect($api_key = null)
	{
		try {
			$http = new WRClient();
			$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_branches, array(), $this->sportrick_api_headers);
			if ($response->code != 200) {
				return false;
			}
			$res = json_decode($response->body);
			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}


	/** List Suite Custom Variables
	 * @param $params
	 * @return mixed|array|null
	 * @author  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @add: 18/02/2021
	 * @copyright (c) 2021, WhiteRabbit srl
	 */

	public function customVariablesList($params)
	{
		try {
			$http = new WRClient();
			$response = $http->get($params['domain'] . "/rest/customVariable/list", [],
				[
					'headers' => ['WR-Token' => $params['token'], 'Accept' => 'application/json']
				]);
			$res = json_decode($response->body);

			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . 'customVariable/list' . ' error ' . $e->getMessage());
			return null;
			// Log error
		}
	}

	/** Add Custom Variables
	 * @param $params
	 * @return mixed|array|null
	 * @author  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @add: 18/02/2021
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function customVariablesAdd($params)
	{
		try {
			$data['type'] = 'string';
			$data['name'] = $params['name'];
			$data['note'] = $params['note'];

			$http = new WRClient();
			$response = $http->post($params['domain'] . "rest/customVariable/add", $data,
				[
					'headers' => ['WR-Token' => $params['token'], 'Accept' => 'application/json']
				]);
			$res = json_decode($response->body);

			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . 'customVariable/add' . ' error ' . $e->getMessage());
			return null;
			// Log error
		}
	}


	/** Add Form Branch
	 * @param $params
	 * @return mixed|array|null
	 * @author  Fabio Mugnano <mugnano@enterprise-consulting.it>
	 * @add: 11/03/2021
	 * @copyright (c) 2021, WhiteRabbit srl
	 */
	public function formSuiteAdd($params)
	{

		try {
			$connection = ConnectionManager::get('crm');
			$response = $connection->transactional(function () use ($params) {
				$mtFormTable = TableRegistry::getTableLocator()->get('MarketingTools.MtForms');
				$mtFormFieldTable = TableRegistry::getTableLocator()->get('MarketingTools.MtFormFields');
				$connectorsTable = TableRegistry::getTableLocator()->get('Connectors');
				$branches = $this->connect();
				$select_template = [];
				$select_intro = null;

				foreach ($branches as $id => $branch) {
					if ($id == 0) {
						$selected = "selected='true'";
					} else {
						$selected = null;
					}
					$select_template[$id]['label'] = $branch->name;
					$select_template[$id]['value'] = $branch->id;
					$select_intro .= "<option value='$branch->id' $selected id='select-branch-0'> $branch->name</option>";
				}

				$form_template = json_encode($this->suite_form_branch['form_template']);
				$form_intro = $this->suite_form_branch['form_intro'];
				$form_template = str_replace('"' . $this->suite_form_branch['form_template_select_placeholder'] . '"', json_encode($select_template), $form_template);
				$form_intro = str_replace($this->suite_form_branch['form_intro_select_placeholder'], $select_intro, $form_intro);

				$mtForm = $mtFormTable->newEntity();

				$data['customer_id'] = $params['customer_id'];
				$data['release'] = 0;
				$data['key_access'] = $mtFormTable->generateKeyAccess();
				$data['is_template'] = 1;
				$data['form_name'] = $this->suite_form_branch['form_name'];
				$data['form_template'] = $form_template;
				$data['form_intro'] = $form_intro;
				$data['is_template'] = 1;
				$mtForm = $mtFormTable->patchEntity($mtForm, $data);
				$save = $mtFormTable->save($mtForm);
				if ($save) {
					foreach ($this->suite_form_branch['form_template'] as $id => $input) {
						$mtFormField = $mtFormFieldTable->newEntity();
						$dataField = array();
						$dataField['form_id'] = $save->id;
						$dataField['field_type'] = $input['type'];
						$dataField['field_name'] = $input['name'] ?? ' ';
						$dataField['field_label'] = $input['label'];
						$dataField['attributes'] = json_encode($dataField);
						$dataField['created'] = Time::now();
						$dataField['modified'] = Time::now();
						$mtFormField = $mtFormFieldTable->patchEntity($mtFormField, $dataField);
						if (!$mtFormFieldTable->save($mtFormField)) {
							\Cake\Log\Log::error('Sportrick SportrickConnector formSuiteAdd branch error save $mtFormField ' . print_r($mtFormField->getErrors(), true));
							return false;
						}
					}
					return true;
				} else {
					\Cake\Log\Log::error('Sportrick SportrickConnector formSuiteAdd branch error save $mtForm');
					return false;
				}
			});
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector formSuiteAdd branch try ' . print_r($e->getMessage(), true));
			return false;
		}
		\Cake\Log\Log::debug('Sportrick SportrickConnector formSuiteAdd save $response ' . print_r($response, true));
		return $response;
	}


	/**
	 * @param null $objectId
	 * @return array
	 */
	public function read($objectId = null, $params = null)
	{

	}

	/**
	 * @param null $objectId
	 * @return array
	 */
	public function readPublicPage($objectId = null)
	{

	}


	/**
	 * @return array
	 */
	public function write($content)
	{

	}

	public function update($content, $objectId)
	{
	}

	/**
	 * @param null $objectId
	 */
	public function delete($objectId = null)
	{
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function mapFormData($data)
	{
		return $data;
	}

	public function stats($objectId)
	{

	}

	/**
	 * @param $objectId
	 * @param string $operation
	 * @param null $content
	 * @return array|mixed|string
	 */
	public function comments($objectId, $operation = 'r', $content = null)
	{
	}

	public function commentFromDate($objectId, $fromDate)
	{

	}


	public function user($objectId)
	{

	}

	public function add_user($content)
	{

	}

	public function update_categories($content)
	{

	}

	public function captureFan($objectId = null)
	{

	}


	/**
	 * @return bool
	 */
	public function isLogged()
	{

	}

	public function callback($params)
	{
	}

	public function configData()
	{
		return json_decode(file_get_contents('appdata.cfg', true), true);
	}

	public function setError($message)
	{

	}


}
