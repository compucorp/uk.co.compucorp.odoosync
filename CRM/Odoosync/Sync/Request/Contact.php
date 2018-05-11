<?php

/**
 * Provides syncing contact with Odoo
 */
class CRM_Odoosync_Sync_Request_Contact {

  /**
   * Setting for Odoo sync
   *
   * @var array
   */
  private $setting;

  /**
   * List of the Odoo response fields to be generated
   *
   * @var array
   */
  private $syncContactResponse = [
    'is_error' => 0,
    'error_message' => '',
    'partner_id' => '',
    'timestamp' => '',
    'contact_id' => ''
  ];

  /**
   * Odoo sync url
   *
   * @var string
   */
  const ODOO_API_ENDPOINT = '/xmlrpc/2/object';

  public function __construct() {
    if (CRM_Odoosync_Sync_Request_Auth::getInstance()->odooUserId === FALSE) {
      $this->syncContactResponse['is_error'] = 1;
      $this->syncContactResponse['error_message'] .= ts("Can't login to Odoo.");
      return;
    }
    $syncSetting = CRM_Odoosync_Setting::getInstance();
    $this->setting = $syncSetting->retrieve();
  }

  /**
   * Syncs with Odoo contact
   * Generates response
   *
   * @param array $sendData
   *
   * @return mixed
   */
  public function sync($sendData) {
    if (CRM_Odoosync_Sync_Request_Auth::getInstance()->odooUserId === FALSE) {
      return $this->syncContactResponse;
    }

    $url = $this->setting['odoosync_odoo_instance_url'] . self::ODOO_API_ENDPOINT;
    $xml = CRM_Odoosync_Sync_Request_XmlGenerator::generateContactSyncOdooXml(
      $this->setting['odoosync_database_name'],
      $this->setting['odoosync_password'],
      'execute_kw',
      CRM_Odoosync_Sync_Request_Auth::getInstance()->odooUserId,
      $sendData
    );

    $responseXml = (new CRM_Odoosync_Sync_Request_Curl)->sendXml($url, $xml);
    $response = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($responseXml);
    if (!$response) {
      $this->syncContactResponse['is_error'] = 1;
      $this->syncContactResponse['error_message'] .= ts("Can't parse response xml.");
      return $this->syncContactResponse;
    }

    return $this->parseResponse($response);
  }

  /**
   * Parses xml object
   *
   * @param \SimpleXMLElement $response
   *
   * @return array
   */
  private function parseResponse($response) {
    $parsedData = [];
    foreach ($response->params->param->value->struct->member as $param) {
      if ((string) $param->name == 'error_log') {
        $parsedData[(string) $param->name] = $this->parseErrorLogMessage($param->value);
        continue;
      }

      $parsedData[(string) $param->name] = (string) $param->value->int;
    }

    if (empty($response)) {
      $this->syncContactResponse['is_error'] = 1;
      $this->syncContactResponse['error_message'] .= ts("Can't parse sync response.");
    }

    if ($parsedData['is_error'] == 1) {
      $this->syncContactResponse['is_error'] = 1;
      $this->syncContactResponse['error_message'] .= $parsedData['error_log'];
    }

    if (!empty($response)) {
      $this->syncContactResponse['partner_id'] = $parsedData['partner_id'];
      $this->syncContactResponse['timestamp'] = $parsedData['timestamp'];
      $this->syncContactResponse['contact_id'] = $parsedData['contact_id'];
    }
    else {
      $this->syncContactResponse['is_error'] = 1;
      $this->syncContactResponse['error_message'] .= ts('Empty response.');
    }

    return $this->syncContactResponse;
  }

  /**
   * Parses "error_log" xml object
   *
   * @param \SimpleXMLElement $value
   *
   * @return string
   */
  private function parseErrorLogMessage($value) {
    $messages = [];
    foreach ($value->array->data->value as $message) {
      $messages[] = (string) $message->string;
    }

    return implode('; ', $messages);
  }

}
