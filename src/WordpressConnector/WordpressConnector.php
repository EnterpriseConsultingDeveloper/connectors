<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WordpressConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;

class WordpressConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_wpapipath;
    protected $_wpuser;
    protected $_wppass;
    protected $_wptoken;

    private $objectId;
    private $wp;

    function __construct($params)
    {
        // Call Wordpress app
        $this->_http = new Client();
        $this->_wpapipath = $params['apipath'];
        $this->_wpuser = $params['username'];
        $this->_wppass = $params['password'];


        $connectPath = $this->_wpapipath . 'connect';

        $response = $this->_http->post($connectPath, [
            'username' => $this->_wpuser,
            'password' => $this->_wppass
        ]);

        $this->_wptoken = json_decode($response->body)->token;

    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {
//        $response = $this->_http->get('http://google.com/search', ['q' => 'widget'], [
//            'headers' => ['X-Requested-With' => 'XMLHttpRequest']
//        ]);

    }

    /**
     * @return array
     */
    public function write($content)
    {
        $publishPath = $this->_wpapipath . '/publish';
        $response = $this->_http->post($publishPath, [
            'type' => 'newsletter',
            'content' => '',
            'content_id' => ''
        ]);

        return $response;

    }

    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
        if($this->_wptoken != null) {
            $deletePath = $this->_wpapipath . 'delete';
            $response = $this->_http->post($deletePath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_wptoken
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

    public function comments($objectId)
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