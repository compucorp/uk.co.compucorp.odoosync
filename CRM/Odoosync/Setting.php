<?php

class CRM_Odoosync_Setting {

  /**
   * Instance for singleton
   *
   * @var object
   */
  private static $instance;

  /**
   * Setting for Odoo sync
   *
   * @var array
   */
  private $setting;

  /**
   * Required setting fields for the extension
   */
  private $requiredSettingFields = [
    'odoosync_odoo_instance_url',
    'odoosync_database_name',
    'odoosync_username',
    'odoosync_password',
    'odoosync_batch_size',
    'odoosync_retry_threshold',
    'odoosync_error_notice_address',
  ];

  /**
   * CRM_Odoosync_Setting constructor.
   *
   * @throws \Exception
   */
  private function __construct() {
    $this->setSettings();
    $this->validateSetting();
  }

  /**
   * Gets singleton instance
   *
   * @return \CRM_Odoosync_Setting|object
   */
  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Sets settings values
   */
  private function setSettings() {
    $domainID = CRM_Core_Config::domainID();
    try {
      $currentValues = civicrm_api3('setting', 'get', ['return' => $this->requiredSettingFields]);
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->setting = [];
      return;
    }

    $setting = [];
    foreach ($currentValues['values'][$domainID] as $name => $value) {
      $setting[$name] = $value;
    }

    $this->setting = $setting;
  }

  /**
   * Validates for all required setting parameters
   *
   * @throws \Exception
   */
  private function validateSetting() {
    $errorMessages = '';

    foreach ($this->requiredSettingFields as $name) {
      if (!isset($this->setting[$name]) || empty($this->setting[$name])) {
        $errorMessages .= ts("Setting '%1' is required. ", [1 => $name]);
      }
    }

    if (!empty($errorMessages)) {
      throw new Exception($errorMessages);
    }
  }

  /**
   * Retrieves valid settings values
   *
   * @return mixed
   */
  public function retrieve() {
    return $this->setting;
  }

  private function __clone() {}

  private function __wakeup() {}

}
