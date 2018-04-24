<?php

/**
 * Provides php curl
 */
class CRM_Odoosync_Sync_Request_Curl {

  /**
   * Sends xml by url
   *
   * @param string $url
   * @param string $xml
   *
   * @return mixed
   */
  public function sendXml($url, $xml) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: text/xml']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
  }

}
