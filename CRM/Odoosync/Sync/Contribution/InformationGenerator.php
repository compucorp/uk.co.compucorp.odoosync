<?php

class CRM_Odoosync_Sync_Contribution_InformationGenerator {

  /**
   * Sync contribution id
   *
   * @var int
   */
  private $contributionId;

  /**
   * Contribution fields
   *
   * @var array
   */
  private $fieldsToGenerate = [];

  /**
   * CRM_Odoosync_Sync_Contribution_InformationGenerator constructor.
   *
   * @param int $contributionId
   */
  public function __construct($contributionId) {
    $this->contributionId = $contributionId;
  }

  /**
   * Prepares contribution data for sync
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function generate() {
    $lineItems = (new CRM_Odoosync_Sync_Contribution_Data_LineItem($this->contributionId))->retrieve();
    $contributionParams = (new CRM_Odoosync_Sync_Contribution_Data_ContributionParam($this->contributionId))->retrieve();
    $paymentList = (new CRM_Odoosync_Sync_Contribution_Data_Payment($this->contributionId))->retrieve();
    $refundList = (new CRM_Odoosync_Sync_Contribution_Data_Refund($this->contributionId))->retrieve();

    $this->fieldsToGenerate = [
      'lineItems' => $lineItems,
      'contributionParams' => $contributionParams,
      'paymentList' => $paymentList,
      'refundList' => $refundList,
    ];

    return $this->fieldsToGenerate;
  }

}
