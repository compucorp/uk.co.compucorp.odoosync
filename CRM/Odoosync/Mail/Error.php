<?php

/**
 * This class sends error messages to emails from Odoo settings
 */
class CRM_Odoosync_Mail_Error {

  /**
   * Default domain email name
   *
   * @var array
   */
  const DEFAULT_DOMAIN_EMAIL_NAME = "CiviCRM";

  /**
   * Log messages
   *
   * @var array
   */
  private $log = [];

  /**
   * Recipients' emails use for send error messages
   *
   * @var array
   */
  private $emails = [];

  /**
   * Error message
   *
   * @var string
   */
  private $errorMessage;

  /**
   * Entity type
   *
   * @var string
   */
  private $entityType;

  /**
   * Entity id
   *
   * @var string
   */
  private $entityId;

  /**
   * Domain email name
   *
   * @var string
   */
  private $domainEmailName;

  /**
   * Domain email address
   *
   * @var string
   */
  private $domainEmailAddress;

  /**
   * CRM_Odoosync_Mail_Error constructor.
   *
   * @param $errorMessage
   *
   * @param $entityType
   *
   * @param $entityId
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function __construct($errorMessage, $entityType, $entityId) {
    $this->setEntityId($entityId);
    $this->setEntityType($entityType);
    $this->setErrorMessage($errorMessage);
    $this->setEmails();
    $this->setLog(ts("Found %1 recipients' emails.", [ 1 => count($this->emails)]));

    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
    $this->setDomainEmailAddress($domainEmailAddress);
    $this->setDomainEmailName($domainEmailName);
  }

  /**
   * Sends sync error message email to the recipient
   */
  public function sendErrorMessage(){
    foreach ($this->emails as $email) {
      $this->sendToEmail($email);
    }
  }

  /**
   * Sends sync error message email to the recipient
   *
   * @param $email
   */
  private function sendToEmail($email) {
    $this->setLog(ts('Sending to the %1 ...', [ 1 => $email]));

    $param = [
      'groupName' => 'msg_tpl_workflow_odoo_sync',
      'valueName' => 'civicrm_odoo_sync_error_report',
      'tplParams' =>
        [
          'errorMessage' => $this->getErrorMessage(),
          'entityType' => $this->getEntityType(),
          'entityId' => $this->getEntityId()
        ],
      'from' => $this->getDomainEmailName() . " <" . $this->getDomainEmailAddress() . ">",
      'toEmail' => $email,
    ];

    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($param);

    if ($sent === TRUE) {
      $this->setLog(ts('Success. Email was sent.'));
    }
    else {
      $this->setLog(ts('Error. Email was not sent.'));
    }
  }

  /**
   * Sets emails from Odoo settings
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setEmails() {
    $emailFromSetting = civicrm_api3(
      'setting',
      'getsingle',
      ['return' => ['odoosync_error_notice_address']]
    );

    if (!empty($emailFromSetting['odoosync_error_notice_address'])) {
      $this->emails = explode(',', $emailFromSetting['odoosync_error_notice_address']);
    }
  }

  /**
   * Returns the log messages
   *
   * @return array
   */
  public function getReturnData() {
    return [
      'log' => $this->log
    ];
  }

  /**
   * @param mixed $log
   */
  public function setLog($log) {
    $this->log[] = $log;
  }

  /**
   * @param string $errorMessage
   */
  public function setErrorMessage($errorMessage) {
    if (empty($errorMessage)) {
      $this->errorMessage = '';
    }

    $this->errorMessage = $errorMessage;
  }

  /**
   * @return string
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * @param string $entityType
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * @return string
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * @param string $entityId
   */
  public function setEntityId($entityId) {
    $this->entityId = $entityId;
  }

  /**
   * @return string
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * @return string
   */
  public function getDomainEmailName() {
    return $this->domainEmailName;
  }

  /**
   * If exist sets domain email name or sets default value
   *
   * @param string $domainEmailName
   */
  public function setDomainEmailName($domainEmailName) {
    if (empty($domainEmailName)) {
      $this->domainEmailName = self::DEFAULT_DOMAIN_EMAIL_NAME;
    }
    else {
      $this->domainEmailName = $domainEmailName;
    }
  }

  /**
   * @return string
   */
  public function getDomainEmailAddress() {
    return $this->domainEmailAddress;
  }

  /**
   * If exist sets domain email address
   *
   * @param string $domainEmailAddress
   */
  public function setDomainEmailAddress($domainEmailAddress) {
    if (empty($domainEmailAddress)) {
      $this->domainEmailAddress = "";
    }
    else {
      $this->domainEmailAddress = $domainEmailAddress;
    }
  }

}
