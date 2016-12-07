<?php
/**
 * WhiteRabbit
 * Copyright (c) WhiteRabbit srl
 *
 */
namespace WR\Connector;

interface IConnector
{
    public function read($objectId);
    public function readPublicPage($objectId);
    public function write($content);
    public function update($content, $objectId);
    public function delete($objectId);

    public function connect($config);
    public function mapFormData($data);
    public function stats($objectId);
    public function comments($objectId);
    public function user($objectId);
    public function add_user($content);
    public function update_categories($content);
}