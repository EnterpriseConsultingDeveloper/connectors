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
use Cake\Log\Log;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

class FacebookConnector extends Connector implements IConnector
{
  protected $fb;
  protected $longLivedAccessToken;
  protected $accessToken;
  protected $appSecret;
  protected $objectFbId;
  protected $connectorUsersSettingsID;

  private $feedLimit;
  private $objectId;
  private $since;
  private $until;

  var $error = false;

  function __construct($params)
  {
    $config = json_decode(file_get_contents('appdata.cfg', true), true);

    $this->fb = new Facebook([
      'http_client_handler' => 'stream', // do not use Guzzle 5.*, prefer PHP streams
      'app_id' => $config['app_id'], //'1561093387542751',
      'app_secret' =>  $config['app_secret'], //'0c081fec3c3b71d6c8bdf796f9868f03',
      'default_graph_version' =>  $config['default_graph_version'] //'v2.6',
    ]);

    $this->accessToken = $config['app_id'];
    $this->appSecret = $config['app_secret'];
    
    // FIXME tolgo il notice, ma è cambiato qualcosa da qualche parte
    if (@isset($params['connectorUsersSettingsID']))
        $this->connectorUsersSettingsID = $params['connectorUsersSettingsID'];

    if ($params != null) {
      if (isset($params['longlivetoken']) && $params['longlivetoken'] != null) {
        $this->longLivedAccessToken = $params['longlivetoken'];
        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
      }

      $this->objectId = isset($params['pageid']) ? $params['pageid'] : '';
      $this->objectFbId = isset($params['pageid']) ? $params['pageid'] : '';

      $this->feedLimit = isset($params['feedLimit']) && $params['feedLimit'] != null ? $params['feedLimit'] : 20;
      $this->since = isset($params['since']) ? $params['since'] : null; // Unix timestamp since
      $this->until = isset($params['until']) ? $params['until'] : null; // Unix timestamp until
    }

//    $debugTokenCommand = 'https://graph.facebook.com/debug_token?input_token='.$this->longLivedAccessToken.'&amp;access_token='.$this->accessToken;
    $debugTokenCommand = 'https://graph.facebook.com/me?access_token='.$this->longLivedAccessToken;

    $http = new Client();
    $response = $http->get($debugTokenCommand);
    $body = $response->json;

//    if ($body['error']) {
//      $this->error = $body;
//      $error = ['Error' => $body['error']['code'], 'Message' => $body['error']['message']];
//      //debug($error); die;
//      return $error;
//    }

    if($response->code !== 200) {
      $this->error = 2;
      $error = ['Error' => $response->code, 'Message' => $response->headers['WWW-Authenticate']];
      //debug($error); die;
      return $error;
    }

  }

  /**
   * @param $config
   * @return string
   */
  public function connect($config)
  {

    $helper = $this->fb->getRedirectLoginHelper();

    $permissions = ['publish_actions','read_insights','public_profile','email','user_friends','manage_pages','publish_pages']; // Optional permissions
    $loginUrl = $helper->getLoginUrl(SUITE_SOCIAL_LOGIN_CALLBACK_URL, $permissions);


    return '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';

  }

  /**
   * @return bool
   */
  public function isLogged()
  {
    $logged = false;

    try {
      // Returns a `Facebook\FacebookResponse` object
      $response = $this->fb->get('/me?fields=id,name', $this->longLivedAccessToken);
      $logged = true;
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $logged = false;
      $this->setError($e->getMessage());
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      $logged = false;
      $this->setError($e->getMessage());
    }
//    $user = $response->getGraphUser();
    return $logged;

  }

  /**
   * @return array
   */
  public function getAccounts()
  {

    try {
      // Returns a `Facebook\FacebookResponse` object
      $response = $this->fb->get('/me?fields=accounts.limit(255)', $this->longLivedAccessToken);
      $logged = true;
    } catch (\Facebook\Exceptions\FacebookResponseException $e) {
      $logged = false;
//        echo 'Graph returned an error: ' . $e->getMessage();
//        exit;
    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
      $logged = false;
//        echo 'Facebook SDK returned an error: ' . $e->getMessage();
//        exit;
    }

    return $response->getDecodedBody();
  }

  /**
   * @return array
   */
  public function getUser()
  {

    try {
      // Returns a `Facebook\FacebookResponse` object
      $response = $this->fb->get('/me?fields=id,name', $this->longLivedAccessToken);
      $logged = true;
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $logged = false;
      //        echo 'Graph returned an error: ' . $e->getMessage();
      //        exit;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      $logged = false;
      //        echo 'Facebook SDK returned an error: ' . $e->getMessage();
      //        exit;
    }

    return $response->getDecodedBody();

  }



  /**
   * Read a Facebook entity
   *
   * @param null $objectId
   * @return array
   */
  public function read($objectId = null)
  {

    if ($this->tokenValid = false) {
      return;
    }

    // Read complete page feed
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }

    $limitString = '&limit=' . $this->feedLimit;
    if(!empty($this->since) && !empty($this->until)) {
      $limitString = '&since=' . $this->since . '&until=' . $this->until;
    }

    $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}' . $limitString;
    try {
      $response = $this->fb->sendRequest('GET', $streamToRead);
      $data = $response->getDecodedBody()['data'];
      $data['social_users'] = [];
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $data = [];
      $data['error'] = $e->getMessage();
    }

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
    $formattedResult = $this->format_result($data);
    return($formattedResult);
  }


  /**
   * @return array
   */
  public function write($content)
  {
    // Di default inserisco sul feed
    $post = strip_tags($content['content']['body']);
    if ($content['content']['link'] != null) {
      $post .= " " . $content['content']['link'];
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

    if ($objectId == null) {
      return;
    }

    //'/'.$graphNode['id'] ,array(), $facebook_access_token // esempio

    try {
      $response = $this->fb->delete($objectId);
    } catch(\Facebook\Exceptions\FacebookApiException $e) {
      $response = false;
    }

    return $response;
  }

  /**
   * @param $data
   * @return mixed
   */
  public function mapFormData($data) {

    // Necessary only if are authenticating a page, not a profile
//    if(isset($data['token'])) {
//      $client = $this->fb->getOAuth2Client();
//
//      try {
//        // Returns a long-lived access token
//        $accessToken = $client->getLongLivedAccessToken($data['token']);
//      } catch(FacebookSDKException $e) {
//        // There was an error communicating with Graph
//        echo $e->getMessage();
//        exit;
//      }
//
//      $data['longlivetoken'] = $accessToken->getValue();
//    }

    return $data;
  }

  /**
   * @param $objectId
   * @return array
   */
  public function stats($objectId)
  {
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }

    $stats = [];

    try {
      //$statRequest = '/' . $objectId . '/likes';

      //*aggiornamento API fb 2.8*/
      $statRequest = '/' . $objectId . '/fan_count';
      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      $stats['likes_number'] = count($response->getDecodedBody()['data']);
      $stats['likes'] = $response->getDecodedBody()['data'];
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $stats['likes'] = [];
      $stats['likes_number'] = 0;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }

    try {
      $statRequest = '/' . $objectId . '/comments';

      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      $stats['comment_number'] = count($response->getDecodedBody()['data']);
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $stats['comment_number'] = 0;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }


    try {
      $statRequest = '/' . $objectId . '/sharedposts';
      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      $stats['sharedposts'] = count($response->getDecodedBody()['data']);

    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $stats['sharedposts'] = 0;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
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
        if(isset($value['values']) && isset($value['values'][0]['value']))
          $myInsights[$value['name']] = $value['values'][0]['value'];
      }

      $stats['insights'] = $myInsights;

    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $stats['insights'] = [];
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }

    return $stats;
  }

  /**
   * @param $objectId The object where you want to read from or write to the comments. More information https://developers.facebook.com/docs/graph-api/reference/v2.9/object/comments
   * @param $operation The operation requested, 'r' stand for read, 'w' stand for write
   * @param $content
   * @return array
   */
  public function comments($objectId, $operation = 'r', $content = null)
  {
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }

    // In case of write first write comment than read the comment
    if($operation === 'w' && !empty($content)) {
      try {
        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
        $url = '/' . $objectId . '/comments';
        $data = [
          'message' => strip_tags($content['comment']),
        ];
        $response = $this->fb->post($url, $data);

        $commentId = $response->getDecodedBody()['id'];
        $commentRequest = '/' . $commentId . '?fields=message,created_time,like_count,from{name,picture,link}';

        $request = $this->fb->request('GET', $commentRequest);
        $response = $this->fb->getClient()->sendRequest($request);

        return $response->getDecodedBody();
      } catch(\Facebook\Exceptions\FacebookResponseException $e) {
        Log::write('debug', $e);
        return [];
      }
    }


    // In case of update first write comment than read the comment
    if($operation === 'u' && !empty($content)) {
      try {
        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
        $url = '/' . $objectId;

        $data = [
          'message' => strip_tags($content['comment']),
        ];
        $this->fb->post($url, $data);

        $url .= '?fields=message,created_time,like_count,from{name,picture,link}';
        $request = $this->fb->request('GET', $url);
        $response = $this->fb->getClient()->sendRequest($request);
        Log::write('debug', $url);
        return $response->getDecodedBody();
      } catch(\Facebook\Exceptions\FacebookResponseException $e) {
        Log::write('debug', $e);
        return [];
      }
    }


    // In case of delete
    if($operation === 'd') {
      try {
        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
        $url = '/' . $objectId;

        $request = $this->fb->request('DELETE', $url);
        $this->fb->getClient()->sendRequest($request);
        return true;
      } catch(\Facebook\Exceptions\FacebookResponseException $e) {
        Log::write('debug', $e);
        return false;
      }
    }

    try {
      $statRequest = '/' . $objectId . '/comments';

      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      return $response->getDecodedBody()['data'];

    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      Log::write('debug', $e);
      return [];
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      Log::write('debug', $e);
      return [];
    }
  }


  /**
   * @param $objectId
   * @param $fromDate
   * @return array
   */
  public function commentFromDate($objectId, $fromDate) {
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }

    try {
      $statRequest = '/' . $objectId . '/comments';

      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      return $response->getDecodedBody()['data'];
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      return [];
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      return [];
    }
  }

  /**
   * @param $objectId
   * @return array
   */
  public function user($objectId)
  {
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }
    //1165594993
    //email,about,age_range,birthday,currency,education,favorite_athletes,favorite_teams,hometown,about,birthday,inspirational_people,interested_in,languages,location,meeting_for,political,quotes,relationship_status,religion,significant_other,sports,website,work
    //id,name,first_name,last_name,middle_name,gender,cover,currency,devices,link,locale,name_format,timezone
    try {
      // Returns a `Facebook\FacebookResponse` object
      $objectId = '/' . $objectId;

      $response = $this->fb->get(
          '/' . $objectId,
          $this->longLivedAccessToken//'{access-token}'
      );
      debug($response); die;
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      debug($e->getMessage());
      return [];
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      debug($e->getMessage());
      return [];
    }
    $graphNode = $response->getGraphNode();
    /* handle the result */



  }

  /**
   * @param $content
   */
  public function add_user($content)
  {

  }

  /**
   * @param $content
   */
  public function update_categories($content)
  {

  }

  public function callback($params) {

    $config = json_decode(file_get_contents('appdata.cfg', true), true);
    $data = array();

    $helper = $this->fb->getRedirectLoginHelper();
    if (isset($_GET['state'])) {
      $helper->getPersistentDataHandler()->set('state', $_GET['state']);
    }

    try {
      $accessToken = $helper->getAccessToken();
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }

    if (! isset($accessToken)) {
      if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: " . $helper->getError() . "\n";
        echo "Error Code: " . $helper->getErrorCode() . "\n";
        echo "Error Reason: " . $helper->getErrorReason() . "\n";
        echo "Error Description: " . $helper->getErrorDescription() . "\n";
      } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Bad request';
      }
      exit;
    }

    // Logged in
    $data['token'] = $accessToken->getValue();

    // The OAuth 2.0 client handler helps us manage access tokens
    $oAuth2Client = $this->fb->getOAuth2Client();

    // Get the access token metadata from /debug_token
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);

    // Validation (these will throw FacebookSDKException's when they fail)
    $tokenMetadata->validateAppId($config['app_id']); // Replace {app-id} with your app id
    // If you know the user ID this access token belongs to, you can validate it here
    //$tokenMetadata->validateUserId('123');
    $tokenMetadata->validateExpiration();

    if (! $accessToken->isLongLived()) {
      // Exchanges a short-lived access token for a long-lived one
      try {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
      } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
        exit;
      }

      $data['longlivetoken'] = $accessToken->getValue();
//        echo '<h3>Long-lived</h3>';
//        debug($accessToken->getValue());
    }

    $client = $this->fb->getOAuth2Client();

    try {
      // Returns a long-lived access token
      $accessToken = $client->getLongLivedAccessToken($data['token']);
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // There was an error communicating with Graph
      echo $e->getMessage();
      exit;
    }

    $data['longlivetoken'] = $accessToken->getValue();

    return $data;

  }


  /**
   * Get fan from the stream
   *
   * @param null $objectId
   * @return array
   */
  public function captureFan($objectId = null)
  {
    // Read complete page feed
    if ($objectId == null) $objectId = $this->objectId;

    if ($objectId == null) {
      return [];
    }

    try {

      $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}&limit=' . $this->feedLimit;
      $response = $this->fb->sendRequest('GET', $streamToRead);

      $data = $response->getDecodedBody()['data'];

      // Append users that have taken an action on the page
      $social_users = array();

      foreach($data as $d) {
        $ancestor_body = isset($d['message']) ? $d['message'] : (isset($d['story']) ? $d['story'] : '');

        if(isset($d['reactions'])) {
          foreach($d['reactions']['data'] as $social_user) {
            $ub = new ConnectorUserBean();
            $ub->setName($social_user['name']);
            $ub->setId($social_user['id']);
            $ub->setAction($social_user['type']);
            $ub->setContentId($d['id']);
            $ub->setText('');

            $ub->setDate($d['created_time']);

            $ub->setAncestorBody($ancestor_body);

            $ub = $this->getUserExtraData($social_user['id'], $ub);

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

            $ub->setAncestorBody($ancestor_body);

            $ub = $this->getUserExtraData($social_user['id'], $ub);

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

                $ub->setAncestorBody($social_user['message']);

                $ub = $this->getUserExtraData($social_user['id'], $ub);

                $social_users[] = $ub;
              }
            }
          }
        }
      }

      $data['social_users'] = $social_users;

    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $data = [];
      $data['error'] = $e->getMessage();
    }

    return($data);
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

      $element->setIsContentMeaningful(1);
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

  private function getUserExtraData($userId, ConnectorUserBean $ub) {
    try {
      $request = $this->fb->request('GET', '/' . $userId . '/?fields=id,name,first_name,last_name,middle_name,gender,cover,currency,devices,link,locale');
      $response = $this->fb->getClient()->sendRequest($request);
      $extraData = $response->getDecodedBody();

      $ub->setFirstname($this->blankForEmpty($extraData['first_name']));
      $ub->setLastname($this->blankForEmpty($extraData['last_name']));
      $ub->setGender($this->blankForEmpty($extraData['gender']));
      $ub->setCoverimage($this->blankForEmpty($extraData['cover']['source']));
      $ub->setLocale($this->blankForEmpty($extraData['locale']));
      $ub->setCurrency($this->blankForEmpty($extraData['currency']));
      //$ub->setDevices($this->blankForNotSet($extraData['first_name']));

    } catch(\Facebook\Exceptions\FacebookResponseException $e) {

    } catch(\Facebook\Exceptions\FacebookSDKException $e) {

    }

    return $ub;
  }

  private function blankForEmpty(&$var) {
    return !empty($var) ? $var : '';
  }

  public function setError($message) {

   return $message;

    // $connectorUsersSettingsTable = TableRegistry::get('ConnectorUsersSettings');
    // $connectorUsersSettings = $connectorUsersSettingsTable->get($this->connectorUsersSettingsID);
    // $connectorUsersSettings->note = $message;
    // $connectorUsersSettingsTable->save($connectorUsersSettings);

  }
}
