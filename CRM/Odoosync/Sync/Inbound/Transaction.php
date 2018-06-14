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
   * Validated params
   *
   * @var array
   */
  private $validatedParams = [];

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
      "total_amount",
      "trxn_date",
      "invoice_id",
      "currency",
      "credit_account_code",
      "contribution_status",
      "to_financial_account_name"
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

    $contributionId = $validParam['invoice_id'];
    $toFinancialAccountName = $validParam['to_financial_account_name'];
    $creditAccountCode = $validParam['credit_account_code'];
    $toFinancialAccountId = $this->getFinancialAccountIdByName($toFinancialAccountName);
    $fromFinancialAccountId = $this->getFinancialAccountIdByCode($creditAccountCode);
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

    if (!$fromFinancialAccountId) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Financial account (with 'accounting code' = '%1') doesn't exist.", [1 => $fromFinancialAccountId]);
    }

    if (!$contributionStatusId) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Contribution status '%1' doesn't exist.", [1 => $validParam['contribution_status']]);
    }

    if ($this->syncResponse['is_error'] === 1) {
      return;
    }

    $this->validatedParams =  [
      'to_financial_account_id' => $fromFinancialAccountId,
      'from_financial_account_id' => $toFinancialAccountId,
      'total_amount' => $validParam['total_amount'],
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
    $financialTrxnId = $this->createFinancialTrxn();

    if (!$financialTrxnId ) {
      $this->syncResponse['is_error'] = 1;
      $this->syncResponse['error_message'] .= ts("Can't create financial transaction.");
      return;
    }

    $this->createEntityFinancialTrxn($financialTrxnId);
    $this->updateContributionStatus();

    $this->syncResponse['transaction_id'] = $financialTrxnId;
    $this->syncResponse['timestamp'] = time();
  }

  /**
   * Creates connect transaction to 'financial item'
   *
   * @param $financialTrxnId
   *
   * @return bool
   */
  private function createEntityFinancialTrxn($financialTrxnId) {
    $financialItemId = $this->getFinancialItemId();
    if (!$financialItemId) {
      return FALSE;
    }

    try {
      $entityFinancialTrxn = civicrm_api3('EntityFinancialTrxn', 'create', [
        'entity_table' => 'civicrm_financial_item',
        'entity_id' => $financialItemId,
        'financial_trxn_id' => $financialTrxnId,
        'amount' =>  $this->validatedParams['total_amount']
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
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$this->validatedParams['contribution_id'], 'Integer']]);

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
  private function createFinancialTrxn() {
    try {
      $params = array_merge($this->validatedParams, [
        'is_payment' => 1,
        'net_amount' => $this->validatedParams['total_amount']
      ]);
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

    if ($currentContributionStatus == $this->validatedParams['contribution_status_id']) {
      return;
    }

    $query = "
      UPDATE civicrm_contribution AS contribution
      SET contribution.contribution_status_id = %2
      WHERE contribution.id = %1
    ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->validatedParams['contribution_id'], 'Integer'],
      2 => [$this->validatedParams['contribution_status_id'], 'String'],
    ]);
  }

  /**
   * Gets current contribution status id
   */
  private function getCurrentContributionStatus() {
    return civicrm_api3('Contribution', 'getvalue', [
      'return' => "contribution_status_id",
      'id' => $this->validatedParams['contribution_id']
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
