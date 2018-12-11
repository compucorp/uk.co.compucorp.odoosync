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

  private $validatedCommonParams = [];

  private $validatedTransactionParamsList = [];

  /**
   * Starts transaction sync from Odoo
   */
  public function run() {
    $inboundData = trim(file_get_contents('php://input'));
    $inboundXmlObject = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($inboundData);

    if (!$inboundXmlObject) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Can't parse a XML request.");
      $this->returnResponse();
      return;
    }

    $params = $this->parseInbound($inboundXmlObject);
    $this->validateParams($params);

    if ($this->syncResponse['is_error'] === 1) {
      $this->returnResponse();
      return;
    }

    $this->syncTransactions();
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
      $this->syncResponse['error_message'] .= ts("Can't find and parse expected data.");
    }

    return $parsedData;
  }

  /**
   * Validates and prepares transaction params
   *
   * @param array $params
   */
  private function validateParams($params) {
    $fields = [
      "trxn_date",
      "invoice_id",
      "currency",
      "contribution_status",
      "to_financial_account_name",
    ];

    $validParam = [];

    foreach ($fields as $fieldName) {
      if (isset($params[$fieldName])) {
        $validParam[$fieldName] = trim($params[$fieldName]);
      }
      else {
        $this->syncResponse['is_error'] = 1;
        $this->syncResponse['error_message'] .= ts("%1 is required field", [1 => $fieldName]);
        return;
      }
    }

    if (!empty($params['transactions'])) {
      $validParam['transactions'] = $params['transactions'];
    } else {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("transactions is required field");
      return;
    }

    foreach ($validParam['transactions'] as &$transaction) {
      $fields = [
        "credit_account_code",
        "total_amount",
      ];

      foreach ($fields as $fieldName) {
        if (isset($transaction[$fieldName])) {
          $transaction[$fieldName] = trim($transaction[$fieldName]);
        }
        else {
          $this->syncResponse['is_error'] = 1;
          $this->syncResponse['error_message'] .= ts("Transaction: %1 field is required", [1 => $fieldName]);
          return;
        }
      }
    }

    $contributionId = $validParam['invoice_id'];
    $toFinancialAccountName = $validParam['to_financial_account_name'];
    $toFinancialAccountId = $this->getFinancialAccountIdByName($toFinancialAccountName);
    $contributionStatusId = $this->getContributionStatusId($validParam['contribution_status']);

    if (!$this->isContributionExist($contributionId)) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Contribution(id = %1) doesn't exist.", [1 => $contributionId]);
      return;
    }

    if (!$toFinancialAccountId) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Financial account (with 'name' = '%1') doesn't exist.", [1 => $toFinancialAccountName]);
    }

    if (!$contributionStatusId) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Contribution status '%1' doesn't exist.", [1 => $validParam['contribution_status']]);
    }

    foreach ($validParam['transactions'] as $transaction) {
      $fromFinancialAccountId = $this->getFinancialAccountIdByCode($transaction['credit_account_code']);

      if (!$fromFinancialAccountId) {
        $this->syncResponse['is_error'] = 1;
        $this->syncResponse['error_message'] .= ts("Financial account (with 'accounting code' = '%1') doesn't exist.", [1 => $fromFinancialAccountId]);
      }

      $this->validatedTransactionParamsList[] = [
        'from_financial_account_id' => $fromFinancialAccountId,
        'total_amount' => $transaction['total_amount'],
      ];
    }

    if ($this->syncResponse['is_error'] === 1) {
      return;
    }

    $this->validatedCommonParams =  [
      'to_financial_account_id' => $toFinancialAccountId,
      'trxn_date' => CRM_Odoosync_Common_Date::convertTimestampToDate($validParam['trxn_date']),
      'currency' => $validParam['currency'],
      'contribution_id' => $contributionId,
      'status_id' => "Completed",
      'payment_instrument_id' => "Cash",
      'contribution_status_id' => $contributionStatusId
    ];
  }

  /**
   * Gets financial account id by 'name'
   *
   * @param string $financialAccountName
   *
   * @return array|bool
   */
  private function getFinancialAccountIdByName($financialAccountName) {
    try {
      $financialAccount = civicrm_api3('FinancialAccount', 'getvalue', [
        'return' => "id",
        'name' => $financialAccountName,
      ]);

      return $financialAccount;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets financial account id by 'accounting_code'
   *
   * @param string $financialAccountCode
   *
   * @return array|bool
   */
  private function getFinancialAccountIdByCode($financialAccountCode) {
    try {
      $financialAccount = civicrm_api3('FinancialAccount', 'getvalue', [
        'return' => "id",
        'accounting_code' => $financialAccountCode,
      ]);

      return $financialAccount;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets 'contribution status id' by 'contribution status name'
   *
   * @param $contributionStatusName
   *
   * @return bool|int
   */
  private function getContributionStatusId($contributionStatusName) {
    try {
      $optionValue = civicrm_api3('OptionValue', 'getSingle', [
        'sequential' => 1,
        'options' => ['limit' => 1],
        'return' => ["value"],
        'option_group_id' => 'contribution_status',
        'name' => $contributionStatusName
      ]);

      return $optionValue['value'];
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks whether there is a contribution
   *
   * @param $contributionId
   *
   * @return bool
   */
  private function isContributionExist($contributionId) {
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', [
        'return' => "id",
        'id' => $contributionId
      ]);

      return TRUE;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Creates transaction
   */
  private function syncTransactions() {
    foreach ($this->validatedTransactionParamsList as $transactionParams) {
      $financialTrxnId = $this->createFinancialTrxn($transactionParams);

      if (!$financialTrxnId) {
        $this->syncResponse['is_error'] = 1;
        $this->syncResponse['error_message'] .= ts("Can't create financial transaction.");
        return;
      }

      $this->createEntityFinancialTrxn($financialTrxnId, $transactionParams['total_amount']);

      $this->syncResponse[] = [
        'transaction_id' => $financialTrxnId,
        'timestamp' =>  time(),
      ];
    }

    $this->updateContributionStatus();
  }

  /**
   * Creates connect transaction to 'financial item'
   *
   * @param $financialTrxnId
   *
   * @return bool
   */
  private function createEntityFinancialTrxn($financialTrxnId, $amount) {
    $financialItemId = $this->getFinancialItemId();
    if (!$financialItemId) {
      return FALSE;
    }

    try {
      $entityFinancialTrxn = civicrm_api3('EntityFinancialTrxn', 'create', [
        'entity_table' => 'civicrm_financial_item',
        'entity_id' => $financialItemId,
        'financial_trxn_id' => $financialTrxnId,
        'amount' =>  $amount
      ]);

      return (int) $entityFinancialTrxn['id'];
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] = ts("Can't create connect 'financial item' to transaction");
      return FALSE;
    }
  }

  /**
   * Gets financial item id
   *
   * @return bool|int
   */
  public function getFinancialItemId() {
    $query = "
      SELECT financial_item.id AS financial_item_id
      FROM civicrm_line_item AS line_item
      LEFT JOIN civicrm_financial_item AS financial_item
        ON line_item.id = financial_item.entity_id
      WHERE line_item.contribution_id = %1 
        AND line_item.entity_table = 'civicrm_contribution'
        AND financial_item.entity_table = 'civicrm_line_item'
	  ";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$this->validatedCommonParams['contribution_id'], 'Integer']]);

    while ($dao->fetch()) {
      return (int) $dao->financial_item_id;
    }

    return FALSE;
  }


  /**
   * Creates financial transaction
   *
   * @return bool|int
   */
  private function createFinancialTrxn($transactionParams) {
    try {
      $params = array_merge($this->validatedCommonParams, [
        'is_payment' => 1,
        'net_amount' => $transactionParams['total_amount']
      ], $transactionParams);
      $financialTrxn = civicrm_api3('FinancialTrxn', 'create', $params);

      return (int) $financialTrxn["id"];
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Updates contribution status
   */
  private function updateContributionStatus() {
    $currentContributionStatus = $this->getCurrentContributionStatus();

    if ($currentContributionStatus == $this->validatedCommonParams['contribution_status_id']) {
      return;
    }

    $query = "
      UPDATE civicrm_contribution AS contribution
      SET contribution.contribution_status_id = %2
      WHERE contribution.id = %1
    ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->validatedCommonParams['contribution_id'], 'Integer'],
      2 => [$this->validatedCommonParams['contribution_status_id'], 'String'],
    ]);
  }

  /**
   * Gets current contribution status id
   */
  private function getCurrentContributionStatus() {
    return civicrm_api3('Contribution', 'getvalue', [
      'return' => "contribution_status_id",
      'id' => $this->validatedCommonParams['contribution_id']
    ]);
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
