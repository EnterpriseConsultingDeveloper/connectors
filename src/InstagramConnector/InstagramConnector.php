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
	private $token;

	function __construct($params)
	{
		$config = json_decode(file_get_contents('appdata.cfg', true), true);
		$api_base = $config['api_base'];

		$this->token = empty($params['accesstoken']) ? '' : $params['accesstoken'];
		$this->insta = $api_base;
	}

	/**
	 * @param $config
	 * @return string
	 */
	public function connect($config)
	{
		return '';
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
		$http = new Client();

		$objectId = $this->cleanObjectId($objectId);
		$url = $this->insta . 'v1/users/self/media/recent/?access_token=' . $this->token;

		$maxPagination = 5;
		$countPagination = 0;
		$data = [];
		do {
			$exit = false;

			$response = $http->get($url);
			$res = $response->json;

			if (!$response->isOk())
				break;

			foreach ($res['data'] as $d)
				$data[] = $d;

			if (isset($res['pagination']['next_url'])) {
				if ($url == $res['pagination']['next_url']) {
					$exit = true;
				} else {
					$url = $res['pagination']['next_url'];
				}
			} else {
				$exit = true;
			}

			$countPagination++;

			if ($countPagination == $maxPagination)
				$exit = true;

		} while ($exit == false);

		foreach($data as $key => $value) {
			$commentsNumber = $value['comments']['count'];
			if($commentsNumber > 0) {
				// fare chiamata
			}
		}

//      debug($data);
//      exit;

		$formattedResult = $this->format_result($data);
		return($formattedResult);

		// TODO: manca la lettura dei commenti
		$url = $this->insta . 'v1/users/' . $objectId . '/media/recent/?access_token=' . $this->token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$jsonDecoded = json_decode($response, true); // Returns an array
		//debug($jsonDecoded); die;
		$comments = array();
		if(!isset($jsonDecoded['data']));
		return($jsonDecoded);

		foreach($jsonDecoded['data'] as $key => $value) {
			$commentsNumber = $value['comments']['count'];
			if($commentsNumber > 0) {
				$url = $this->insta . 'v1/media/' . $value['id'] . '/comments?access_token=' . $this->token;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);

				$comments = json_decode($response, true);
				$comments['parent_id'] = $value['id'];
				$jsonDecoded['data'][$key ]['comments']['list'] = $comments;
			}
		}

		//debug($jsonDecoded); die;

		return($jsonDecoded);
	}

	/**
	 * @param null $objectId
	 * @return array
	 */
	public function readPublicPage($objectId = null)
	{
		// Read complete public page feed
		if ($objectId == null)
			$objectId = $this->objectId;

		if ($objectId == null)
			return [];

		$objectId = $this->cleanObjectId($objectId);
		//$url = $this->insta . 'v1/users/' . $objectId . '/media/recent/?access_token=' . $this->token;

		$url = $this->insta . 'v1/users/self/media/recent/?access_token=' . $this->token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$jsonDecoded = json_decode($response, true); // Returns an array

		$formattedResult = $this->format_result($jsonDecoded);
		return($formattedResult);
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
	 */
	public function delete($objectId = null)
	{
		//$response = $this->fb->delete($objectId);
		return null;
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
		return null;
	}

	/**
	 * @param $objectId
	 * @param string $operation
	 * @param null $content
	 * @return array|mixed|string
	 */
	public function comments($objectId, $operation = 'r', $content = null)
	{
		if ($objectId == null) {
			return [];
		}

		$comments = [];
		if($operation === 'r') {
			try {
				$url = $this->insta . 'v1/media/' . $objectId . '/comments?access_token='.$this->token;
				$http = new Client();
				$data = [];
				do {

					$exit = false;

					$response = $http->get($url);

					if (!$response->isOk())
						break;

					$res = $response->json;


					foreach ($res['data'] as $d)
						$comments[] = $d;

					if (isset($res['pagination'])) {
						if ($url == $res['pagination']['next_url']) {
							$exit = true;
						} else {
							$url = $res['pagination']['next_url'];
						}
					} else {
						$exit = true;
					}

				} while ($exit == false);

//            if (isset($jsonDecoded['meta'])) $response->isOk()
//              if ($jsonDecoded['meta']['code'] != 200)
//                return false;

				$formattedResult = $this->format_result_comment($comments);

				return $formattedResult;

			} catch(\Exception $e) {

				return [];
			}
		}

		// In case of write first write comment than read the comment
		if($operation === 'w' && !empty($content)) {
			try {
				$url = $this->insta . 'v1/media/' . $objectId . '/comments';
				//'https://api.instagram.com/v1/media/1590543212022433315_3561573774/comments'

				$content = strip_tags($content['comment']);

				// Instagram accepts 300chr max for comment
				$content  = strlen($content) > 300 ? substr($content, 0, 296) . '...' : $content;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,
					"access_token=" . $this->token . "&text=" . $content);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				$response = curl_exec($ch);
				curl_close($ch);

				$res = json_decode($response, true); // Returns an array

				$compatiblePictureArray = [];
				$compatiblePictureArray['data']['is_silhouette'] = false;
				$compatiblePictureArray['data']['url'] = $res['data']['from']['profile_picture'];

				$compatibleFromArray = [];
				$compatibleFromArray['name'] = $res['data']['from']['username'];
				$compatibleFromArray['picture'] = $compatiblePictureArray;
				$compatibleFromArray['link'] = '';
				$compatibleFromArray['id'] = $res['data']['from']['id'];

				$compatibleResArray = [];
				$compatibleResArray['message'] = $res['data']['text'];
				$compatibleResArray['created_time'] = $res['data']['created_time'];
				$compatibleResArray['like_count'] = 0;
				$compatibleResArray['from'] = $compatibleFromArray;
				$compatibleResArray['id'] = $res['data']['id'];

				return $compatibleResArray;

			} catch(\Exception $e) {

				return [];
			}
		}

		// In case of delete
		if($operation === 'd') {
			try {
				$mediaId = $content['id'];
				$url = $this->insta . 'v1/media/' . $mediaId . '/comments/' . $objectId . '?access_token=' . $this->token;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_exec($ch);

				//$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				return true;
			} catch(\Exception $e) {
				Log::write('debug', $e);
				return false;
			}
		}
	}

	public function commentFromDate($objectId, $fromDate) {
		return null;
	}


	public function user($objectId)
	{
		return null;
	}

	public function add_user($content)
	{
		return null;
	}

	public function update_categories($content)
	{
		return null;
	}

	public function captureFan($objectId = null)
	{
		// Read complete public page feed
		if ($objectId == null)
			$objectId = $this->objectId;

		if ($objectId == null)
			return [];

		$http = new Client();

		$objectId = $this->cleanObjectId($objectId);

		$url = $this->insta . 'v1/users/self/media/recent/?access_token=' . $this->token;

		$maxPagination = 5;
		$countPagination = 0;
		$data = [];
		do {
			$exit = false;

			$response = $http->get($url);
			$res = $response->json;

			if (!$response->isOk())
				break;

			foreach ($res['data'] as $d)
				$data[] = $d;

			if (isset($res['pagination']['next_url'])) {
				if ($url == $res['pagination']['next_url']) {
					$exit = true;
				} else {
					$url = $res['pagination']['next_url'];
				}
			} else {
				$exit = true;
			}

			$countPagination++;

			if ($countPagination == $maxPagination)
				$exit = true;

		} while ($exit == false);


		$formattedResult = $this->format_result($data);
		return($formattedResult);
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

	/**
	 * @param $posts
	 * @return array
	 */
	private function format_result_comment($posts) {
		$beans = array();
		foreach($posts as $post) {
			$element =  new ConnectorBean();
			$element->setBody($post['text']);
			$element->setTitle($post['text']);

			$element->setIsContentMeaningful(1);
			$element->setCreationDate($post['created_time']);
			$element->setMessageId($post['from']['username']);
			$element->setAuthor($post['from']['username']);
			$element->setUri('');

			$element->setRawPost($post);

			$beans[] = $element;
		}
		return $beans;
	}


	/**
	 * @param $posts
	 * @return array
	 */
	private function format_result($posts) {
		$beans = array();
		foreach($posts as $post) {
			$element =  new ConnectorBean();
			$element->setBody($post['caption']['text']);
			$element->setTitle($post['caption']['text']);

			$element->setIsContentMeaningful(1);
			$element->setCreationDate($post['created_time']);
			$element->setMessageId($post['id']);
			$element->setAuthor($post['user']['id']);
			$element->setUri($post['link']);

			$element->setRawPost($post);

			$beans[] = $element;
		}
		return $beans;
	}


	public function setError($message) {

	}

}