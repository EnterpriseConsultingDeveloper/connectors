<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\FacebookConnector;


use Facebook\Facebook;
use Facebook\FacebookRequest;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;

class FacebookConnector extends Connector implements IConnector
{
    protected $fb;
    protected $longLivedAccessToken;

    private $feedLimit;
    private $objectId;

    function __construct($params)
    {
        $config = json_decode(file_get_contents('appdata.cfg', true), true);

        $this->fb = new Facebook([
            'app_id' => $config['app_id'], //'1561093387542751',
            'app_secret' =>  $config['app_secret'], //'0c081fec3c3b71d6c8bdf796f9868f03',
            'default_graph_version' =>  $config['default_graph_version'] //'v2.6',
        ]);

        if ($params != null) {
            $this->longLivedAccessToken = $params['longlivetoken'];
            $this->objectId = isset($params['pageid']) ? $params['pageid'] : '';

            $this->fb->setDefaultAccessToken($this->longLivedAccessToken);

            if (isset($params['feedLimit']) && $params['feedLimit'] != null) {
                $this->feedLimit = $params['feedLimit'];
            } else {
                $this->feedLimit = 10;
            }
        }

    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {
        // Read complete page feed
        if ($this->objectId == null) {
            return [];
        }
        $objectId = $this->objectId;
        $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture},created_time,message},from{name,picture}&limit=' . $this->feedLimit;
        $response = $this->fb->sendRequest('GET', $streamToRead);
        $data = $response->getDecodedBody()['data'];
        return($data);
    }

    /**
     * @return array
     */
    public function write($content)
    {
        // Di default inserisco sul feed
        $post = strip_tags($content['content']['body']);
        if ($content['content']['main_url'] != null) {
            $post .= " " . $content['content']['main_url'];
        }

        $data = [
            'title' => $content['content']['title'],
            'message' => $post,
        ];


        $response = $this->fb->post('me/feed', $data);

        $nodeId = $response->getGraphNode()->getField('id');

        $info['id'] = $nodeId;
        $info['url'] = 'http://www.facebook.com/' . $nodeId;

        return $info;

    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return \Facebook\FacebookResponse
     */
    public function delete($objectId = null)
    {
        $response = $this->fb->delete($objectId);
        return $response;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function mapFormData($data) {

        // Necessary only if are authenticating a page, not a profile
        if(isset($data['token'])) {
            $client = $this->fb->getOAuth2Client();

            try {
                // Returns a long-lived access token
                $accessToken = $client->getLongLivedAccessToken($data['token']);
            } catch(FacebookSDKException $e) {
                // There was an error communicating with Graph
                echo $e->getMessage();
                exit;
            }

            $data['longlivetoken'] = $accessToken->getValue();
        }

        return $data;
    }

    public function stats($objectId)
    {
        if ($this->objectId == null) {
            return [];
        }

        $stats = [];

        try {
            $statRequest = '/' . $objectId . '/likes';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['likes_number'] = count($response->getDecodedBody()['data']);
            $stats['likes'] = $response->getDecodedBody()['data'];
        } catch(FacebookResponseException $e) {
            $stats['likes'] = [];
            $stats['likes_number'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['comment_number'] = count($response->getDecodedBody()['data']);
        } catch(FacebookResponseException $e) {
            $stats['comment_number'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/sharedposts';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['sharedposts'] = count($response->getDecodedBody()['data']);

        } catch(FacebookResponseException $e) {
            $stats['sharedposts'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/insights';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $insights = $response->getDecodedBody()['data'];

            $myInsights = [];
            foreach ($insights as $key => $value) {
                $myInsights[$value['name']] = $value['values'][0]['value'];
            }

            $stats['insights'] = $myInsights;

        } catch(FacebookResponseException $e) {
            $stats['insights'] = [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        return $stats;
    }

    public function comments($objectId)
    {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);

            return $response->getDecodedBody()['data'];
        } catch(FacebookResponseException $e) {
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }

    public function commentFromDate($objectId, $fromDate) {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];
        } catch(FacebookResponseException $e) {
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }


    public function user($objectId)
    {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $request = $this->fb->request('GET', '/' . $objectId);
            $response = $this->fb->getClient()->sendRequest($request);
            //debug($response->getGraphObject()); die;
            return $response->getDecodedBody();
        } catch(FacebookResponseException $e) {
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }

    public function add_user($content)
    {

    }

    public function update_categories($content)
    {

    }
}