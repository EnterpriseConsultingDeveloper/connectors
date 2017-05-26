<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;

class MagentoConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_mageapipath;
    protected $_mageuser;
    protected $_magepass;
    protected $_magetoken;

    private $objectId;
    private $mage;

    function __construct($params)
    {
        // Call Magento app
        $this->_http = new WRClient();
        $this->_mageapipath = $params['apipath'];
        $this->_mageuser = $params['username'];
        $this->_magepass = $params['password'];


        $connectPath = $this->_mageapipath . 'connect';

        $response = $this->_http->post($connectPath, [
            'username' => $this->_mageuser,
            'password' => $this->_magepass
        ]);

        $this->_magetoken = json_decode($response->body)->token;

    }

    public function connect($config)
    {
        if ($this->_magetoken != null) {
            $readPath = $this->_wpapipath . 'connect';

            $response = $this->_http->get($readPath, [
                'q' => 'categories',
                'token' => $this->_magetoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            if (isset($bodyResp['categories']))
                $wp_category = $bodyResp['categories'];
            if (isset($bodyResp['authors']))
                $wp_authors = $bodyResp['authors'];
        }
    }

    public function read($objectId = null)
    {
//        $response = $this->_http->get('http://google.com/search', ['q' => 'widget'], [
//            'headers' => ['X-Requested-With' => 'XMLHttpRequest']
//        ]);

    }


    public function readPublicPage($objectId = null)
    {

    }

    /**
     * @return array
     */
    public function write($content)
    {
        $publishPath = $this->_mageapipath . '/publish';
        $response = $this->_http->post($publishPath, [
            'type' => 'newsletter',
            'content' => '',
            'content_id' => ''
        ]);

        return false;

    }

    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
        if($this->_magetoken != null) {
            $publishPath = $this->_mageapipath . 'delete';
            $response = $this->_http->post($publishPath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_magetoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            return $bodyResp['result'];
        } else {
            return false;
        }
    }

    public function mapFormData($data) {
        return $data;
    }

    public function stats($objectId)
    {

    }

    public function comments($objectId, $operation = 'r', $content = null)
    {

    }

    public function user($objectId)
    {

    }

    public function add_user($content)
    {

    }

    public function captureFan($objectId = null)
    {

    }

    public function update_categories($content)
    {
        //It's not correct to implement this here. Trying to find a different solutions
        $ciChannels = TableRegistry::get('ConnectorInstanceChannels');
        $ciChannel = $ciChannels->find('all')
            ->where(['ConnectorInstanceChannels.id' => $content['connector_instance_channel_id']])
            ->first();

        if ($ciChannel && isset($content['categories_tree'])) {
            $ciChannel->categories_tree = json_encode ($content['categories_tree']);
            try {
                return $ciChannels->save($ciChannel);
            } catch (\PDOException $e) {
                return false;
            }
        }

        return false;
    }
}