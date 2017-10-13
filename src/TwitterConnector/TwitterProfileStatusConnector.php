<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\TwitterConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;

class TwitterProfileStatusConnector extends TwitterConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     * function write
     */
    public function write($content)
    {
        $post = strip_tags($content['content']['body']);

        if ($content['content']['main_image'] != null) {
            $media1 = $this->twitter->upload('media/upload', ['media' => $content['content']['main_image']]);

            $parameters = [
                'status' => $post,
                'media_ids' => $media1->media_id_string
            ];

        } else {
            $parameters = [
                'status' => $post
            ];
        }

        $resObj = $this->twitter->post("statuses/update", $parameters);

        $res = get_object_vars($resObj);
        $res['user'] = get_object_vars($resObj->user);
        $res['url'] = 'https://twitter.com/' . $res['user']['screen_name'] . '/status/' . $res['id'];

        return $res;
    }

    public function read($objectId = null)
    {
    }


    public function update($content, $objectId)
    {
        $post = strip_tags($content['content']['body']);

        $resObj = $this->twitter->post("statuses/update", [
            "status" => $post
        ]);

        $res = get_object_vars($resObj);
        $res['user'] = get_object_vars($resObj->user);

        return $res;

    }

}