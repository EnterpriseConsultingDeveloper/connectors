<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\InstagramConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;
use Cake\Log\Log;
use Cake\Collection\Collection;
class InstagramConnector extends Connector implements IConnector
{
  protected $insta;
  protected $accessToken;
  protected $appSecret;

  private $feedLimit;
  private $objectId;
  private $context;

  function __construct($params)
  {
    $config = json_decode(file_get_contents('appdata.cfg', true), true);
    $api_base = $config['api_base'];

    //This is all you need to configure.
    $secret = $config['secret'];

    $client_id = $config['client_id'];
    $url = $api_base.'oauth/access_token';
    $redirectUri = '';
    if(!empty($params)) {
      $code = $params['code'];

      //echo $result;//Your response
//        curl -F 'client_id=CLIENT_ID' \
//    -F 'client_secret=CLIENT_SECRET' \
//    -F 'grant_type=authorization_code' \
//    -F 'redirect_uri=AUTHORIZATION_REDIRECT_URI' \
//    -F 'code=CODE' \
//    https://api.instagram.com/oauth/access_token


      /*            $ch = curl_init();
                  $pf = "client_id=".$client_id."&client_secret=".$secret."&grant_type=authorization_code&redirect_uri=".$redirectUri."&code=".$code;
                  curl_setopt($ch, CURLOPT_URL, $url);
                  curl_setopt($ch, CURLOPT_POST, 1);
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $pf);

                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                  $server_output = curl_exec ($ch);
                  curl_close ($ch);
                  if ($server_output == "OK") {

                  } else {
                      die("Something went wrong.");
                  }*/
    }

    $this->insta = $api_base;
  }

  /**
   * @param $config
   * @return string
   */
  public function connect($config)
  {
    return '<a href="TEST">Log in with AAA!</a>';
  }



  /**
   * @param null $objectId
   * @return array
   */
  public function read($objectId = null)
  {
    // Read complete public page feed
    if ($objectId == null)
      $objectId = $this->objectId;

    if ($objectId == null)
      return [];

    $objectId = $this->cleanObjectId($objectId);


    $token = '3561573774.cab61f6.3475cbbc097c4ab1b1dcc4deb69aace6';
    $url = 'https://api.instagram.com/v1/media/5536012723?access_token=' . $token;
    $url = 'https://api.instagram.com/v1/users/self/media/recent/?access_token=' . $token;
    $url = $this->insta . 'v1/users/3561573774/media/recent/?access_token=' . $token;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    $response = curl_exec($ch);
    curl_close($ch);
    $jsonDecoded = json_decode($response, true); // Returns an array
    //debug($jsonDecoded);
    // die;

    /*

            $serviceUrl = $this->api_base . '/v1/media/shortcode/auto?access_token=ACCESS-TOKEN';
            $json = file_get_contents($this->insta . $serviceUrl . $objectId, false, $this->context);
            $data = json_decode($json, true);

            // Append users that have taken an action on the page
            $social_users = array();
            $data['social_users'] = $social_users;

            return($data); */
    return null;
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

    $objectId = $this->cleanObjectId($objectId);
    $formatted_res = array();

    try {
      $serviceUrl = '/v1/media/shortcode/auto?access_token=ACCESS-TOKEN';
      $json = file_get_contents($this->insta . $serviceUrl . $objectId, false, $this->context);

      $res = json_decode($json, true);
      if(isset($res['statuses'])) {
        $res = $res['statuses'];
      }
      foreach($res as $key => $value) {
        try {
          // Check if the array returned is not related to content (e.g. stats array)
          if(!isset($value['text']) || empty($value['text']))
            continue;

          $element =  new ConnectorBean();
          $element->setBody($value['text']);
          $element->setCreationDate($value['created_at']);
          $element->setMessageId($value['id_str']);
          $element->setAuthor($value['user']['name']);

          //https://twitter.com/RadioNightwatch/status/827460856128614401
          $uri = 'https://twitter.com/' . $objectId . '/status/' . $value['id_str'];
          $element->setUri($uri);
          $element->setIsContentMeaningful(1);
          $element->setRawPost($value);

          $formatted_res[] = $element;
        } catch (\Exception $e) {
          continue;
        }
      }
    } catch (\Exception $e) {
      // Do nothing
    }

    return($formatted_res);
  }


  /**
   * @return array
   */
  public function write($content)
  {
  }

  public function update($content, $objectId)
  {
    return "Ho scritto " . $content . " su " . $objectId;
  }

  /**
   * @param null $objectId
   * @return \Twitter\TwitterResponse
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
    } catch(TwitterResponseException $e) {
      $stats['likes'] = [];
      $stats['likes_number'] = 0;
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }

    try {
      $statRequest = '/' . $objectId . '/comments';

      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      $stats['comment_number'] = count($response->getDecodedBody()['data']);
    } catch(TwitterResponseException $e) {
      $stats['comment_number'] = 0;
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }


    try {
      $statRequest = '/' . $objectId . '/sharedposts';
      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);
      $stats['sharedposts'] = count($response->getDecodedBody()['data']);

    } catch(TwitterResponseException $e) {
      $stats['sharedposts'] = 0;
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
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

    } catch(TwitterResponseException $e) {
      $stats['insights'] = [];
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
      $stats = [];
    }

    return $stats;
  }

  public function comments($objectId, $operation = 'r', $content = null)
  {
    if ($this->objectId == null) {
      return [];
    }

    try {
      $statRequest = '/' . $objectId . '/comments';

      $request = $this->fb->request('GET', $statRequest);
      $response = $this->fb->getClient()->sendRequest($request);

      return $response->getDecodedBody()['data'];
    } catch(TwitterResponseException $e) {
      return [];
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
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
    } catch(TwitterResponseException $e) {
      return [];
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
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
    } catch(TwitterResponseException $e) {
      return [];
    } catch(TwitterSDKException $e) {
      // When validation fails or other local issues
      echo 'Twitter SDK returned an error: ' . $e->getMessage();
      return [];
    }
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
   * Object ID can be only screen_name or url like 'dinofratelli' or 'https://www.instagram.com/applenws'
   * @param $objectId
   * @return mixed
   */
  private function cleanObjectId($objectId) {
    if(substr($objectId, 0, 25) === 'https://www.instagram.com/')
      return substr($objectId, 25, strlen($objectId));

    return $objectId;
  }

  /**
   * @return bool
   */
  public function isLogged()
  {

  }

  public function callback($params)
  {
    //$data['code'] = $_GET['code'];
    $code = $params['code'];

    $url = 'https://api.instagram.com/oauth/access_token';
    $opts = "client_id=cab61f6a3ba04ca484c5eb5cc1b8d62a&redirect_uri=" . SUITE_SOCIAL_LOGIN_CALLBACK_URL . "&client_secret=8e89d198b5874d94ac41bb4b588aafb5&grant_type=authorization_code&code=" . $code;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $opts);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    $jsonDecoded = json_decode($response, true); // Returns an array

    $data = [];
    $data['token'] = $jsonDecoded['access_token'];
    $data['username'] = $jsonDecoded['user']['username'];
    $data['profile_picture'] = $jsonDecoded['user']['profile_picture'];
    $data['userid'] = $jsonDecoded['user']['id'];
    $data['code'] = $params['code'];

    return $data;
  }

}