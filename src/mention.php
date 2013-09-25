<?php
//error_reporting(0);
require_once('workflows.php');
require_once('mention_client.php');

$query = ltrim($argv[1]);
$parts = explode('\ ', $query);

$w = new Workflows();

// check if url supports webmentions
if (count($parts) == 1 && array_key_exists(0, $parts)) {
  if (MentionClient::isUrl($parts[0])) {
    if (MentionClient::supportsWebmention($parts[0])) {
      $w->result ( 'mention-lookup', '', 'YES!', $parts[0] . ' supports Webmentions', 'icon.png', 'no', $parts[0] );
    } else {
      $w->result ( 'mention-lookup', '', 'NO!', $parts[0] . ' does not support Webmentions', 'icon.png', 'no', $parts[0] );
    }
  } else {
    $w->result ( 'mention-lookup', '', 'wm <target>', 'Check if URL supports Webmentions', 'icon.png', 'no' );
  }
  $w->result ( 'mention-send', '', 'wm '.$parts[0].' <source>', 'Send a Webmention', 'icon.png', 'no' );
}

// webmention part
if (count($parts) >= 2) {
  if (MentionClient::isUrl($parts[0]) && MentionClient::isUrl($parts[1])) {
    if ($endpoint = MentionClient::supportsWebmention($parts[0])) {
      $w->result ( 'mention-send',
                   '"source='.urlencode($parts[1]).'&target='.urlencode($parts[0]).'" '.$endpoint,
                   'Send Webmention',
                   'curl -i -d "source='.urlencode($parts[1]).'&target='.urlencode($parts[0]).'" '.$endpoint,
                   'icon.png',
                   'yes',
                   $parts[0] . " " . $parts[1]
                 );
    } else {
      $w->result ( 'mention-send', '', 'NO!', $parts[0].' doesn\'t support Webmentions', 'icon.png', 'no' );
    }
  } else {
    $w->result ( 'mention-send', '', 'wm '.$parts[0].' '.$parts[1], 'Send a Webmention', 'icon.png', 'no', $parts[0] . " " . $parts[1] );
  }
}

echo $w->toxml();
