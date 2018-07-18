<?php

/**
 * Main boot point for Odoo sync
 */
class CRM_Odoosync_Sync {

  /**
   * Runs Odoo sync
   *
   * @param $params
   *
   * @return array
   * @throws \Exception
   */
  public function run($params) {
    $log = [];
    $logContact = (new CRM_Odoosync_Sync_Contact($params))->run();
    $logContribution = (new CRM_Odoosync_Sync_Contribution($params))->run();
    $mailLog = (new CRM_Odoosync_Mail_Error())->sendToRecipients();

    $log['is_error'] = ($logContact['is_error'] == 1 || $logContact['is_error'] == 1) ? 1 : 0;

    //more detail log can view in api when debug = 1
    if ($params['debug'] == 1) {
      $log['debugLog']['contacts_debug_log'] = $logContact['debugLog'];
      $log['debugLog']['contribution_debug_log'] = $logContribution['debugLog'];
      $log['debugLog']['mail_debug_log'] = $mailLog;
    }

    //this log can view on schedule job
    $log['values'] = '<br/>' . $logContact['values'] . $logContribution['values'];
    $log['values'] .= !empty($mailLog) ? '<br/>' . $mailLog : '';

    return $log;
  }

}
