<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 18:36
 */

namespace WR\Connector;
use Cake\Network\Http\Client;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class CRMManager
{
    private $client;
    private $serviceUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->serviceUrl = 'http://socialcrm.whiterabbitsuite.com/rest/';
    }

    /**
     * @param $customerId
     * @param $nlRecipient
     * @return mixed
     */
    public function pushClientToCrm ($customerId, $nlRecipient) {
        //Viene richiesto l’inserimento del nuovo contatto associandolo all’id cliente suite n.7
        //$service_url = 'http://socialcrm.whiterabbitsuite.com/rest/client/' . $customerId . '/';

        $fName = $nlRecipient->name != null ? $nlRecipient->name : $nlRecipient->email;
        $lName = $nlRecipient->surname != null ? $nlRecipient->surname : $nlRecipient->email;
        $data = array(
            'facebookid' => null,
            'externalid' => $nlRecipient->id,
            'source' => 'suite',
            'companyname' => $customerId,
            'firstname'=> $fName,
            'lastname'=> $lName,
            'vatnumber'=> null,
            'taxcode' => null,
            'address' => null,
            'city' => null,
            'zone' => null,
            'postalcode' => null,
            'province' => null,
            'nation' => null,
            'email1' => $nlRecipient->email,
            'email2' => null,
            'telephone1' => null,
            'telephone2' => null,
            'mobilephone1' => $nlRecipient->mobile,
            'mobilephone2' => null,
            'faxnumber' => null,
            'birthdaydate' => null,
            'birthplace' => null,
            'newsletter' => '1',
            'ordertotal' => '0',
            'orderaverage' => '0'
        );

        $url = $this->serviceUrl . 'client/' . $customerId . '/';
        $response = $this->client->post($url, $data);

        return $response;
    }

//Il webservice activity serve a popolare la tabella delle attività collegata ad un contact.
//Url base del servizio: http://socialcrm.whiterabbitsuite.com/rest/activity/
//Alla url base va concatenato sempre l’identificativo cliente della suite (intero)
//Esempio di chiamata valida:
//http://socialcrm.whiterabbitsuite.com/rest/client/7/
//Nome Tipo Descrizione
//typeid Varchar Tipo attività (post, site message)
//Source Varchar (Site, Facebookpost etc)
//sourceid varchar Indirizzo email o facebook id del contact a cui
//associare la nota
//date date Data (formato yyyy-mm-dd hh:ii:ss)
//title varchar Titolo da assegnare all’attività
//note text Eventuali note collegate all’attività
//properties text Array serializzato delle ulteriori info
//Esempio di chiamata POST
//<?php
//$service_url = 'http://socialcrm.whiterabbitsuite.com/rest/activity/7/';
//$curl = curl_init($service_url);
//$data = array(
//'typeid'=> 'post',
//'source'=> 'site',
//'sourceid'=> 'pietro.celeste@gmail.com',
//'date'=> date('Y-m-d'),
//'title' => 'REST TICKET',
//'note' => 'Sto provando l\'inserimento di un activity rest',
//'properties' => serialize(array('provo a serializzare'))
//);
//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($curl, CURLOPT_POST, true);
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//$resp = curl_exec($curl);
//curl_close($curl);

    public function pushActivityoCrm ($customerId, $data) {
        //Viene richiesto l’inserimento del nuovo contatto associandolo all’id cliente suite n.7
        //$service_url = 'http://socialcrm.whiterabbitsuite.com/rest/client/' . $customerId . '/';

        $data = array(
            'typeid'=> $data['typeid'],
            'source'=> $data['source'],
            'sourceid'=> $data['sourceid'],
            'date'=> date('Y-m-d'),
            'title' => $data['title'],
            'note' => $data['note']
        );

        $url = $this->serviceUrl . '/activity/' . $customerId . '/';
        $response = $this->client->post($url, $data);

        return $response;
    }
}