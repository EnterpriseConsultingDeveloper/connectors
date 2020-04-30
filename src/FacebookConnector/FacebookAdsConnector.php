<?php

namespace WR\Connector\FacebookConnector;


use App\Controller\Component\UtilitiesComponent;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Facebook\Facebook;
use WR\Connector\Connector;
use WR\Connector\FacebookConnector\FacebookConnector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

class FacebookAdsConnector extends FacebookConnector
{

    public function __construct($params)
    {
        parent::__construct($params);
    }

    public function write($content)
    {
        return null;
    }

    public function update($content, $objectId)
    {
        return null;
    }

    public function read($objectId = null)
    {
        // Read only campaigns
        if ($objectId == null)
            $objectId = $this->objectAdsId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '?fields=campaigns{status,name,created_time},name,account_status';

        try {
            $request = $this->fb->request('GET', $streamToRead);
            $graphNode = $this->fb->getClient()->sendRequest($request);
            $graphEdge = $graphNode->getGraphNode()->getField('campaigns');

            $accountName = $graphNode->getGraphNode()->getField('name');
            $accountStatus = $graphNode->getGraphNode()->getField('account_status');
            $accountId = $graphNode->getGraphNode()->getField('id');

            $result = [];
            $adsArray['account_id'] = $accountId;
            $adsArray['account_name'] = $accountName;
            $adsArray['account_status'] = $accountStatus;

            if (isset($graphEdge)) {
                if ($this->fb->next($graphEdge)) {
                    $campaignsArray = $graphEdge->asArray();
                    $result += $campaignsArray;

                    while ($graphEdge = $this->fb->next($graphEdge)) {
                        $campaignsArray = $graphEdge->asArray();
                        $result = array_merge($result, $campaignsArray);
                    }

                } else {
                    $campaignsArray = $graphEdge->asArray();
                    $result += $campaignsArray;
                }


                foreach ($result as $key => $elem) {
                    $val = Hash::extract($result, $key . '.status');
                    $result = Hash::remove($result, $key . '.status');
                    $result = Hash::insert($result, $key . '.campaign_status', $val[0]);

                    $val = Hash::extract($result, $key . '.id');
                    $result = Hash::remove($result, $key . '.id');
                    $result = Hash::insert($result, $key . '.campaign_id', $val[0]);

                    $val = Hash::extract($result, $key . '.name');
                    $result = Hash::remove($result, $key . '.name');
                    $result = Hash::insert($result, $key . '.campaign_name', $val[0]);

                    $val = Hash::extract($result, $key . '.created_time');
                    $result = Hash::remove($result, $key . '.created_time');
                    $result = Hash::insert($result, $key . '.campaign_date', $val[0]);

                    $result[$key] += $adsArray;
                }
            }

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $result = [];
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function captureFan($objectId = null) {
        // Read complete lead gen
        if ($objectId == null) {
            return [];
        }

        $forms_lang = null;
        $forms_lang = Config::load( __DIR__ . '/lang', new Json)->all();
        $iso_lang = array_keys($forms_lang);

        $facebookFormTable = TableRegistry::getTableLocator()->get('FacebookForms');

        try {

            $streamToRead = '/' . $objectId . '?fields=adsets{ads{leads{field_data,form_id,platform,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,created_time}}}';
            $request = $this->fb->request('GET', $streamToRead);
            $graphNodes = $this->fb->getClient()->sendRequest($request);
            $graphEdgeAdSet = $graphNodes->getGraphNode()->getField('adsets');

            // Append users that have taken lead gen
            $social_users = array();
            $dataCrm = array();
            $form_ids = array();

            if (isset($graphEdgeAdSet)) {
                do {
                    foreach ($graphEdgeAdSet as $adSet) {
                        $graphEdgeAd = $adSet->getField('ads');
                        if (isset($graphEdgeAd)) {
                            do {
                                foreach ($graphEdgeAd as $ads) {
                                    $graphEdgeLead = $ads->getField('leads');
                                    if (isset($graphEdgeLead)) {
                                        do {
                                            foreach ($graphEdgeLead as $key => $leads) {
                                                $form_id = $leads->getField('form_id');
                                                $lead_id = $leads->getField('id');
                                                if (!in_array($form_id, array_keys($form_ids))) {
                                                    $form_table = $facebookFormTable->getFormFields($form_id);
                                                    if($form_table){
                                                        //revert array from Table
                                                        $form_fields_table = array_flip(json_decode($form_table->fields,true));
                                                        $form_ids[$form_id]['field_maps'] = $form_fields_table;
                                                    }else {
                                                        //GET locale of Forms
                                                        $streamToRead = '/' . $form_id . '?fields=locale';
                                                        $response = $this->fb->sendRequest('GET', $streamToRead);
                                                        //check if exist lang on folder
                                                        if (!isset($forms_lang) || !in_array($response->getDecodedBody()['locale'],
                                                                $iso_lang)) {
                                                            continue;
                                                        }
                                                        $fields_lang = $forms_lang[$response->getDecodedBody()['locale']];

                                                        $form_ids[$form_id]['locale'] = $response->getDecodedBody()['locale'];
                                                        $form_ids[$form_id]['field_maps'] = $fields_lang;
                                                    }

                                                }

                                                foreach ($leads->getField('field_data')->asArray() as $form_field) {
                                                    $social_users[$form_field['name']] = $form_field['values'][0];
                                                }

                                                $mapData = UtilitiesComponent::remap($social_users,
                                                    $form_ids[$form_id]['field_maps'], false);

                                                //Mapping Field Form and CRM for all Lang
                                                $dataCrm[$lead_id] = $mapData;
                                                $dataCrm[$lead_id] += [
                                                    "date_add" => $leads->getField('created_time'),
                                                    "action" => "lead",
                                                    "contentId" => $leads->getField('ad_id'),
                                                    "ad_id" => $leads->getField('ad_id'),
                                                    "platform" => $leads->getField('platform'),
                                                    "ad_name" => $leads->getField('ad_name'),
                                                    "id" => $lead_id,
                                                    "adset_name" => $leads->getField('adset_name'),
                                                    "adset_id" => $leads->getField('adset_id'),
                                                    "campaign_name" => $leads->getField('campaign_name'),
                                                    "campaign_id" => $leads->getField('campaign_id'),
                                                    "properties" => $leads->asArray(),
                                                ];
                                            }
                                        } while ($graphEdgeLead = $this->fb->next($graphEdgeLead));
                                    }
                                }
                            } while ($graphEdgeAd = $this->fb->next($graphEdgeAd));
                        }
                    }
                } while ($graphEdgeAdSet = $this->fb->next($graphEdgeAdSet));
            }

            $data['social_users'] = $dataCrm;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $data = [];
            $data['error'] = $e->getMessage();
        }

        return ($data);
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null) {
        // Read public page forms
        if ($objectId == null)
            $objectId = $this->objectFbId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '/leadgen_forms?fields=questions{key,type,label},locale,id,name,status,created_time,page{name}';

        try {
            $request = $this->fb->request('GET', $streamToRead);
            $graphNode = $this->fb->getClient()->sendRequest($request);
            $graphEdge = $graphNode->getGraphEdge();
            $result = [];

            if (isset($graphEdge)) {
                do {
                    $formsArray = $graphEdge->asArray();
                    $result = array_merge($result, $formsArray);
                } while ($graphEdge = $this->fb->next($graphEdge));

                foreach ($result as $key => $elem) {
                    $val = Hash::extract($result, $key . '.status');
                    $result = Hash::remove($result, $key . '.status');
                    $result = Hash::insert($result, $key . '.form_status', $val[0]);

                    $val = Hash::extract($result, $key . '.id');
                    $result = Hash::remove($result, $key . '.id');
                    $result = Hash::insert($result, $key . '.form_id', $val[0]);

                    $val = Hash::extract($result, $key . '.name');
                    $result = Hash::remove($result, $key . '.name');
                    $result = Hash::insert($result, $key . '.form_name', $val[0]);

                    $val = Hash::extract($result, $key . '.created_time');
                    $result = Hash::remove($result, $key . '.created_time');
                    $result = Hash::insert($result, $key . '.form_date', $val[0]);
                }
            }

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $result = [];
            $result['error'] = $e->getMessage();
        }

        return $result;

    }

    /**
     * @param $objectId
     * @return array
     * @link https://developers.facebook.com/docs/graph-api/reference/v5.0/insights facebook api insights
     */
    public function stats($objectId) {
        if ($objectId == null)
            return [];

        try {
            $statRequest = '/' . $objectId . '/insights?fields=spend,unique_clicks,unique_ctr,cpc,impressions,objective,video_play_actions,purchase_roas,frequency,unique_actions';

            $request = $this->fb->request('GET', $statRequest);
            $graphNode = $this->fb->getClient()->sendRequest($request);
            $graphEdge = $graphNode->getGraphEdge()->asArray();

            $stats['insights'] = [];
            $myInsights = [];

            if(isset($graphEdge[0])) {
                $insights = $graphEdge[0];
                foreach ($insights as $key => $value) {
                    if (isset($value) && !empty($insights)) {
                        if ($key == 'unique_actions' || $key == 'video_play_actions' || $key == 'purchase_roas') {
                            $metrics = (new Collection($value))->combine('action_type', 'value')->toArray();
                            $myInsights[$key] = $metrics;
                        } else {
                            $myInsights[$key] = $value;
                        }
                    }
                }
            }

            $stats['insights'] = $myInsights;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        return $stats;
    }

    public function setError($message) {

        return $message;
    }
}