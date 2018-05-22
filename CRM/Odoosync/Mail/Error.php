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
  private $recipientsEmails = [];

  /**
   * Sender email name
   *
   * @var string
   */
  private $senderEmailName;

  /**
   * Sender email address
   *
   * @var string
   */
  private $senderEmailAddress;

  /**
   * List of messages data
   *
   * @var string
   */
  private static $syncErrorMessageList = [];

  /**
   * CRM_Odoosync_Mail_Error constructor.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function __construct() {
    $this->setRecipientsEmails();
    $senderEmail = CRM_Core_BAO_Domain::getNameAndEmail();
    $this->setSenderEmailName($senderEmail[0]);
    $this->setSenderEmailAddress($senderEmail[1]);
  }

  /**
   * Sets emails from Odoo settings
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setRecipientsEmails() {
    $emailFromSetting = civicrm_api3('setting', 'getsingle',
      ['return' => ['odoosync_error_notice_address']]
    );

    if (!empty($emailFromSetting['odoosync_error_notice_address'])) {
      $this->recipientsEmails = explode(',', $emailFromSetting['odoosync_error_notice_address']);
    }
  }

  /**
   * Sets the sender's configured name or defines the default one
   *
   * @param string $senderEmailName
   */
  private function setSenderEmailName($senderEmailName) {
    if (empty($senderEmailName)) {
      $this->senderEmailName = self::DEFAULT_DOMAIN_EMAIL_NAME;
    }
    else {
      $this->senderEmailName = $senderEmailName;
    }
  }

  /**
   * Sets the sender's configured address or defines the default one
   *
   * @param string $senderEmailAddress
   */
  private function setSenderEmailAddress($senderEmailAddress) {
    if (empty($senderEmailAddress)) {
      $this->senderEmailAddress = "";
    }
    else {
      $this->senderEmailAddress = $senderEmailAddress;
    }
  }

  /**
   * Sends sync error message email to the recipient
   */
  public function sendToRecipients() {
    if (empty(self::$syncErrorMessageList)) {
      return $this->log;
    }

    $this->setLog(ts("Found %1 recipients' emails.", [ 1 => count($this->recipientsEmails)]));
    foreach ($this->recipientsEmails as $email) {
      $this->sendEmail($email);
    }

    return $this->log;
  }

  /**
   * Sets log message
   *
   * @param mixed $log
   */
  private function setLog($log) {
    $this->log[] = $log;
  }

  /**
   * Sends sync error message email to the recipient
   *
   * @param $email
   */
  private function sendEmail($email) {
    $logMessages = ts('Sending to the %1 ... ', [ 1 => $email]);
    $this->setLog(ts("Message list:"));
    $this->setLog(self::$syncErrorMessageList);

    try {
      civicrm_api3('MessageTemplate', 'send', [
        'option_group_name' => 'msg_tpl_workflow_odoo_sync',
        'option_value_name' => 'civicrm_odoo_sync_error_report',
        'template_params' => ['syncErrorMessageList' => self::$syncErrorMessageList],
        'from' => $this->senderEmailName . " <" . $this->senderEmailAddress . ">",
        'to_email' => $email,
      ]);

      $logMessages .= ts('Success. Email was sent.');
    }
    catch (CiviCRM_API3_Exception $e) {
      $logMessages .= ts('Email was not sent. Error:');
      $logMessages .= ts($e->getMessage());
    }

    $this->setLog($logMessages);
  }

  /**
   * Collects messages
   *
   * @param $entityType
   * @param $entityId
   * @param $errorLog
   */
  public static function collectMessage($entityType, $entityId, $errorLog) {
    self::$syncErrorMessageList[] = [
      'entityType' => $entityType,
      'entityId' => $entityId,
      'errorLog' => $errorLog
    ];
  }

}
