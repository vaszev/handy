<?php

namespace Vaszev\HandyBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;
use Twig\Extension\AbstractExtension;
use Vaszev\HandyBundle\Service\Handy;
use Vaszev\HandyBundle\Service\MyRedis;

class HandyExtension extends AbstractExtension {

  private $handy;
  private $myRedis;
  private $container;



  /**
   * HandyExtension constructor.
   * @param Handy $handy
   * @param MyRedis $myRedis
   * @param ContainerInterface $container
   */
  public function __construct(Handy $handy, MyRedis $myRedis, ContainerInterface $container) {
    $this->handy = $handy;
    $this->myRedis = $myRedis;
    $this->container = $container;
  }



  public function getFilters() {
    return [
        new \Twig_SimpleFilter('joinByKey', [$this, 'joinByKey']),
        new \Twig_SimpleFilter('secret', [$this, 'secret']),
        new \Twig_SimpleFilter('minutesTime', [$this, 'minutesTimeFilter']),
        new \Twig_SimpleFilter('dayName', [$this, 'dayNameFilter']),
        new \Twig_SimpleFilter('price', [$this, 'priceFilter']),
        new \Twig_SimpleFilter('imgSizeKept', [$this, 'imgSizeFilterKept']),
        new \Twig_SimpleFilter('friendly', [$this, 'friendlyFilter']),
        new \Twig_SimpleFilter('entityCheck', [$this, 'entityCheck']),
        new \Twig_SimpleFilter('metricToImperial', [$this, 'metricToImperial']),
        new \Twig_SimpleFilter('country', [$this, 'countryFilter']),
        new \Twig_SimpleFilter('ordinal', [$this, 'ordinal']),
        new \Twig_SimpleFilter('strPos', [$this, 'strPos']),
        new \Twig_SimpleFilter('strReplace', [$this, 'strReplace']),
        new \Twig_SimpleFilter('strPad', [$this, 'strPad']),
        new \Twig_SimpleFilter('verifiedDefault', [$this, 'verifiedDefault']),
        new \Twig_SimpleFilter('numberScale', [$this, 'numberScale']),
        new \Twig_SimpleFilter('autoPunctuation', [$this, 'autoPunctuation']),
        new \Twig_SimpleFilter('quotation', [$this, 'quotation'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('resolution', [$this, 'resolution']),
        new \Twig_SimpleFilter('br2nl', [$this, 'br2nl'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('month', [$this, 'month'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('urlDecode', [$this, 'urlDecode'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('pregReplace', [$this, 'pregReplace'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('arrayToTableOfContents', [$this, 'arrayToTableOfContents'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('kgToLbs', [$this, 'kgToLbs']),
        new \Twig_SimpleFilter('dateDayName', [$this, 'dateDayName'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('regex', [$this, 'regex'], ['is_safe' => ['html']]),
        new \Twig_SimpleFilter('redis', [$this, 'redis'], ['is_safe' => ['html']]),
    ];
  }



  public function getFunctions() {
    return [
        new \Twig_SimpleFunction('lorem', [$this, 'loremIpsum']),
        new \Twig_SimpleFunction('rnd', [$this, 'rndGen']),
        new \Twig_SimpleFunction('instanceCheck', [$this, 'instanceCheck']),
        new \Twig_SimpleFunction('randomChr', [$this, 'randomChr']),
    ];
  }



  public function redis($text, $key) {
    $cacheKey = 'twig_' . $key . '_' . MyRedis::EXCLUDE_USER;
    if (!$cache = $this->myRedis->getFast($cacheKey)) {
      $this->myRedis->setFast($cacheKey, $text);

      return $text;
    }

    return $cache;
  }



  public function randomChr($min = 5, $max = 10) {
    $letters = range('a', 'z');
    $length = rand($min, $max);
    shuffle($letters);
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= (rand(0, 2) ? strtoupper($letters[$i]) : $letters[$i]);
    }

    return $str;
  }



  public function regex($str, $pattern = '/^\/.+\/[a-zA-Z]*$/', $replacement = ' ') {
    return preg_replace($pattern, $replacement, $str);
  }



  public function dateDayName($dateStr) {
    try {
      $date = new \DateTime($dateStr);
      $dayName = $date->format('D');
    } catch (\Exception $e) {
      $dayName = null;
    }

    return $dayName;
  }



  public function kgToLbs($kg) {
    $lbs = round(($kg * 2.20462), 1);

    return $lbs . ' lbs';
  }



  public function instanceCheck($entity, $className) {
    $fullClassName = 'App\Entity\\' . $className;
    $class = new $fullClassName;

    return ($entity instanceof $class);
  }



  public function arrayToTableOfContents($arr, $property = 'name') {
    $ret = [];
    $letters = range('A', 'Z');
    foreach ($arr as $item) {
      $found = false;
      $value = $item->{"get" . ucfirst($property)}();
      $firstLetter = strtoupper(substr($value, 0, 1));
      foreach ($letters as $letter) {
        if ($letter == $firstLetter) {
          if (!isset($ret[$letter])) {
            $ret[$letter] = [];
          }
          $ret[$letter][] = $item;
          $found = true;
        }
      }
      if (!$found) {
        if (!isset($ret['#'])) {
          $ret['#'] = [];
        }
        $ret['#'][] = $item;
      }
    }

    return $ret;
  }



  public function pregReplace($string, $keywords, $highLightTag = 'b') {
    foreach ($keywords as $keyword) {
      $string = preg_replace("/\w*?$keyword\w*/i", "<$highLightTag>$0</$highLightTag>", $string);
    }

    return $string;
  }



  public function urlDecode($url = null) {
    return urldecode($url);
  }



  public function month($number) {
    $ts = mktime(0, 0, 0, $number, 1, date("Y"));

    return date("M", $ts);
  }



  public function rndGen($start = 0, $end = 100, $float = false) {
    $tmp = [];
    $tmp[] = (integer)$start;
    $tmp[] = (integer)$end;
    sort($tmp, SORT_NUMERIC);
    $rnd = rand($tmp[0], $tmp[1]);
    if ($float) {
      $d = rand(1, 99) / 100;
      $rnd += $d;
    }

    return $rnd;
  }



  public function loremIpsum($words = 1, $onlyalpha = false, $html = false) {
    $sample = $this->handy->loremIpsum(100, true);

    $tags = ['h2', 'h3', 'h4', 'strong', 'span', 'underline', 'p', 'em', 'a'];
    $tmp = [];

    $arr = explode(" ", $sample);
    for ($w = 0; $w < ($words); $w++) {
      shuffle($arr);
      $first = $arr[0];
      if ($onlyalpha) {
        $tmp[] = preg_replace("/[^a-zA-Z]+/", "", $first);
      } else {
        $tmp[] = $first;
      }
      if ($html) {
        shuffle($tags);
        $tag = $tags[0];
        $tmp[] = '<' . ($tag == "a" ? 'a href="javascript:void(0);"' : $tag) . '>' . $first . '</' . $tag . '>';
      }
    }
    shuffle($tmp);

    return implode(" ", $tmp);
  }



  public function entityCheck($entity) {
    return $this->handy->entityCheck($entity);
  }



  public function friendlyFilter($str, $default = 'untitled') {
    $str = trim($this->handy->friendlyFilter($str));
    $str = ($str ? $str : $default);

    return $str;
  }



  public function minutesTimeFilter($number) {
    $hours = floor($number / 60);
    $minutes = $number % 60;
    $str = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);

    return $str;
  }



  public function dayNameFilter($number) {
    $days = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday',];

    return $days[$number];
  }



  public function priceFilter($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',', $currency = '$') {
    $price = number_format($number, $decimals, $decPoint, $thousandsSep);
    $price = $currency . $price;

    return $price;
  }



  public function imgSizeFilterKept($path, $size = 'small') {
    error_reporting(E_ERROR);
    $outer = strpos($path, 'http');
    if ($outer === false) {
      // not outer
    } else {
      // outer link, start with http
      return $path;
    }
    $rootDir = $this->container->get('kernel')->getRootDir();
    $docPath = trim($this->container->getParameter('vaszev_handy.docs'), '/');
    $defaultImage = $this->container->getParameter('vaszev_handy.default_image');
    $defaultImageNewName = 'default-transparent.png';
    $defaultImageDestination = $rootDir . '/../public/' . $docPath . '/' . $defaultImageNewName;
    // copy default image if not exists
    if (!file_exists($defaultImageDestination)) {
      copy($defaultImage, $defaultImageDestination);
    }
    $unfold = explode('/', $path);
    $fileStr = end($unfold);
    $originalUrl = $docPath . '/' . $fileStr;
    if (!file_exists($originalUrl)) {
      // if path is out of vaszev_handy.docs folder, we're gonna copy there
      copy($rootDir . '/../public/' . $path, $rootDir . '/../public/' . $originalUrl);
    }
    $resizedUrl = $docPath . '/' . $size . '-kept' . '/' . $fileStr;
    // pre-check for image, get default is it fails
    $originalImageSize = @getimagesize($originalUrl);
    if (empty($originalImageSize)) {
      // not an image
      $originalUrl = $docPath . '/' . $defaultImageNewName;
      $resizedUrl = $docPath . '/' . $size . '-kept' . '/' . $defaultImageNewName;
    }
    // finally, get that image with correct size
    if (!file_exists($resizedUrl)) {
      $resizedUrl = $this->handy->getImageVersion($originalUrl, $size);
    } else {
      $resizedUrl = '/' . $resizedUrl;
    }

    return $resizedUrl;
  }



  public function metricToImperial($cm = 0) {
    return $this->handy->metricToImperial($cm);
  }



  public function countryFilter($countryCode) {
    $c = Intl::getRegionBundle()->getCountryName($countryCode);

    return ($c ? $c : $countryCode);
  }



  public function ordinal($number) {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
      return $number . 'th';
    } else {
      return $number . $ends[$number % 10];
    }
  }



  public function strPos($string, $findMe) {
    if (empty($string) || empty($findMe)) {
      return null;
    }

    return stripos($string, $findMe);
  }



  public function strReplace($string, $findMe, $replace) {
    $ret = str_ireplace($findMe, $replace, $string);

    return $ret;
  }



  public function numberScale($number, $decimal = 1) {
    return $this->handy->numberScale($number, $decimal);
  }



  public function autoPunctuation($string = null, $prefix = null, $postfix = null) {
    if (empty($string)) {
      return null;
    }
    $string = trim($string);
    $end = substr($string, -1);
    $chk = ctype_alnum($end);
    $txt = $prefix . ($chk ? $string . '.' : $string) . $postfix;

    return $txt;
  }



  public function quotation($string, $stripTags = true) {
    $string = html_entity_decode($string);
    $string = nl2br($string);
    if ($stripTags) {
      $string = strip_tags($string);
    }
    $string = html_entity_decode($string, ENT_QUOTES);
    $string = str_replace("\n", ' ', $string);
    $string = str_replace("\r", '', $string);
    $string = str_replace('"', "'", $string);

    return $string;
  }



  public function resolution($path, $type = 'width') {
    try {
      $info = @getimagesize($path);
      if (empty($info)) {
        throw new \Exception('invalid image');
      }
      if ($type == 'width') {
        return $info[0];
      } else {
        return $info[1];
      }
    } catch (\Exception $e) {
      return null;
    }
  }



  public function secret($str) {
    $str = str_rot13($str);
    $str = str_shuffle($str);

    return $str;
  }



  public function strPad($str, $padLength, $padString, $orient = STR_PAD_LEFT) {
    return str_pad($str, $padLength, $padString, $orient);
  }



  public function br2nl($str) {
    $breaks = ["<br />", "<br>", "<br/>"];
    $str = str_ireplace($breaks, "\r\n", $str);

    return $str;
  }



  public function joinByKey($arr, $glue = ',', $index = null) {
    if (!is_array($arr) || empty($index)) {
      return $arr;
    }
    $tmp = [];
    foreach ($arr as $item) {
      foreach ($item as $key => $val) {
        if ($key == $index) {
          $tmp[] = $val;
        }
      }
    }
    $ret = implode($glue, $tmp);

    return $ret;
  }



  public function getName() {
    return 'handy_extension';
  }

}
