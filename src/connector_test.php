<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15/03/2017
 * Time: 10:31
 */


$connectorName = 'TwitterConnector';
$connectorClass = 'TwitterConnector';
$connectorManager = new \WR\Connector\ConnectorManager($connectorName, $connectorClass);

$params = [
    'key' => null,
    'longlivetoken' => null,
    'profileid' => 'dinofratelli'
];
$pageId = 'dinofratelli';
$result = $connectorManager->get_content($params, $pageId);
debug($result); die;