<?php

namespace Mydiabeteshome\Crio;

use Exception;

// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\ClientException;

class Crio extends CrioServiceProvider{

    private $client_id = null;
    private $bearer_token = null;
    private $api_url = null;

    /**
     * Objective of the function is to initilize crio with requisite infomration
     * @param int $client_id indentification provided for your site
     * @param string $bearer_token 
     * @param string $mode
     */

    public function __construct($client_id, $bearer_token, $mode = 'sandbox')
    {
        $this->client_id = $client_id;
        $this->bearer_token = $bearer_token;
        switch ($mode) {
            case 'sandbox':
                $this->api_url = 'https://recruitment-api.np.clinicalresearch.io/api/v1';
                break;
            case 'default':
                $this->api_url = 'https://recruitment-api.np.clinicalresearch.io/api/v1';
                break;
        }
    }
    public function createPatient($patient_data)
    {
        try{    
            $cURLConnection = curl_init($this->api_url.'/patient?client_id='.$this->client_id);
            curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($patient_data));
            curl_setopt($cURLConnection, CURLOPT_POST, 1);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer  $this->bearer_token",
                'Accept: application/json',
                'content-type: application/json'
            ));
            $apiResponse = curl_exec($cURLConnection);
            curl_close($cURLConnection);
            $response = json_decode($apiResponse);
            return $response;
        }
        catch(Exception $e){
            dd($e->getMessage());
        }
               
    }

    public function getPatient($site_id,$patient_id){
        try{    
            $cURLConnection = curl_init($this->api_url."/patient/$patient_id/site/$site_id?client_id=".$this->client_id);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer  $this->bearer_token",
                'Accept: application/json',
                'content-type: application/json'
            ));
            $apiResponse = curl_exec($cURLConnection);
            curl_close($cURLConnection);
            return json_decode($apiResponse);
        }
        catch(Exception $e){
            dd($e->getMessage());
        }
    }

    public function updatePatient($patient_data){
        try{    
            $cURLConnection = curl_init($this->api_url.'/patient?client_id='.$this->client_id);
            curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($patient_data));
            curl_setopt($cURLConnection, CURLOPT_POST, 1);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer  $this->bearer_token",
                'Accept: application/json',
                'content-type: application/json'
            ));
            $apiResponse = curl_exec($cURLConnection);
            curl_close($cURLConnection);
            $response = json_decode($apiResponse);
            return $response;
        }
        catch(Exception $e){
            dd($e->getMessage());
        }
    }
}