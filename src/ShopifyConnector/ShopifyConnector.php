<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ShopifyConnection;
use Cake\Collection\Collection;
use Abraham\ShopifyOAuth\ShopifyOAuth;
use PHPShopify;

class ShopifyConnector extends Connector implements IConnector
{
	//protected $tw;
	protected $app_key;
	protected $app_secret;

	private $feedLimit;
	private $objectId;
	protected $shopify;
	protected $shopUrl;
    protected $limitCall;

	function __construct($params)
	{


		$config = json_decode(file_get_contents('appdata.cfg', true), true);

		//debug($config); debug($params); die;
		$config = array(
			'ShopUrl' => isset($params['shop_url']) ? $params['shop_url'] : null, //'whiterabbittest.myshopify.com',
			'AccessToken' => isset($params['access_token']) ? $params['access_token'] : null, //'6e3e5605965b925764e0c67ffd3f1a0e',
			'SharedSecret' => $config['SharedSecret']
		);

		$this->shopify = new PHPShopify\ShopifySDK($config);
		$this->shopUrl = isset($params['shop_url']) ? $params['shop_url'] : null;
        $this->limitCall = 250;

	}

	public function connect($config)
	{
		return "connect";
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
	 * @return \Shopify\ShopifyResponse
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
