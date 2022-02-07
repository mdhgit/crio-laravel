<?php

namespace Mydiabeteshome\Crio;

use Exception;

// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\ClientException;

class Crio extends CrioServiceProvider
{

    private $client_id = null;
    private $bearer_token = null;
    private $api_url = null;
    private $required_fields = [
        'patient_details_create' => ['externalId', 'status', 'firstName', 'lastName', 'email'],
        'patient_details_update' => ['externalId', 'status', 'firstName', 'lastName'],
        'studies' => ['studyId', 'subjectStatus'],
        'procedures' => ['procedureKey','questionKey','value'],
        'emergency_contacts' => []
    ];
    private $patient_status = ['DECEASED', 'DO_NOT_ENROLL', 'NO_CONTACT_INFO', 'DELETED', 'DO_NOT_SOLICT', 'AVAILABLE'];

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
    public function createPatient($site_id, $patient_details, $studies = array(), $procedures = array(), $emergency_contacts = array())
    {
        try {
            $site_id = (int)$site_id;
            $patient_details_status = $this->validateFields('patient_details_create', $patient_details);
            $procedures=$this->validateFields('procedures',$procedures);
            if (!empty($site_id) && $patient_details_status['status'] == 1) {
                if(!empty($procedures) && !empty($procedures['data'])){
                    $procedures=$procedures['data'];
                    $procedures=$this->mapProcedures($procedures);
                }
                else{
                    $procedures=[];
                }
                $patient_data = $this->mapPatient($site_id, $patient_details,$studies,$procedures,$emergency_contacts);
                $cURLConnection = curl_init($this->api_url . '/patient?client_id=' . $this->client_id);
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
                if (!empty($apiResponse)) {
                    $apiResponse = json_decode($apiResponse);
                    if (empty($apiResponse->errors)) {
                        if (!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message != 'Unauthorized')) {
                            return $apiResponse;
                        } else {
                            throw new Exception('Credentials provided to CRIO is invalid', 401);
                        }
                    } else {
                        throw new Exception('Patient id and/or site id is invalid', 500);
                    }
                } else {
                    throw new Exception('No data received from CRIO Server', 404);
                }
            } else {
                throw new Exception('Site ID or Patient Details not provided or not in correct format', 500);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Objective of the fuction is to get patient data from CRIO
     * @param int $site_id unique site id for your site from CRIO
     * @param int $patient_id unique identifier for your patient given by CRIO
     * @return array having patient details
     */

    public function getPatient($site_id, $patient_id)
    {
        try {
            $site_id = (int)$site_id;
            $patient_id = (int)$patient_id;
            if (!empty($site_id) && !empty($patient_id)) {
                $cURLConnection = curl_init($this->api_url . "/patient/$patient_id/site/$site_id?client_id=" . $this->client_id);
                curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer  $this->bearer_token",
                    'Accept: application/json',
                    'content-type: application/json'
                ));
                $apiResponse = curl_exec($cURLConnection);
                curl_close($cURLConnection);
                if (!empty($apiResponse)) {
                    $apiResponse = json_decode($apiResponse);
                    if (empty($apiResponse->errors)) {
                        if (!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message != 'Unauthorized')) {
                            return $apiResponse;
                        } else {
                            throw new Exception('Credentials provided to CRIO is invalid', 401);
                        }
                    } else {
                        throw new Exception('Patient id and/or site id is invalid', 500);
                    }
                } else {
                    throw new Exception('No data received from CRIO Server', 404);
                }
            } else {
                throw new Exception('Site Patient ID not provided or not in correct format', 500);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Objective of the funciton is to update patient data and send it to CRIO
     * @param int $site_id id of the site from CRIO
     * @param array $patient_details having patient data
     * @param int $patient_id id of the patient
     * @return array API response
     */

    public function updatePatient($site_id, $patient_details, $patient_id, $studies = array(), $procedures = array(), $emergency_contacts = array())
    {
        try {
            $site_id = (int)$site_id;
            $patient_details_status = $this->validateFields('patient_details_update', $patient_details);
            $procedures=$this->validateFields('procedures',$procedures);
            if (!empty($site_id) && !empty($patient_id) && $patient_details_status['status'] == 1) {
                if(!empty($procedures) && !empty($procedures['data'])){
                    $procedures=$procedures['data'];
                    $procedures=$this->mapProcedures($procedures);
                }
                else{
                    $procedures=[];
                }
                
                $patient_data = $this->mapPatient($site_id, $patient_details, $studies, $procedures, $emergency_contacts, $patient_id);
                $cURLConnection = curl_init($this->api_url . "/patient/{$patient_id}?client_id={$this->client_id}");
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
                if (!empty($apiResponse)) {
                    $apiResponse = json_decode($apiResponse);
                    if (empty($apiResponse->errors)) {
                        if (!isset($apiResponse->message) || (isset($apiResponse->message) && $apiResponse->message != 'Unauthorized')) {
                            return $apiResponse;
                        } else {
                            throw new Exception('Credentials provided to CRIO is invalid', 401);
                        }
                    } else {
                        throw new Exception('Patient id and/or site id is invalid', 500);
                    }
                } else {
                    throw new Exception('No data received from CRIO Server', 404);
                }
            } else {
                throw new Exception('Site Patient ID not provided or not in correct format', 500);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Objective of the function is to validate fields and throw out errors for mandatory fields
     * @param string $key denotes the section
     * @param array $data patient data received 
     * @return array having status and message
     */

    private function validateFields($key, $data)
    {
        $response = ['status' => 1, 'message' => ''];
        $mandatory_fields = $this->required_fields;
        $mandatory_fields = $mandatory_fields[$key];
        switch ($key) {
            case 'patient_details_create':
            case 'patient_details_update':
                foreach ($mandatory_fields as $mandatory) {
                    if (!array_key_exists($mandatory, $data)) {
                        $response['status'] = 0;
                        $response['message'] = $mandatory . ' is required and does not exist';
                        return $response;
                    }
                }
                break;
            case 'procedures':
                
                foreach($data as $head){
                    $counter=0;
                    foreach($head['records'] as &$row){
                        foreach($mandatory_fields as $mandatory){
                            $row=(array)$row;
                            if(!array_key_exists($mandatory,$row)){
                                unset($row[$counter]);
                            }
                        }
                        $counter++;
                    }
                }
                $response['data']=$data;

            break;
        }
        return $response;
    }

    /**
     * Objective of the function is to map procedure data and return in a formatted way
     * @param 
     */
    private function formatProcedures($procedures_list){

    }

    /**
     * Objective of the function is to format patient data
     * @param int $patient_id id of patient (CRIO Primary Key)
     * @param string $json having webhook input from crio
     * @return
     */
    public function formatPatientData($patient_id, $json)
    {
        $response = ['status' => 0, 'message' => 'Patient ID or JSON was invalid'];
        if (!empty($patient_id) && !empty($json)) {
            $json = json_decode($json);
            if (!empty($json)) {
                $studies = [];
                $procedures = [];
                $emergency_contacts = [];
                $external_id=null;
                $patient_data = [
                    'site_id' => $json->siteId,
                    'revision' => $json->revision
                ];
                if (!empty($json->patientInfo)) {
                    $patient_info = $json->patientInfo;
                    $external_id=$patient_info->externalId;
                    $data = [
                        'patientId' => $patient_info->patientId,
                        'externalId' => $patient_info->externalId,
                        'birthDate' => !empty($patient_info->birthDate) ? date("Y-m-d", strtotime($patient_info->birthDate)) : '',
                        'doNotCall' => $patient_info->doNotCall,
                        'doNotEmail' => $patient_info->doNotEmail,
                        'doNotText' => $patient_info->doNotText,
                        'status' => $patient_info->status,
                        'notes' => $patient_info->notes,
                        'gender' => $patient_info->gender,
                        'sex' => $patient_info->sex,
                        'dateCreated' => !empty($patient_info->dateCreated) ? date('Y-m-d h:i:s', strtotime($patient_info->dateCreated)) : '',
                        'lastUpdated' => !empty($patient_info->lastUpdated) ? date('Y-m-d h:i:s', strtotime($patient_info->lastUpdated)) : '',
                        'nin' => $patient_info->nin
                    ];
                    $patient_data = array_merge($patient_data, $data);
                    if (!empty($patient_info->patientContact)) {
                        $patient_contact = $patient_info->patientContact;
                        $data = [
                            'firstName' => $patient_contact->firstName,
                            'middleName' => $patient_contact->middleName,
                            'lastName' => $patient_contact->lastName,
                            'address1' => $patient_contact->address1,
                            'address2' => $patient_contact->address2,
                            'email' => $patient_contact->email,
                            'homePhone' => $patient_contact->homePhone,
                            'cellPhone' => $patient_contact->cellPhone,
                            'workPhone' => $patient_contact->workPhone,
                            'state' => $patient_contact->state,
                            'city' => $patient_contact->city,
                            'postalCode' => $patient_contact->postalCode,
                            'countryCode' => $patient_contact->countryCode
                        ];
                        $patient_data = array_merge($patient_data, $data);
                    }
                }
                $response= ['status' => 1, 'message' => 'Data formatted successfully', 'patient_data' => $patient_data, 'studies' => $studies, 'procedures' => $procedures, 'emergency_contacts' => $emergency_contacts,'external_id'=>$external_id];
            }
        }
        return $response;
    }

    /**
     * Objective of the function is to map procedures
     * @param
     * @return
     */
    function mapProcedures($procedures_list){
        $procedures=[];
        foreach($procedures_list as $row){
            foreach($row['records'] as $procedure){
                    $procedure=(array)$procedure;
                    $procedures[$procedure['procedureKey']]['records'][]['questions'][]=array('questionKey'=>$procedure['questionKey'],'value'=>$procedure['value']);
            }
        }
        $final_procedure=array();
        foreach($procedures as $key=>$procedure){
            $final_procedure[]=["procedureKey"=>$key,"records"=>$procedure['records']];
        }
        return $final_procedure;
    }

    /**
     * Objective of the function is map the linear data provided into CRIO accepted fields
     * @param int $site_id id of the site of crio
     * @param array $patient_details containing patient data
     * @param int $patient_id id of the patient
     * @param int $revision revision number if required
     * @return array formatted array for CRIO
     */

    private function mapPatient($site_id, $patient_details, $studies=null, $procedures=null, $emergency_contacts=null, $patient_id = null, $revision = null )
    {
        $patient_information = ['patientId', 'externalId', 'birthDate', 'doNotCall', 'doNotEmail', 'doNotText', 'status', 'notes', 'gender', 'sex', 'dateCreated', 'lastUpdated', 'nin'];
        $patient_contact_information = ['firstName', 'middleName', 'lastName', 'address1', 'address2', 'email', 'homePhone', 'cellPhone', 'workPhone', 'state', 'city', 'postalCode', 'countryCode'];
        $patient = ['patientContact' => array()];
        foreach ($patient_information as $pi) {
            if (array_key_exists($pi, $patient_details) && !empty($patient_details[$pi])) {
                $patient[$pi] = $patient_details[$pi];
            }
        }
        foreach ($patient_contact_information as $pci) {
            if (array_key_exists($pci, $patient_details) && !empty($patient_details[$pci])) {
                $patient['patientContact'][$pci] = $patient_details[$pci];
            }
        }
        if (!empty($patient_id)) {
            $patient['patientId'] = $patient_id;
        }
        if(!empty($procedures)){
            $patient['procedures']=$procedures;
        }
        if(!empty($emergency_contacts)){
            $patient['emergencyContacts']=$emergency_contacts;
        }
        $final_data = [
            'revision' => $revision,
            'siteId' => $site_id,
            'patientInfo' => $patient
        ];
        if(!empty($studies)){
            $final_data['studies']=$studies;
        }
        return $final_data;
    }
}
