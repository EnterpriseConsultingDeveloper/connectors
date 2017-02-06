<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;


use App\Lib\CRM\CRMManager;
use WR\Connector\Connector;
use WR\Connector\IConnector;

class MagentoOrderConnector extends MagentoConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        try {
            //debug($content); die;

            $data['typeid'] = 'order';
            $data['source'] = 'ecommerce';
            $data['sourceid'] = $content['customer_email'];
            $data['title'] = 'Acquisto fatto da ' . $content['customer_name'];
            $data['note'] = $content['items'];
            $crmManager = new CRMManager();
            $cmrRes = $crmManager->pushActivityoCrm($content['customer_id'], $data);

            return $cmrRes;

        } catch (\Exception $e) {
            return false;
        }

    }

    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }

        return $objectId;
    }


    public function update($content, $objectId)
    {
        return $content;
    }

}