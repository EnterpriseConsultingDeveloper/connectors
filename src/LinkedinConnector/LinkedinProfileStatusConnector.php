<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 19/04/2019
 * Time: 12:06
 */

namespace WR\Connector\LinkedinConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;

//use Cake\Network\Http\Client;
use Cake\Collection\Collection;
use App\Lib\Linkedin\Client;
use App\Lib\Linkedin\Scope;
use Cake\I18n\Time;
use Cake\Routing\Router;


class LinkedinProfileStatusConnector extends LinkedinConnector
{

    function __construct($params)
    {
        parent::__construct($params);
    }

    public function write($content)
    {

        $meAPICall = $this->getMe();

        if ($meAPICall['Error'] === true) {
            return $meAPICall;
        } else {
            $linkedin_id = $meAPICall['id'];
        }

        try {
            $share = $this->li->postV2(
                '/v2/shares',
                [
                    'content' => [
                        'contentEntities' => [
                            [
                                'entityLocation' => $content['content']['main_url'],
                                'thumbnails' => [
                                    [
                                        'resolvedUrl' => $content['content']['main_image']
                                    ]
                                ]
                            ]
                        ],
                        'title' => $content['content']['title']
                    ],
                    'distribution' => [
                        'linkedInDistributionTarget' => new \stdClass()
                    ],
                    'owner' => 'urn:li:person:' . $linkedin_id,
                    'subject' => $content['content']['title'],
                    'text' => [
                        'text' => $content['content']['abstract']
                    ]

                ]
            );

            $link = "https://www.linkedin.com/feed/update/" . $share['activity'];
            $info['id'] = $share['id'];
            $info['url'] = $link;

            return $info;

        } catch (\Throwable $th) {
            \Cake\Log\Log::debug('Linkedin share exception: ' . print_r($th->getMessage(), true));
            $value['Error'] = true;
            $value['Message'] = 'Linkedin share exception: ' . print_r($th->getMessage(), true);

            if ($th->getCode() === 409) {
                $value['Message'] = __('This Post can\'t be shared on Linkedin. Reason: Duplicated Title');
            }

            return $value;

        }
    }

    public function writeold($content)
    {
        try {
            $share = $this->li->post(
                'people/~/shares',
                [
                    'comment' => strip_tags($content['content']['abstract']),
                    'content' => [
                        'title' => $content['content']['title'],
                        'description' => $content['content']['meta_description'],
                        'submitted-url' => $content['content']['main_url'],
                        'submitted-image-url' => $content['content']['main_image'],
                    ],
                    'visibility' => [
                        'code' => 'anyone'
                    ]
                ]
            );
        } catch (\Throwable $th) {
            \Cake\Log\Log::debug('Likedin share exception: ' . print_r($th->getMessage(), true));
            $value['Error'] = true;
            $value['Message'] = 'Likedin share exception: ' . print_r($th->getMessage(), true);
            return $value;

        }

        if (isset($share['updateUrl'])) {
            $explode = explode("-", $share['updateKey']);
            $link = "https://www.linkedin.com/feed/update/urn:li:activity:" . $explode[2];
            // print_r($link);
            $info['id'] = $explode[2];
            $info['url'] = $link;
            return $info;
        }
        $value['Error'] = true;
        $value['Message'] = print_r($share, true);
        return $value;

    }


}
