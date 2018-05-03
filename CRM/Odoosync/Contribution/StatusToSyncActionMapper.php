<?php

/**
 * Mapping contribution statuses to sync actions
 */
class CRM_Odoosync_Contribution_StatusToSyncActionMapper {

  /**
   * Gets 'action to sync' by contribution id
   *
   * @param int $contributionId
   *
   * @return string
   */
  public static function getActionByContributionId($contributionId) {
    $contributionStatusId = self::getContributionStatusId($contributionId);
    $contributionStatusName = self::getContributionStatus($contributionStatusId);

    return self::retrieveAction($contributionStatusName);
  }

  /**
   * Gets 'action to sync' by contribution status id
   *
   * @param int $contributionStatusId
   *
   * @return string
   */
  public static function getActionByContributionStatusId($contributionStatusId) {
    $contributionStatusName = self::getContributionStatus($contributionStatusId);

    return self::retrieveAction($contributionStatusName);
  }

  /**
   * Gets 'contribution status id' by contribution id
   *
   * @param int $contributionId
   *
   * @return int
   */
  private static function getContributionStatusId($contributionId) {
    $statusId = civicrm_api3('Contribution', 'getvalue', [
      'return' => "contribution_status_id",
      'id' => $contributionId,
    ]);

    return (int) $statusId;
  }

  /**
   * Gets 'contribution status name' by contribution status id
   *
   * @param $statusId
   *
   * @return string
   */
  private static function getContributionStatus($statusId) {
    $optionList = civicrm_api3('Contribution', 'getoptions', [
      'field' => "contribution_status_id"
    ]);

    if (empty($optionList['values'])) {
      return '';
    }

    return (isset($optionList['values'][$statusId])) ? $optionList['values'][$statusId] : '';
  }

  /**
   * Gets 'action to sync' by contribution status name
   *
   * @param string $statusName
   *
   * @return string
   */
  private static function retrieveAction($statusName) {
    switch ($statusName) {
      case "Completed":
        $actionName = "completed";
        break;

      case "Pending":
        $actionName = "create";
        break;

      case "Cancelled":
        $actionName = "cancelled";
        break;

      case "Failed":
        $actionName = "failed";
        break;

      case "In Progress":
        $actionName = "partially_paid";
        break;

      case "Refunded":
        $actionName = "refunded";
        break;

      case "Partially paid":
        $actionName = "partially_paid";
        break;

      default:
        $actionName = "other";
    }

    return $actionName;
  }

}
