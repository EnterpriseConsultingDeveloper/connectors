<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08/02/2017
 * Time: 11:41
 */

namespace WR\Connector;

use App\Lib\Bean;
use Cake\I18n\Time;

class ConnectorUserBean extends Bean
{
    private $_name = '';
    private $_id = '';
    private $_action = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

}