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
   * Error message
   *
   * @var string
   */
  private $message;

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
  public function __construct($entityId, $entityType, $errorMessage) {
    $this->entityId = $entityId;
    $this->entityType = $entityType;
    $this->setMessage($errorMessage);

    $this->setRecipientsEmails();
    $this->setLog(ts("Found %1 recipients' emails.", [ 1 => count($this->recipientsEmails)]));

    $senderEmail = CRM_Core_BAO_Domain::getNameAndEmail();
    $this->setSenderEmailName($senderEmail[0]);
    $this->setSenderEmailAddress($senderEmail[1]);
  }

  /**
   * Sends sync error message email to the recipient
   */
  public function sendToRecipients() {
    foreach ($this->recipientsEmails as $email) {
      $this->sendEmail($email);
    }

    return $this->log;
  }

  /**
   * Sends sync error message email to the recipient
   *
   * @param $email
   */
  private function sendEmail($email) {
    $logMessages = ts('Sending to the %1 ... ', [ 1 => $email]);

    try {
      civicrm_api3('MessageTemplate', 'send', [
        'option_group_name' => 'msg_tpl_workflow_odoo_sync',
        'option_value_name' => 'civicrm_odoo_sync_error_report',
        'template_params' => [
          'errorMessage' => $this->message,
          'entityType' => $this->entityType,
          'entityId' => $this->entityId,
        ],
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
   * Sets log message
   *
   * @param mixed $log
   */
  private function setLog($log) {
    $this->log[] = $log;
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
   * Sets error message
   *
   * @param string $message
   */
  private function setMessage($message) {
    if (empty($message)) {
      $this->message = '';
    }

    $this->message = $message;
  }

}
