<?php
/**
 * Created by Dino Fratelli.
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
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;

class FacebookConnector extends Connector implements IConnector
{
    protected $fb;
    protected $longLivedAccessToken;
    protected $accessToken;
    protected $appSecret;

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

        $this->accessToken = $config['app_id'];
        $this->appSecret = $config['app_secret'];

        if ($params != null) {
            if (isset($params['longlivetoken']) && $params['longlivetoken'] != null) {
                $this->longLivedAccessToken = $params['longlivetoken'];
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
            }

            $this->objectId = isset($params['pageid']) ? $params['pageid'] : '';

            $this->feedLimit = isset($params['feedLimit']) && $params['feedLimit'] != null ? $params['feedLimit'] : 10;
        }

    }

    public function connect($config)
    {
        return "connect";
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {
        // Read complete page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}&limit=' . $this->feedLimit;
        $response = $this->fb->sendRequest('GET', $streamToRead);
        $data = $response->getDecodedBody()['data'];

        // Append users that have taken an action on the page
        $social_users = array();

        foreach($data as $d) {
            if(isset($d['reactions'])) {
                foreach($d['reactions']['data'] as $social_user) {
                    $ub = new ConnectorUserBean();
                    $ub->setName($social_user['name']);
                    $ub->setId($social_user['id']);
                    $ub->setAction($social_user['type']);
                    $ub->setContentId($d['id']);
                    $ub->setText('');

                    $social_users[] = $ub;
                }
            }

            if(isset($d['comments'])) {
                foreach($d['comments']['data'] as $social_user) {
                    $ub = new ConnectorUserBean();
                    $ub->setName($social_user['from']['name']);
                    $ub->setId($social_user['from']['id']);
                    $ub->setAction('COMMENT');
                    $ub->setContentId($d['id']);
                    $ub->setDate($social_user['created_time']);
                    $ub->setText($social_user['message']);

                    $social_users[] = $ub;

                    if(isset($social_user['comments'])) {
                        foreach($social_user['comments']['data'] as $sub_comment) {
                            $ub = new ConnectorUserBean();
                            $ub->setName($sub_comment['from']['name']);
                            $ub->setId($sub_comment['from']['id']);
                            $ub->setAction('COMMENT');
                            $ub->setContentId($d['id']);
                            $ub->setDate($sub_comment['created_time']);
                            $ub->setText($sub_comment['message']);

                            $social_users[] = $ub;
                        }
                    }
                }
            }
        }

        $data['social_users'] = $social_users;

        //debug($data); die;
        return($data);
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null)
    {
        // Read complete public page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $urlToRead = "https://graph.facebook.com/" . $objectId . "?fields=posts&limit=" . $this->feedLimit . "&access_token=" . $this->accessToken . "|" . $this->appSecret;
        $http = new Client();
        $response = $http->get($urlToRead);
        $data = $response->json;
        return($this->format_result($data));
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


    /**
     * @param $posts
     * @return array
     */
    private function format_result($posts) {
        $beans = array();
        foreach($posts['posts']['data'] as $post) {
            $element =  new ConnectorBean();
            if(!empty($post['message']))
                $element->setBody($post['message']);
            else
                $element->setBody($post['story']);

            if(!empty($post['story']))
                $element->setTitle($post['story']);

            $element->setCreationDate($post['created_time']);
            $element->setMessageId($post['id']);
            $element->setAuthor('');

            //https://twitter.com/RadioNightwatch/status/827460856128614401
            $uri = 'https://www.facebook.com/' . $post['id'];
            $element->setUri($uri);

            $element->setRawPost($post);

            $beans[] = $element;
        }
        return $beans;
    }
}