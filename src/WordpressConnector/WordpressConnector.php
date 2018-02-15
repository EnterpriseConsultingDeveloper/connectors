<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WordpressConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;

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
        $this->_http = new WRClient();
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

    public function connect($config = null)
    {
        // In the connect we can eventually return properties of this media, like the category tree
        $category_tree = array();
        $wp_category = array();
        $wp_authors = array();
        $authors_tree = array();
        if ($this->_wptoken != null) {
            $readPath = $this->_wpapipath . 'connect';

            $response = $this->_http->get($readPath, [
                'q' => 'categories',
                'token' => $this->_wptoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            if (isset($bodyResp['categories']))
                $wp_category = $bodyResp['categories'];
            if (isset($bodyResp['authors']))
                $wp_authors = $bodyResp['authors'];
        }
//        Category Array from WP
//        (int) 0 => [
//            'term_id' => (int) 15,
//            'name' => 'Categoria1',
//            'slug' => 'categoria1',
//            'term_group' => (int) 0,
//            'term_taxonomy_id' => (int) 15,
//            'taxonomy' => 'category',
//            'description' => '',
//            'parent' => (int) 0,
//            'count' => (int) 1,
//            'filter' => 'raw',
//            'cat_ID' => (int) 15,
//            'category_count' => (int) 1,
//            'category_description' => '',
//            'cat_name' => 'Categoria1',
//            'category_nicename' => 'categoria1',
//            'category_parent' => (int) 0
//        ],
//      The category list must attain the standard only with id and text
        foreach ($wp_category as $cat) {
            $c['id'] = $cat['term_id'];
            $c['text'] = $cat['name'];

            $category_tree[] = $c;
        }

        foreach ($wp_authors as $author) {
            $c['id'] = $author['data']['ID'];
            $c['text'] = $author['data']['display_name'] . " - " . $author['data']['user_email'];
            $authors_tree[] = $c;
        }

        $result = array();
        $result['category'] = $category_tree;
        $result['authors'] = $authors_tree;
        return $result;
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
        if ($this->_wptoken != null) {
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

    public function mapFormData($data)
    {
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
            $ciChannel->categories_tree = json_encode($content['categories_tree']);
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
