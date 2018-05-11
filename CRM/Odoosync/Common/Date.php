<?php

class CRM_Odoosync_Common_Date {

  /**
   * MySQL format date
   *
   * @var string
   */
  const MYSQL_FORMAT_DATE = 'Y-m-d H:i:s';

  /**
   * Converts date into timestamp
   * In default use MySQL date format('Y-m-d H:i:s')
   *
   * @param $mysqlDate
   * @param string $inputDateFormat
   *
   * @return int
   */
  public static function convertDateToTimestamp($mysqlDate, $inputDateFormat = self::MYSQL_FORMAT_DATE) {
    $date = DateTime::createFromFormat($inputDateFormat, $mysqlDate);

    return ($date) ? $date->getTimestamp() : 0;
  }

  /**
   * Converts timestamp into date
   * In default use MySQL date format('Y-m-d H:i:s')
   *
   * @param $timestamp
   *
   * @return int
   */
  public static function convertTimestampToDate($timestamp) {
    $date = new DateTime();
    $date->setTimestamp($timestamp);

    return $date->format(self::MYSQL_FORMAT_DATE);
  }

}
