<?php

/**
 * Abstraction class provides skeleton for calls to Odoo API, and provides log process
 */
abstract class CRM_Odoosync_Sync_Handler {

  /**
   * Sync setting
   *
   * @var array
   */
  protected $setting = [];

  /**
   * Is debug mode
   *
   * @var bool
   */
  protected $isDebug = FALSE;

  /**
   * Save to log
   *
   * @var array
   */
  protected $log = [];

  /**
   * Handler is error
   *
   * @var bool
   */
  protected $isError = FALSE;

  /**
   * CRM_Odoosync_Sync_Handler constructor.
   *
   * @param $params
   */
  public function __construct($params) {
    if (!empty($params['debug']) && $params['debug'] == 1) {
      $this->isDebug = TRUE;
    }

    $this->setSettings();
  }

  /**
   * Calls odoo sync if no errors
   *
   * @return array
   * @throws \Exception
   */
  public function run() {
    if ($this->isError) {
      return $this->getReturnData();
    }

    return $this->syncStart();
  }

  /**
   * Abstract method
   * Starts sync
   */
  abstract protected function syncStart();

  /**
   * Returns log messages if debug is enabled
   *
   * @return array
   */
  protected function getReturnData() {
    if ($this->isDebug) {
      return [
        'log' => $this->log
      ];
    }

    return [];
  }

  /**
   * Sets to log
   *
   * @param mixed $log
   */
  protected function setLog($log) {
    $this->log[] = $log;
  }

  /**
   * Sets setting values
   */
  protected function setSettings() {
    try {
      $setting = CRM_Odoosync_Sync_Setting::getInstance();
      $this->setting = $setting->retrieveSetting();
    }
    catch (Exception $e) {
      $this->setting = [];
      $this->isError = TRUE;
      $this->setLog($e->getMessage());
    }
  }

}
