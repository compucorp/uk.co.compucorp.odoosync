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
   * Schedule job log
   *
   * @var array
   */
  protected $jobLog = '';

  /**
   * Handler is error
   *
   * @var bool
   */
  protected $isError = FALSE;

  /**
   * CRM_Odoosync_Sync_Handler constructor.
   *
   * @param array $params
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
    $data = [];
    $data['values'] = $this->jobLog;
    $data['is_error'] = 0;

    if ($this->isDebug) {
      $data['debugLog'] = $this->log;
    }

    return $data;
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

  /**
   * Sets to Schedule log
   *
   * @param string $jobLog
   */
  public function setJobLog($jobLog) {
    $this->jobLog .= $jobLog . '<br/>';
  }

  /**
   * Sets to log
   *
   * @param mixed $log
   */
  protected function setLog($log) {
    $this->log[] = $log;
  }

}
