<?php

namespace Mydiabeteshome\Crio;

use Exception;

// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\ClientException;

class Crio extends CrioServiceProvider{

    private $client_id = null;
    private $bearer_token = null;
    private $api_url = null;
    private $required_fields=['patient_details_create'=>['externalId','status','firstName','lastName','email'],
        'patient_details_update'=>['externalId','status','firstName','lastName'],
        'studies'=>['studyId','subjectStatus'],
        'procedures'=>[],
        'emergency_contacts'=>[]
    ];
    private $patient_status=['DECEASED','DO_NOT_ENROLL','NO_CONTACT_INFO','DELETED','DO_NOT_SOLICT','AVAILABLE'];

    /**
     * Objective of the function is to initilize crio with requisite infomration
     * @param int $client_id indentification provided for your site recived from CRIO
     * @param string $bearer_token received from CRIO
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

    /**
     * Objective of the function is to create a patient in CRIO
     * @param int $site_id unique site id for your site from CRIO
     * @param array $patient_details
     * @param array $studies
     * @param array $procedures
     * @param array $emergency_contacts
     */
    public function createPatient($site_id,$patient_details,$studies=array(),$procedures=array(),$emergency_contacts=array())
    {
        try{    
            $site_id=(int)$site_id;
            $patient_details_status=$this->validateFields('patient_details_create',$patient_details);
            if(!empty($site_id) && $patient_details_status['status']==1){
                $patient_data=$this->mapPatient($site_id,$patient_details);
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
                if(!empty($apiResponse)){
                    $apiResponse=json_decode($apiResponse);
                    if(empty($apiResponse->errors)){
                        if(!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message!='Unauthorized')){
                            return $apiResponse;
                        }
                        else{
                            throw new Exception('Credentials provided to CRIO is invalid',401);
                        }
                    }
                    else{
                        throw new Exception('Patient id and/or site id is invalid',500);
                    }
                }
                else{
                    throw new Exception('No data received from CRIO Server',404);
                }
            }
            else{
                throw new Exception('Site ID or Patient Details not provided or not in correct format',500);
            }  
        }
        catch(Exception $e){ dd($e);
            throw new Exception($e->getMessage(),500);
        }
               
    }

    /**
     * Objective of the fuction is to get patient data from CRIO
     * @param int $site_id unique site id for your site from CRIO
     * @param int $patient_id unique identifier for your patient given by CRIO
     * @return array having patient details
     */

    public function getPatient($site_id,$patient_id){
        try{
            $site_id=(int)$site_id;
            $patient_id=(int)$patient_id;
            if(!empty($site_id) && !empty($patient_id)){
                $cURLConnection = curl_init($this->api_url."/patient/$patient_id/site/$site_id?client_id=".$this->client_id);
                curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer  $this->bearer_token",
                    'Accept: application/json',
                    'content-type: application/json'
                ));
                $apiResponse = curl_exec($cURLConnection);
                curl_close($cURLConnection);
                if(!empty($apiResponse)){
                    $apiResponse=json_decode($apiResponse);
                    if(empty($apiResponse->errors)){
                        if(!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message!='Unauthorized')){
                            return $apiResponse;
                        }
                        else{
                            throw new Exception('Credentials provided to CRIO is invalid',401);
                        }
                    }
                    else{
                        throw new Exception('Patient id and/or site id is invalid',500);
                    }
                }
                else{
                    throw new Exception('No data received from CRIO Server',404);
                }
            }
            else{
                throw new Exception('Site Patient ID not provided or not in correct format',500);
            }    
        }
        catch(Exception $e){
            throw new Exception($e->getMessage(),500);
        }
    }

    /**
     * Objective of the funciton is to update patient data and send it to CRIO
     * @param int $site_id id of the site from CRIO
     * @param array $patient_details having patient data
     * @param int $patient_id id of the patient
     * @return array API response
     */

    public function updatePatient($site_id,$patient_details,$patient_id){
        try{    
            $site_id=(int)$site_id;
            $patient_details_status=$this->validateFields('patient_details_update',$patient_details);
            if(!empty($site_id) && !empty($patient_id) && $patient_details_status['status']==1){
                $patient_data=$this->mapPatient($site_id,$patient_details,$patient_id);
                $cURLConnection = curl_init($this->api_url."/patient/{$patient_id}?client_id={$this->client_id}");
                curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($patient_data));
                curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer  $this->bearer_token",
                    'Accept: application/json',
                    'content-type: application/json'
                ));
                $apiResponse = curl_exec($cURLConnection);
                curl_close($cURLConnection);
                if(!empty($apiResponse)){
                    $apiResponse=json_decode($apiResponse);
                    if(empty($apiResponse->errors)){
                        if(!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message!='Unauthorized')){
                            return $apiResponse;
                        }
                        else{
                            throw new Exception('Credentials provided to CRIO is invalid',401);
                        }
                    }
                    else{
                        throw new Exception('Patient id and/or site id is invalid',500);
                    }
                }
                else{
                    throw new Exception('No data received from CRIO Server',404);
                }
            }
            else{
                throw new Exception('Site Patient ID not provided or not in correct format',500);
            }  
        }
        catch(Exception $e){
            throw new Exception($e->getMessage(),500);
        }

        
    }

    /**
     * Objective of the function is to validate fields and throw out errors for mandatory fields
     * @param string $key denotes the section
     * @param array $data patient data received 
     * @return array having status and message
     */

    private function validateFields($key,$data){
        $response=['status'=>1,'message'=>''];
        switch($key){
            case 'patient_details_create':
            case 'patient_details_update':
                $mandatory_fields=$this->required_fields;
                $mandatory_fields=$mandatory_fields[$key];
                foreach($mandatory_fields as $mandatory){
                    if(!array_key_exists($mandatory,$data)){
                        $response['status']=0;
                        $response['message']=$mandatory.' is required and does not exist';
                        return $response;
                    }
                }
            break;
        }
        return $response;
    }

    /**
     * Objective of the function is map the linear data provided into CRIO accepted fields
     * @param int $site_id id of the site of crio
     * @param array $patient_details containing patient data
     * @param int $patient_id id of the patient
     * @param int $revision revision number if required
     * @return array formatted array for CRIO
     */

    private function mapPatient($site_id,$patient_details,$patient_id=null,$revision=null){
        $patient_information=['patientId','externalId','birthDate','doNotCall','doNotEmail','doNotText','status','notes','gender','sex','dateCreated','lastUpdated','nin'];
        $patient_contact_information=['firstName','middleName','lastName','address1','address2','email','homePhone','cellPhone','workPhone','state','city','postalCode','countryCode'];
        $patient=['patientContact'=>array()];
        foreach($patient_information as $pi){
            if(array_key_exists($pi,$patient_details) && !empty($patient_details[$pi]))
            {
                $patient[$pi]=$patient_details[$pi];
            }
        }
        foreach($patient_contact_information as $pci){
            if(array_key_exists($pci,$patient_details) && !empty($patient_details[$pci]))
            {
                $patient['patientContact'][$pci]=$patient_details[$pci];
            }
        }
        if(!empty($patient_id)){
            $patient['patientId']=$patient_id;
        }
        $final_data=[
            'revision'=>$revision,
            'siteId'=>$site_id,
            'patientInfo'=>$patient
        ];
        return $final_data;
    }
}