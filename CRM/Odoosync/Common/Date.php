<?php

class CRM_Odoosync_Common_Date {

  /**
   * Converts date into timestamp
   * In default use MySQL date format('Y-m-d H:i:s')
   *
   * @param $mysqlDate
   * @param string $inputDateFormat
   *
   * @return int
   */
  public static function convertDateToTimestamp($mysqlDate, $inputDateFormat = 'Y-m-d H:i:s') {
    $date = DateTime::createFromFormat($inputDateFormat, $mysqlDate);

    return ($date) ? $date->getTimestamp() : 0;
  }

}
