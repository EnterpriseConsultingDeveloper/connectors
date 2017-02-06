<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\RSSConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;

class RSSUrlConnector extends RSSConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }


        $rssArray = [];
        $content = file_get_contents($objectId); // Feed url
        $x = new \SimpleXMLElement($content);

        $rssRow = [];
        foreach($x->channel->item as $entry) {
            //debug($entry); die;
            $rssRow['link'] = (string)$entry->link;
            $rssRow['title'] = (string)$entry->title;
            $rssRow['pubDate'] = (string)$entry->pubDate;
            $rssRow['description'] = (string)$entry->description;
            $rssRow['category '] = (string)$entry->category;
            $rssRow['guid'] = (string)$entry->guid;
            $rssArray[] = $rssRow;
        }

        return $rssArray;
    }


}