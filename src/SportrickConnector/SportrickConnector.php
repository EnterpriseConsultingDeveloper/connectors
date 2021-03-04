<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\SportrickConnector;

use App\Lib\WhiteRabbit\WRClient;
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
		$this->sportrick_api_headers = [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-sprk-apikey' => $this->api_key
			]
		];
	}

	/**
	 * @param null $api_key
	 * @return mixed|string|null
	 */
	public function connect($api_key = null)
	{
		try {
			$http = new WRClient();
			$response = $http->get($this->sportrick_end_point . $this->sportrick_api_url_branches, array(), $this->sportrick_api_headers);
			$res = json_decode($response->body);
			return ($res);
		} catch (\Exception $e) {
			\Cake\Log\Log::error('Sportrick SportrickConnector connect for ' . $this->sportrick_end_point . $this->sportrick_api_url_branches . ' error ' . $e->getMessage());
			return null;
		}
	}


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


	public function customVariablesAdd($params)
	{
		try {
			$data['type'] = 'string';
			$data['name'] = $params['name'];
			$data['note'] = $params['note'];

			$http = new WRClient();
			$response = $http->post($params['domain'] . "rest/customVariable/add",$data,
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
