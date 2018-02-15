<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WhiterabbitConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;

class WhiterabbitConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_wrapipath;
    protected $_wruser;
    protected $_wrpass;
    protected $_wrtoken;

    private $objectId;
    private $wr;

    function __construct($params)
    {
        // Call Whiterabbit app
        $this->_http = new WRClient();
        $this->_wrapipath = $params['apipath'];
        $this->_wruser = $params['username'];
        $this->_wrpass = $params['password'];


//        $connectPath = $this->_wrapipath . 'connect';
//
//        $response = $this->_http->post($connectPath, [
//            'username' => $this->_wruser,
//            'password' => $this->_wrpass
//        ]);
//
//        $this->_wrtoken = json_decode($response->body)->token;

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

    public function readPublicPage($objectId = null)
    {

    }

    /**
     * @return array
     */
    public function write($content)
    {
        $publishPath = $this->_wrapipath . '/publish';
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
        if($this->_wrtoken != null) {
            $deletePath = $this->_wrapipath . 'delete';
            $response = $this->_http->post($deletePath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_wrtoken
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

    /**
     * @return bool
     */
    public function isLogged()
    {

    }

    public function callback($params)
    {

    }

    public function setError($message) {

    }

}
