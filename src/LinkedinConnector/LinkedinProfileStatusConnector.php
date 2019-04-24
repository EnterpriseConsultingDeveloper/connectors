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
