<?php

/**
 * Mapping contribution statuses to sync actions
 */
class CRM_Odoosync_Contribution_StatusMapping {

  /**
   * Gets 'action to sync' by contribution id
   *
   * @param int $contributionId
   *
   * @return string
   */
  public function getActionByContributionId($contributionId) {
    $contributionStatusId = $this->getContributionStatusId($contributionId);
    $contributionStatusName = $this->getContributionStatus($contributionStatusId);
    return $this->retrieveAction($contributionStatusName);
  }

  /**
   * Gets 'action to sync' by contribution status id
   *
   * @param int $contributionStatusId
   *
   * @return string
   */
  public function getActionByContributionStatusId($contributionStatusId) {
    $contributionStatusName = $this->getContributionStatus($contributionStatusId);
    return $this->retrieveAction($contributionStatusName);
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
  private function getContributionStatus($statusId) {
    return CRM_Core_PseudoConstant::getName(
      'CRM_Contribute_BAO_Contribution',
      'contribution_status_id',
      $statusId
    );
  }

  /**
   * Gets 'action to sync' by contribution status name
   *
   * @param string $statusName
   *
   * @return string
   */
  private function retrieveAction($statusName) {
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

