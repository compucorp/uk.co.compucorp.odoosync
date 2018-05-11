<?php

/**
 * Handles syncing transaction data from Odoo
 */
class CRM_Odoosync_Sync_Inbound_Transaction {

  /**
   * List of the CiviCRM response fields to Odoo
   *
   * @var array
   */
  private $syncResponse = ['is_error' => 0];

  /**
   * Starts transaction sync from Odoo
   *
   */
  public function run() {
    $inboundData = trim(file_get_contents('php://input'));
    $response = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($inboundData);
    $params = [];

    if (!$response) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts('Can\'t parse a XML response.');
    }
    else {
      $params = $this->parseResponse($response);
      $this->validateParams($params);
    }

    if (!$this->syncResponse['is_error']) {
      $this->syncTransactions($params);
    }

    $this->returnResponse();
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

    if (isset($response->params->param->value->struct->financial_trxn)) {
      foreach ($response->params->param->value->struct->financial_trxn as $param) {
        if (!empty($param->name) && !empty($param->value)) {
          $parsedData[(string) $param->name] = (string) $param->value;
        }
      }
    }

    if (empty($parsedData)) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts('Response is empty.');
    }

    return $parsedData;
  }

  /**
   * Validates transaction params
   *
   * @param array $params
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  private function validateParams(&$params) {
    $requiredParameters = [
      'to_financial_account_id' => 'String',
      'total_amount' => 'String',
      'trxn_date' => 'String',
      'currency' => 'String',
    ];

    foreach ($requiredParameters as $param => $type) {
      $params[$param] = CRM_Utils_Type::validate(CRM_Utils_Array::value($param, $params), $type);
    }
    $params['to_financial_account_id'] = civicrm_api3('FinancialAccount', 'getvalue', [
      'return' => "id",
      'name' => $params['to_financial_account_id'],
    ]);

    if (empty($params['to_financial_account_id'])) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts('Can not find a Financial Account');
    }
  }

  /**
   * Creates transaction
   *
   * @param array $params
   */
  protected function syncTransactions($params) {
    $transaction = CRM_Core_BAO_FinancialTrxn::create($params);

    if (!$transaction) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts('Can not create a transaction');
    }
    else {
      $this->syncResponse['transaction_id'] = $transaction->id;
      $this->syncResponse['timestamp'] = time();
    }
  }

  /**
   * Outputs XML response
   */
  protected function returnResponse() {
    $xml = new SimpleXMLElement('<ResultSet/>');
    $result = $xml->addChild('Result');

    foreach ($this->syncResponse as $name => $value) {
      $result->addChild($name, $value);
    }
    echo $xml->asXML();
    CRM_Utils_System::civiExit();
  }

}
