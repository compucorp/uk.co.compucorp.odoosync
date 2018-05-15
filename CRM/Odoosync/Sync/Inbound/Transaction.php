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
  private $syncResponse = [
    'is_error' => 0,
    'error_message' => ''
  ];

  /**
   * Starts transaction sync from Odoo
   *
   */
  public function run() {
    $inboundData = trim(file_get_contents('php://input'));
    $inboundXmlObject = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($inboundData);

    if (!$inboundXmlObject) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Can't parse a XML request.");
      $this->returnResponse();
      return;
    }

    $params = $this->parseInbound($inboundXmlObject);
    $validatedParams = $this->validateParams($params);

    if (!$validatedParams) {
      $this->returnResponse();
      return;
    }

    $this->syncTransactions($validatedParams);
    $this->returnResponse();
  }

  /**
   * Parses xml object
   *
   * @param \SimpleXMLElement $response
   *
   * @return array
   */
  private function parseInbound($response) {
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
   * @return array|bool
   */
  private function validateParams($params) {
    $fields = ["total_amount", "trxn_date", "invoice_id", "currency", "to_financial_account_name"];
    $validParam = [];

    foreach ($fields as $fieldName) {
      if (isset($params[$fieldName])) {
        $validParam[$fieldName] = trim($params[$fieldName]);
      }
      else {
        $this->syncResponse['is_error'] = 1;
        $this->syncResponse['error_message'] = ts("%1 is required field", [1 => $fieldName]);
        return FALSE;
      }
    }

    $contributionId = $validParam['invoice_id'];
    $toFinancialAccountName = $validParam['to_financial_account_name'];
    $toFinancialAccountId = $this->getFinancialAccountId($toFinancialAccountName);

    if (!$this->isContributionExist($contributionId)) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Contribution(id = %1) doesn't exist.", [1 => $contributionId]);
      return FALSE;
    }

    if (!$toFinancialAccountId) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Financial account('%1') doesn't exist.", [1 => $toFinancialAccountName]);
      return FALSE;
    }

    return [
      'to_financial_account_id' => $toFinancialAccountId,
      'total_amount' => $validParam['total_amount'],
      'trxn_date' => CRM_Odoosync_Common_Date::convertTimestampToDate($validParam['trxn_date']),
      'currency' => $validParam['currency'],
      'contribution_id' => $contributionId,
      'status_id' => "Completed",
      'payment_instrument_id' => "Cash"
    ];
  }

  /**
   * Gets financial account id
   *
   * @param string $financialAccountName
   *
   * @return array|bool
   */
  private function getFinancialAccountId($financialAccountName) {
    try {
      $financialAccount = civicrm_api3('FinancialAccount', 'getvalue', [
        'return' => "id",
        'name' => $financialAccountName,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    return $financialAccount;
  }

  /**
   * Checks whether there is a contribution
   *
   * @param $contributionId
   *
   * @return bool
   */
  public function isContributionExist($contributionId) {
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', [
        'return' => "id",
        'id' => $contributionId
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Creates transaction
   *
   * @param array $params
   */
  private function syncTransactions($params) {
    $financialTrxnId = $this->createFinancialTrxn($params);

    if (!$financialTrxnId ) {
      return;
    }

    $connectTransaction = $this->connectTransactionToContribution(
      $financialTrxnId,
      $params['contribution_id'],
      $params['total_amount']
    );

    $this->syncResponse['transaction_id'] = $financialTrxnId;
    $this->syncResponse['timestamp'] = time();
  }

  /**
   * Creates financial transaction
   *
   * @param $params
   *
   * @return bool|int
   */
  private function createFinancialTrxn($params) {
    try {
      $financialTrxn = civicrm_api3('FinancialTrxn', 'create', $params);
      return (int) $financialTrxn["id"];
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Can't create financial transaction");
      return FALSE;
    }
  }

  /**
   * Creates connect transaction to contribution
   *
   * @param $financialTrxnId
   * @param $contributionId
   * @param $amount
   *
   * @return bool
   */
  private function connectTransactionToContribution($financialTrxnId, $contributionId, $amount) {
    try {
      $entityFinancialTrxn = civicrm_api3('EntityFinancialTrxn', 'create', [
        'entity_table' => "civicrm_contribution",
        'entity_id' => (int)$contributionId,
        'financial_trxn_id' => (int)$financialTrxnId,
        'amount' => (int) $amount
      ]);
      return true;
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Can't create connect transaction to contribution");
      return FALSE;
    }
  }

  /**
   * Outputs XML response
   */
  private function returnResponse() {
    $xml = new SimpleXMLElement('<ResultSet/>');
    $result = $xml->addChild('Result');

    foreach ($this->syncResponse as $name => $value) {
      $result->addChild($name, $value);
    }
    echo $xml->asXML();
    CRM_Utils_System::civiExit();
  }

}
