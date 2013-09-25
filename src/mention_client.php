<?php
class MentionClient {
  public static function supportsWebmention($target) {

    $headers = self::fetchHead($target);
    $link_header = false;
      
    if(array_key_exists('Link', $headers)) {
      if(is_array($headers['Link'])) {
        $link_header = implode($headers['Link'], ", ");
      } else {
        $link_header = $headers['Link'];
      }
    }

    if ($link_header && ($endpoint = self::findWebmentionEndpointInHeader($link_header))) {
      return $endpoint;
    } else {
      $body = self::fetchBody($target);

      if($endpoint = self::findWebmentionEndpointInHTML($body)) {
        return $endpoint;
      }
    }

    return false;
  }
  
  public static function isUrl($url) {
    if (preg_match("/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i", $url, $matches)) {
      return true;
    } else {
      return false;
    }
  }
  
  public static function findWebmentionEndpointInHTML($body) {
    if(preg_match('/<link[ ]+href="([^"]+)"[ ]+rel="webmention"[ ]*\/?>/i', $body, $match)
        || preg_match('/<link[ ]+rel="webmention"[ ]+href="([^"]+)"[ ]*\/?>/i', $body, $match)) {
      return $match[1];
    } elseif(preg_match('/<link[ ]+href="([^"]+)"[ ]+rel="http:\/\/webmention\.org\/?"[ ]*\/?>/i', $body, $match)
        || preg_match('/<link[ ]+rel="http:\/\/webmention\.org\/?"[ ]+href="([^"]+)"[ ]*\/?>/i', $body, $match)) {
      return $match[1];
    } else {
      return false;
    }
  }

  public static function findWebmentionEndpointInHeader($link_header) {
    if(preg_match('~<(https?://[^>]+)>; rel="webmention"~i', $link_header, $match)) {
      return $match[1];
    } elseif(preg_match('~<(https?://[^>]+)>; rel="http://webmention.org/?"~i', $link_header, $match)) {
      return $match[1];
    }
    return false;
  }

  private static function fetchHead($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    $response = curl_exec($ch);
    return self::parseHeaders($response);
  }

  private static function fetchBody($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($ch);
  }

  public static function parseHeaders($headers) {
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
    foreach($fields as $field) {
      if(preg_match('/([^:]+): (.+)/m', $field, $match)) {
        $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
        if(isset($retVal[$match[1]])) {
          $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
        } else {
          $retVal[$match[1]] = trim($match[2]);
        }
      }
    }
    return $retVal;
  }
  
  private static function get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($ch);
  }

  private static function post($url, $body, $headers=array(), $returnHTTPCode=false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if($returnHTTPCode)
      return curl_getinfo($ch, CURLINFO_HTTP_CODE);
    else
      return $response;
  }
}