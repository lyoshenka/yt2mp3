#!/usr/bin/env php
<?php

$extensions = array(
  5 => 'flv',
  18 => 'mp4',
  22 => 'mp4',
  34 => 'flv',
  35 => 'flv',
  37 => 'mp4',
  38 => 'mp4',
  43 => 'webm',
  44 => 'webm',
  45 => 'webm'
);

if ($argc != 2)
{
  die("Usage: " . $argv[0] . " <url>\n"); 
}

$url = $argv[1];

$source = file_get_contents($url);
if (preg_match('/var swfConfig = \{.*\};/', $source, $matches))
{
  
  $title = '';
  if (preg_match('#<title>(.*?)</title>#is', $source, $titleMatches))
  {
    $title = trim(str_replace("\n", '', html_entity_decode($titleMatches[1])));

    $regexes = array(
      '/^YouTube \- /i' => '',
      '![;#"\'\?:\*]!' => '',
      '![&\|\\\/]!' => '_',
      '/\'/' => '\'',
      '/\.+$/' =>  '',
      '/\s+/' => ' '
    );
    foreach ($regexes as $from => $to)
    {
      $title = preg_replace($from, $to, $title);
    }
  }
  if (!$title)
  {
    $title = 'video';
  }

  $json = substr($matches[0], strlen('var swfConfig = '), -1);
  $arr = json_decode($json,true);

  $urlMap = $arr['args']['fmt_url_map'];

  $sep1='%2C';
  $sep2='%7C';
  if (strpos($urlMap, ',') !== false) 
  { 
    $sep1=','; 
    $sep2='|';
  }  

  $formats = array();
  foreach (explode($sep1, $urlMap) as $group)
  {
    list($format, $url) = explode($sep2, trim($group));
    $formats[$format] = strtr(urldecode($url), array(
      '\/' => '/',
      '\u0026' => '&'
    ));
  }


  $bestFormat = max(array_keys($formats));

  $filename = tempnam('/tmp', 'youtubedl');
  
  $cmd = 'wget -O \'%s\' \'%s\' && ffmpeg -i \'%s\' -acodec libmp3lame -ab 320k -y \'%s.mp3\' && rm \'%s\'';
  $cmdWithArgs = sprintf($cmd, $filename, $formats[$bestFormat], $filename, $title, $filename);
  echo $cmdWithArgs . "\n";
  exec($cmdWithArgs);
}

