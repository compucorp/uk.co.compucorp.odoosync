<?php

/**
 * Provides syncing contribution with Odoo
 */
class CRM_Odoosync_Sync_Request_Contribution {

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
  private $syncContributionResponse = [
    'is_error' => 0,
    'error_message' => '',
    'invoice_number' => NULL,
    'creditnote_number' => NULL,
    'timestamp' => NULL
  ];

  /**
   * Odoo sync url
   *
   * @var string
   */
  const ODOO_API_ENDPOINT = '/xmlrpc/2/object';

  public function __construct() {
    if (CRM_Odoosync_Sync_Request_Auth::getInstance()->odooUserId === FALSE) {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= ts("Can't login to Odoo.");
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
      return $this->syncContributionResponse;
    }

    $url = $this->setting['odoosync_odoo_instance_url'] . self::ODOO_API_ENDPOINT;
    $xml = CRM_Odoosync_Sync_Request_XmlGenerator::generateContributionSyncOdooXml(
      $this->setting['odoosync_database_name'],
      $this->setting['odoosync_password'],
      'execute_kw',
      CRM_Odoosync_Sync_Request_Auth::getInstance()->odooUserId,
      $sendData
    );

    $responseXml = (new CRM_Odoosync_Sync_Request_Curl)->sendXml($url, $xml);
    $response = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($responseXml);
    if (!$response) {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= ts("Can't parse response xml.");
      return $this->syncContributionResponse;
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

    if (isset($response->params->param->value->struct->member)) {
      foreach ($response->params->param->value->struct->member as $param) {
        if ((string) $param->name == 'error_log') {
          $parsedData[(string) $param->name] = $this->parseErrorLogMessage($param->value);
          continue;
        }

        if ((string) $param->name == 'invoice_number' || (string) $param->name ==  'creditnote_number') {
          $parsedData[(string) $param->name] = (string) $param->value->string;
          continue;
        }

        if (
          (string) $param->name == 'timestamp'
          || (string) $param->name == 'is_error'
          || (string) $param->name == 'contribution_id'
        ) {
          $parsedData[(string) $param->name] = (string) $param->value->int;
        }
      }
    }
    else {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= ts("Can't parse sync response.");
    }

    if (empty($response)) {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= ts("Can't parse sync response.");
    }

    if (isset($parsedData['is_error']) && $parsedData['is_error'] == 1) {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= $parsedData['error_log'];
    }

    if (!empty($response)) {
      if (isset($parsedData['invoice_number'])) {
        $this->syncContributionResponse['invoice_number'] = $parsedData['invoice_number'];
      }

      if (isset($parsedData['timestamp'])) {
        $this->syncContributionResponse['timestamp'] = (int) $parsedData['timestamp'];
      }

      if (isset($parsedData['creditnote_number'])) {
        $this->syncContributionResponse['creditnote_number'] = $parsedData['creditnote_number'];
      }
    }
    else {
      $this->syncContributionResponse['is_error'] = 1;
      $this->syncContributionResponse['error_message'] .= ts('Empty response.');
    }

    return $this->syncContributionResponse;
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
