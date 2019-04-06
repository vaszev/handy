<?php

namespace Vaszev\HandyBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Glooby\Pexels\Client;
use Gregwar\Image\Image;
use ImageOptimizer\OptimizerFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Handy {

  private $translator;
  private $container;
  private $em;
  /** @var UserPasswordEncoderInterface */
  private $passwordEncoder;
  private $encoderFactory;



  public function __construct($passwordEncoder, TranslatorInterface $translator,
                              ContainerInterface $container, EntityManagerInterface $em,
                              EncoderFactoryInterface $encoderFactory) {
    $this->translator = $translator;
    $this->container = $container;
    $this->em = $em;
    $this->passwordEncoder = $passwordEncoder;
    $this->encoderFactory = $encoderFactory;
  }



  /**
   * Checks if entity's callable while get value getId() method
   * @param $entity
   * @return bool
   */
  public function entityCheck($entity) {
    try {
      if (empty($entity)) {
        throw new \Exception("entity is empty");
      }
      if (!is_object($entity)) {
        throw new \Exception("entity is not an object");
      }
      $entity->getId();

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }



  /**
   * encodes user's password and return that string
   * @param object $user
   * @param string $password
   * @return string
   */
  public function passwordEncode($user = null, $password = null) {
    $encoded = $this->passwordEncoder->encodePassword($user, $password);

    return $encoded;
  }



  /**
   * verify that a new password and the old one are matches
   */
  public function passwordVerify($user = null, $oldpassword = null, $oldpasswordencoded = null) {
    $encoder = $this->encoderFactory->getEncoder($user);
    $valid = $encoder->isPasswordValid($oldpasswordencoded, $oldpassword, $user->getSalt());

    return $valid;
  }



  /**
   * generates a single password
   * @return string
   */
  public function genSimplePassword() {
    $base = $this->loremIpsum(1, true);
    $password = $base . "!" . rand(100, 999);

    return $password;
  }



  /**
   * generates lorem ipsum text on default|cyrill|hebrew languages
   * @param number $words maximum number of words
   * @param boolean $onlyalpha cut off everything but a..Z
   * @param boolean $html enclose words between random html tags
   * @param array $langs <default,cyrill,hebrew>
   * @param string $separator
   * @return string
   */
  public function loremIpsum($words = 1, $onlyalpha = false, $html = false, $langs = ['default'], $separator = " ") {

    $sample = [];
    $sample["default"] = "Ea vix ornatus offendit delicatissimi, perfecto similique in has. Summo consetetur at vis. Vix an nulla malorum sapientem, nostrud voluptatum cum ex, an usu civibus accusam salutatus. Ex magna voluptaria his, has latine convenire assentior in, vel insolens pertinacia ut. Id justo ullum meliore sit, cu tempor nemore ius.";
    $sample["cyrill"] = "Ыам ан ножтрюм дэфянятйоныс ентырпрытаряш, алиё трактатоз консэквюат жят ку, мэя коммодо жанктюч пытынтёюм ты. Льаборэ ножтрюд вэл ед, ад ийжквюы аэтырно нам. Шэа дёжкэрэ дэлььиката йн, коммодо ёнэрмйщ консэквюат эож ад, ыт ыюм фалля дикунт аккюжамюз. Эож экз омнеж мютат кэтэро, видишчы аликвюип аккюмзан но эжт. Эю вэл эзшэ аппэльлььантюр, алиё вёртюты пытынтёюм нам ты. Этёам доктюж дуо ку.";
    $sample["hebrew"] = "ומהימנה חרטומים אתנולוגיה שכל דת, הספרות והנדסה קלאסיים אחד ב. כלל מה לציין הבקשה לערכים, היום משופרות בדף בה. את שער מיותר איטליה, בדף אל מיזם המדינה שיתופית. הראשי למתחילים דת כדי, אל בקרבת צרפתית העברית אתה, מתוך חינוך מדריכים או בקר. ערכים ייִדיש אספרנטו ב עזה, של זכר קבלו למנוע, נפלו כלשהו אתה על. ליצירתה ויקימדיה וספציפיים ויש או, קרן למנוע בחירות משפטים או.";

    $tags = ['h2' => 2, 'h3' => 3, 'h4' => 3, 'strong' => 5, 'span' => 20, 'underline' => 3, 'p' => 30, 'em' => 5, 'a' => 3];
    $shuffledTags = [];
    $keys = array_keys($tags);
    shuffle($keys);
    foreach ($keys as $key) {
      $shuffledTags[$key] = $tags[$key];
    }
    $tags = $shuffledTags;
    $tagWords = 0;
    $tmp = [];
    foreach ($langs as $one_lang) {
      $txt = $sample[$one_lang];
      $arr = explode(" ", $txt);
      for ($w = 0; $w < ($words / count($langs)); $w++) {
        shuffle($arr);
        $first = $arr[0];
        if ($onlyalpha) {
          $tmp[] = preg_replace("/[^a-zA-Z]+/", "", $first);
        } else {
          $tmp[] = $first;
        }
      } // FOR
    }
    shuffle($tmp);
    if ($html) {
      $htmlTmp = [];
      for ($w = 0; $w < count($tmp); $w++) {
        if ($tagWords == 0) {
          $next = (list($tag, $tagWords) = each($tags));
          if (!$next) {
            reset($tags);
            list($tag, $tagWords) = each($tags);
          }
          $htmlTmp[] = '<' . ($tag == "a" ? 'a href="javascript:void(0);"' : $tag) . '>';
        }
        $htmlTmp[] = $tmp[$w];
        $tagWords--;
        if ($tagWords == 0) {
          $htmlTmp[] = '</' . $tag . '>';
        }
      }
      if ($tagWords > 0) {
        // handle unclosed tags
        $htmlTmp[] = '</' . $tag . '>';
      }
      $tmp = $htmlTmp;
    }

    return implode($separator, $tmp);
  }



  /**
   * Converts all accent characters to ASCII characters.
   * If there are no accent characters, then the string given is just returned.
   * @param string $string Text that might have accent characters.
   * @return string Filtered string with replaced "nice" characters.
   */
  public function removeAccents($string) {
    if (!preg_match('/[\x80-\xff]/', $string)) {
      return $string;
    }

    $chars = [
      // Decompositions for Latin-1 Supplement
        chr(194) . chr(170)            => 'a', chr(194) . chr(186) => 'o', chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
        chr(195) . chr(130)            => 'A', chr(195) . chr(131) => 'A', chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
        chr(195) . chr(134)            => 'AE', chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E', chr(195) . chr(137) => 'E',
        chr(195) . chr(138)            => 'E', chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I', chr(195) . chr(141) => 'I',
        chr(195) . chr(142)            => 'I', chr(195) . chr(143) => 'I', chr(195) . chr(144) => 'D', chr(195) . chr(145) => 'N',
        chr(195) . chr(146)            => 'O', chr(195) . chr(147) => 'O', chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
        chr(195) . chr(150)            => 'O', chr(195) . chr(153) => 'U', chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
        chr(195) . chr(156)            => 'U', chr(195) . chr(157) => 'Y', chr(195) . chr(158) => 'TH', chr(195) . chr(159) => 's',
        chr(195) . chr(160)            => 'a', chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a', chr(195) . chr(163) => 'a',
        chr(195) . chr(164)            => 'a', chr(195) . chr(165) => 'a', chr(195) . chr(166) => 'ae', chr(195) . chr(167) => 'c',
        chr(195) . chr(168)            => 'e', chr(195) . chr(169) => 'e', chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
        chr(195) . chr(172)            => 'i', chr(195) . chr(173) => 'i', chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
        chr(195) . chr(176)            => 'd', chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o', chr(195) . chr(179) => 'o',
        chr(195) . chr(180)            => 'o', chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o', chr(195) . chr(184) => 'o',
        chr(195) . chr(185)            => 'u', chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u', chr(195) . chr(188) => 'u',
        chr(195) . chr(189)            => 'y', chr(195) . chr(190) => 'th', chr(195) . chr(191) => 'y', chr(195) . chr(152) => 'O',
      // Decompositions for Latin Extended-A
        chr(196) . chr(128)            => 'A', chr(196) . chr(129) => 'a', chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
        chr(196) . chr(132)            => 'A', chr(196) . chr(133) => 'a', chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
        chr(196) . chr(136)            => 'C', chr(196) . chr(137) => 'c', chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
        chr(196) . chr(140)            => 'C', chr(196) . chr(141) => 'c', chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
        chr(196) . chr(144)            => 'D', chr(196) . chr(145) => 'd', chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
        chr(196) . chr(148)            => 'E', chr(196) . chr(149) => 'e', chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
        chr(196) . chr(152)            => 'E', chr(196) . chr(153) => 'e', chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
        chr(196) . chr(156)            => 'G', chr(196) . chr(157) => 'g', chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
        chr(196) . chr(160)            => 'G', chr(196) . chr(161) => 'g', chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
        chr(196) . chr(164)            => 'H', chr(196) . chr(165) => 'h', chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
        chr(196) . chr(168)            => 'I', chr(196) . chr(169) => 'i', chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
        chr(196) . chr(172)            => 'I', chr(196) . chr(173) => 'i', chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
        chr(196) . chr(176)            => 'I', chr(196) . chr(177) => 'i', chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
        chr(196) . chr(180)            => 'J', chr(196) . chr(181) => 'j', chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
        chr(196) . chr(184)            => 'k', chr(196) . chr(185) => 'L', chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
        chr(196) . chr(188)            => 'l', chr(196) . chr(189) => 'L', chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
        chr(197) . chr(128)            => 'l', chr(197) . chr(129) => 'L', chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
        chr(197) . chr(132)            => 'n', chr(197) . chr(133) => 'N', chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
        chr(197) . chr(136)            => 'n', chr(197) . chr(137) => 'N', chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
        chr(197) . chr(140)            => 'O', chr(197) . chr(141) => 'o', chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
        chr(197) . chr(144)            => 'O', chr(197) . chr(145) => 'o', chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
        chr(197) . chr(148)            => 'R', chr(197) . chr(149) => 'r', chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
        chr(197) . chr(152)            => 'R', chr(197) . chr(153) => 'r', chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
        chr(197) . chr(156)            => 'S', chr(197) . chr(157) => 's', chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
        chr(197) . chr(160)            => 'S', chr(197) . chr(161) => 's', chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
        chr(197) . chr(164)            => 'T', chr(197) . chr(165) => 't', chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
        chr(197) . chr(168)            => 'U', chr(197) . chr(169) => 'u', chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
        chr(197) . chr(172)            => 'U', chr(197) . chr(173) => 'u', chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
        chr(197) . chr(176)            => 'U', chr(197) . chr(177) => 'u', chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
        chr(197) . chr(180)            => 'W', chr(197) . chr(181) => 'w', chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
        chr(197) . chr(184)            => 'Y', chr(197) . chr(185) => 'Z', chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
        chr(197) . chr(188)            => 'z', chr(197) . chr(189) => 'Z', chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
      // Decompositions for Latin Extended-B
        chr(200) . chr(152)            => 'S', chr(200) . chr(153) => 's', chr(200) . chr(154) => 'T', chr(200) . chr(155) => 't',
      // Euro Sign
        chr(226) . chr(130) . chr(172) => 'E',
      // GBP (Pound) Sign
        chr(194) . chr(163)            => '',
      // Vowels with diacritic (Vietnamese)
// unmarked
        chr(198) . chr(160)            => 'O', chr(198) . chr(161) => 'o', chr(198) . chr(175) => 'U', chr(198) . chr(176) => 'u',
      // grave accent
        chr(225) . chr(186) . chr(166) => 'A', chr(225) . chr(186) . chr(167) => 'a', chr(225) . chr(186) . chr(176) => 'A', chr(225) . chr(186) . chr(177) => 'a',
        chr(225) . chr(187) . chr(128) => 'E', chr(225) . chr(187) . chr(129) => 'e', chr(225) . chr(187) . chr(146) => 'O', chr(225) . chr(187) . chr(147) => 'o',
        chr(225) . chr(187) . chr(156) => 'O', chr(225) . chr(187) . chr(157) => 'o', chr(225) . chr(187) . chr(170) => 'U', chr(225) . chr(187) . chr(171) => 'u',
        chr(225) . chr(187) . chr(178) => 'Y', chr(225) . chr(187) . chr(179) => 'y',
      // hook
        chr(225) . chr(186) . chr(162) => 'A', chr(225) . chr(186) . chr(163) => 'a', chr(225) . chr(186) . chr(168) => 'A', chr(225) . chr(186) . chr(169) => 'a', chr(225) . chr(186) . chr(178) => 'A', chr(225) . chr(186) . chr(179) => 'a',
        chr(225) . chr(186) . chr(186) => 'E', chr(225) . chr(186) . chr(187) => 'e', chr(225) . chr(187) . chr(130) => 'E', chr(225) . chr(187) . chr(131) => 'e',
        chr(225) . chr(187) . chr(136) => 'I', chr(225) . chr(187) . chr(137) => 'i', chr(225) . chr(187) . chr(142) => 'O', chr(225) . chr(187) . chr(143) => 'o',
        chr(225) . chr(187) . chr(148) => 'O', chr(225) . chr(187) . chr(149) => 'o', chr(225) . chr(187) . chr(158) => 'O', chr(225) . chr(187) . chr(159) => 'o',
        chr(225) . chr(187) . chr(166) => 'U', chr(225) . chr(187) . chr(167) => 'u', chr(225) . chr(187) . chr(172) => 'U', chr(225) . chr(187) . chr(173) => 'u',
        chr(225) . chr(187) . chr(182) => 'Y', chr(225) . chr(187) . chr(183) => 'y',
      // tilde
        chr(225) . chr(186) . chr(170) => 'A', chr(225) . chr(186) . chr(171) => 'a', chr(225) . chr(186) . chr(180) => 'A', chr(225) . chr(186) . chr(181) => 'a',
        chr(225) . chr(186) . chr(188) => 'E', chr(225) . chr(186) . chr(189) => 'e', chr(225) . chr(187) . chr(132) => 'E', chr(225) . chr(187) . chr(133) => 'e',
        chr(225) . chr(187) . chr(150) => 'O', chr(225) . chr(187) . chr(151) => 'o', chr(225) . chr(187) . chr(160) => 'O', chr(225) . chr(187) . chr(161) => 'o',
        chr(225) . chr(187) . chr(174) => 'U', chr(225) . chr(187) . chr(175) => 'u', chr(225) . chr(187) . chr(184) => 'Y', chr(225) . chr(187) . chr(185) => 'y',
      // acute accent
        chr(225) . chr(186) . chr(164) => 'A', chr(225) . chr(186) . chr(165) => 'a', chr(225) . chr(186) . chr(174) => 'A', chr(225) . chr(186) . chr(175) => 'a',
        chr(225) . chr(186) . chr(190) => 'E', chr(225) . chr(186) . chr(191) => 'e', chr(225) . chr(187) . chr(144) => 'O', chr(225) . chr(187) . chr(145) => 'o',
        chr(225) . chr(187) . chr(154) => 'O', chr(225) . chr(187) . chr(155) => 'o', chr(225) . chr(187) . chr(168) => 'U', chr(225) . chr(187) . chr(169) => 'u',
      // dot below
        chr(225) . chr(186) . chr(160) => 'A', chr(225) . chr(186) . chr(161) => 'a', chr(225) . chr(186) . chr(172) => 'A', chr(225) . chr(186) . chr(173) => 'a',
        chr(225) . chr(186) . chr(182) => 'A', chr(225) . chr(186) . chr(183) => 'a', chr(225) . chr(186) . chr(184) => 'E', chr(225) . chr(186) . chr(185) => 'e',
        chr(225) . chr(187) . chr(134) => 'E', chr(225) . chr(187) . chr(135) => 'e', chr(225) . chr(187) . chr(138) => 'I', chr(225) . chr(187) . chr(139) => 'i',
        chr(225) . chr(187) . chr(140) => 'O', chr(225) . chr(187) . chr(141) => 'o', chr(225) . chr(187) . chr(152) => 'O', chr(225) . chr(187) . chr(153) => 'o',
        chr(225) . chr(187) . chr(162) => 'O', chr(225) . chr(187) . chr(163) => 'o', chr(225) . chr(187) . chr(164) => 'U', chr(225) . chr(187) . chr(165) => 'u',
        chr(225) . chr(187) . chr(176) => 'U', chr(225) . chr(187) . chr(177) => 'u', chr(225) . chr(187) . chr(180) => 'Y', chr(225) . chr(187) . chr(181) => 'y',
      // Vowels with diacritic (Chinese, Hanyu Pinyin)
        chr(201) . chr(145)            => 'a',
      // macron
        chr(199) . chr(149)            => 'U', chr(199) . chr(150) => 'u',
      // acute accent
        chr(199) . chr(151)            => 'U', chr(199) . chr(152) => 'u',
      // caron
        chr(199) . chr(141)            => 'A', chr(199) . chr(142) => 'a', chr(199) . chr(143) => 'I', chr(199) . chr(144) => 'i',
        chr(199) . chr(145)            => 'O', chr(199) . chr(146) => 'o', chr(199) . chr(147) => 'U', chr(199) . chr(148) => 'u',
        chr(199) . chr(153)            => 'U', chr(199) . chr(154) => 'u',
      // grave accent
        chr(199) . chr(155)            => 'U', chr(199) . chr(156) => 'u',
    ];

    return strtr($string, $chars);
  }



  /**
   * Replaces all non alphanumeric characters with values of replacement or joker.
   * @param string $string The input string.
   * @param string $joker The common replacement value.
   * @param array $replacement [optional] Replacement char pairs (search => replace).
   * @param boolean $duplicate [optional] Remove any duplicate joker characters.
   * @return string The string with the replaced values.
   */
  public function replaceNonAlphanumericChars($string, $joker = "", $replacement = [], $duplicate = true) {
    preg_match_all("/[^\p{L}\p{N}]/u", $string, $matches);
    $keys = array_keys($replacement);
    foreach (array_unique($matches[0]) as $char) {
      $key = array_search($char, $keys);
      $string = str_replace($char, ($key !== false ? $replacement[$keys[$key]] : $joker), $string);
    }

    return (!empty($joker) && $duplicate) ? preg_replace("/" . preg_quote($joker) . "+/", $joker, trim($string, $joker)) : $string;
  }



  /**
   * if filename is already taken, set a counter into the filename
   * @param $pathAbsolute
   * @return string
   */
  public function incFileNameIfExists($pathAbsolute) {
    $dirName = dirname($pathAbsolute);
    $fileName = basename($pathAbsolute);

    $count = 1;
    while (file_exists($pathAbsolute)) {
      $dot = strrpos($fileName, ".");
      if ($dot !== false) {
        $ext = substr($fileName, $dot);
        $name = substr($fileName, 0, $dot) . "_" . $count++ . $ext;
      } else {
        $name = $fileName . "_" . $count++;
      }
      $pathAbsolute = $dirName . '/' . $name;
    }

    return $pathAbsolute;
  }



  /**
   * creates a friendly filename
   * @param $fileName
   * @return string
   */
  public function repairFileName($fileName) {
    $fileName = $this->removeAccents($fileName);
    $fileName = $this->replaceNonAlphanumericChars($fileName, "_", ["." => ".", "-" => "-"]);
    if (($pos = strrpos($fileName, ".")) !== false) {
      $fileName = substr($fileName, 0, $pos) . strtolower(substr($fileName, $pos));
    }

    return $fileName;
  }



  public function pngCompression($file = null, $quality = 70, $speed = 1) {
    try {
      $factory = new OptimizerFactory([
          'ignore_errors'    => true,
          'pngquant_options' => [
              '--force', '--speed=' . $speed, '--quality=' . $quality,
          ],
      ]);
      $optimizer = $factory->get('pngquant');
      $optimizer->optimize($file);
    } catch (\Exception $exception) {
      // error
      return false;
    }

    return true;
  }



  public function getImageVersion($path = null, $size = null) {
    if ($path) {
      $gregwar = new Image();
      $unfold = explode("/", $path);
      $filename = array_pop($unfold);
      $parentPath = implode("/", $unfold);
      $relativePath = $this->container->getParameter('vaszev_handy.docs');
      $variations = $this->container->getParameter('vaszev_handy.image_variations');
      foreach ($variations as $variation => $arr) {
        if ($variation == $size) {
          // let aspect ratio the same
          $newPath = $parentPath . "/" . $variation . "-kept";
          @mkdir($newPath, "0755", true);
          $data = getimagesize($path);
          if (!file_exists($newPath . "/" . $filename)) {
            if ($data[0] < $arr[0] && $data[1] < $arr[1]) {
              $gregwar->open($path)->save($newPath . "/" . $filename, 'guess', $this->container->getParameter('vaszev_handy.image_quality'));
            } else {
              $gregwar->open($path)->cropResize($arr[0], $arr[1], 'transparent')->save($newPath . "/" . $filename, 'guess', $this->container->getParameter('vaszev_handy.image_quality'));
            }
            $this->pngCompression($newPath . "/" . $filename);
          }

          return ($relativePath . $variation . "-kept" . "/" . $filename);
        }
      }
    }
    throw new FileNotFoundException('Image not found');
  }



  /**
   * converts address to geo-coords
   * @param array $address [0:city, 1:street & number, 2:zip & country]
   * @return array
   */
  public function addressToCoords($address = []) {
    $coords = [
        'lat' => null,
        'lng' => null,
    ];
    for ($i = 0; $i < count($address); $i++) {
      if (empty($address[$i])) {
        unset($address[$i]);
      }
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?address=' . rawurlencode(implode(',', $address)));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $results = curl_exec($curl);
    curl_close($curl);
    if ($results) {
      $data = json_decode($results);
      if ($data->status == "OK") {
        $first = $data->results[0];
        $coords['lat'] = $first->geometry->location->lat;
        $coords['lng'] = $first->geometry->location->lng;
      }
    }

    return $coords;
  }



  public function friendlyFilter($str) {
    if (class_exists('Transliterator')) {
      $transliterator = \Transliterator::create('Any-Latin;Latin-ASCII'); // Accents-Any;
      $str = $transliterator->transliterate($str);
    }
    $str = $this->removeAccents($str);
    $str = $this->replaceNonAlphanumericChars($str, "-");
    $str = strtolower($str);

    return $str;
  }



  public function metricToImperial($cm = null) {
    $ret = null;
    try {
      if (empty($cm)) {
        throw new \Exception('empty value');
      }
      $meters = $cm / 100;
      $feets = floor($meters * 3.2808);
      if (!$feets) {
        throw new \Exception('feet value became zero');
      }
      $cm = $cm % (($feets * 0.3048) * 100);
      if (!$cm) {
        throw new \Exception('cm value became zero');
      }
      $inches = round(!$cm ? 0 : $cm / 2.54);
      if ($feets) {
        $ret .= $feets . "'";
      }
      if ($inches) {
        $ret .= $inches . '"';
      }
    } catch (\Exception $e) {
      // error
      $ret = null;
    }

    return $ret;
  }



  public function numberScale($number, $decimal = 1, $minValue = null) {
    $kilo = 1000;
    $mega = $kilo * 1000;
    $giga = $mega * 1000;
    if (!empty($minValue) && $minValue > $number) {
      $number = $minValue;
    }
    if ($number >= $giga) {
      $ret = round(($number / $giga), $decimal) . 'G';
    } elseif ($number >= $mega) {
      $ret = round(($number / $mega), $decimal) . 'M';
    } elseif ($number >= $kilo) {
      $ret = round(($number / $kilo), $decimal) . 'K';
    } else {
      $ret = $number;
    }

    return $ret;
  }



  public function truncateAtWords($str, $maxLength = 100, $sign = '...') {
    $delimiter = ' ';
    $exploded = explode($delimiter, $str);
    $ret = null;
    try {
      $sum = 0;
      $tmp = [];
      foreach ($exploded as $word) {
        $tmp[] = $word;
        $sum += strlen($word) + 1;
        if ($sum >= $maxLength) {
          throw new \Exception('limit reached');
        }
      }
      $ret = implode($delimiter, $tmp);
    } catch (\Exception $e) {
      $ret = implode($delimiter, $tmp) . $sign;
    }

    return $ret;
  }



  public function truncate($str, $max = 25) {
    if (strlen($str) <= $max) {
      return $str;
    }
    $str = mb_substr($str, 0, $max) . "...";

    return $str;
  }



  public function getFavicon($url = null) {
    $favicon = null;
    $elems = parse_url($url);
    $url = $elems['scheme'] . '://' . $elems['host'];
    $output = @file_get_contents($url);
    $regex_pattern = "/rel=\"shortcut icon\" (?:href=[\'\"]([^\'\"]+)[\'\"])?/";
    preg_match_all($regex_pattern, $output, $matches);
    if (isset($matches[1][0])) {
      $favicon = $matches[1][0];
      $favicon_elems = parse_url($favicon);
      if (!isset($favicon_elems['host'])) {
        $favicon = $url . '/' . $favicon;
      }
    }

    return $favicon;
  }



  public function urlResponse($url = null) {
    $exceptions = ['soundcloud.com'];
    foreach ($exceptions as $exception) {
      $pos = strpos($url, $exception);
      if ($pos !== false) {
        return true;
      }
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    if (!curl_errno($ch)) {
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($code >= 200 and $code < 400) {
        return true;
      }
    }
    curl_close($ch);

    return false;
  }



  static function getClientIp() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
      $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
      $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
      $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
      $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
      $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
      $ipaddress = null;
    }

    $ipaddress = str_replace('for=', '', $ipaddress);

    return $ipaddress;
  }



  /**
   * @param $path * must be absolute path
   * @param $filename
   * @return bool|string
   */
  public function filenameCheck($path, $filename) {
    if (empty($filename)) {
      return false;
    }
    if (substr($path, -1) == '/') {
      $path = substr($path, 0, -1);
    }
    $tmp = $filename;
    $count = 1;
    while (file_exists($path . '/' . $tmp)) {
      $unfold = explode(".", $filename);
      $ext = array_pop($unfold);
      $tmp = implode('.', $unfold) . "(" . ($count++) . ")." . $ext;
    }

    return $path . '/' . $tmp;
  }



  /**
   * @param string $type [abstract,animals,business,cats,city,food,nightlife,fashion,people,nature,sports,technics,transport]
   * @param int $w
   * @param int $h
   * @param $uploadDir
   * @return string|null
   */
  public function grabRandomPicture($type = null, $w = 1000, $h = 700, $uploadDir = null) {
    try {
      $imageUrl = 'https://picsum.photos/' . $w . '/' . $h . '/?random';
      $content = file_get_contents($imageUrl);
      if (empty($content)) {
        throw new \Exception('error calling API');
      }
      $tmpName = uniqid() . '.jpg';
      $tmpFile = fopen($uploadDir . '/' . $tmpName, 'wb+');
      fwrite($tmpFile, $content);
      fclose($tmpFile);

      return $tmpName;
    } catch (\Exception $e) {
      // error
    }

    return null;
  }



  /**
   * @param $type
   * @param null $uploadDir
   * @param string $size [https://www.pexels.com/api/documentation/ --> image formats]
   * @return string|null
   */
  public function grabSpecifiedPicture($type, $uploadDir = null, $size = 'large2x') {
    try {
      $pexels = new Client($this->container->getParameter('pexelsApiKey'));
      $response = $pexels->search($type, 1, rand(1, 1000));
      if ($response->getStatusCode() == 200 && $body = json_decode($response->getBody())) {
        $total = $body->total_results;
        if (!$total) {
          throw new \Exception('no results');
        }
        $photos = $body->photos;
        $photo = current($photos);
        $imageUrl = $photo->src->{$size};
        $content = file_get_contents($imageUrl);
        if (empty($content)) {
          throw new \Exception('error calling API');
        }
        $tmpName = uniqid() . '.jpg';
        $tmpFile = fopen($uploadDir . '/' . $tmpName, 'wb+');
        fwrite($tmpFile, $content);
        fclose($tmpFile);

        return $tmpName;
      }
    } catch (\Exception $e) {
      // error
    }

    return null;
  }



  /**
   * @param $text
   * @param string $languages
   * @return string|null
   */
  public function yandexTranslate($text, $languages = 'hu-en') {
    try {
      $apiKey = $this->container->getParameter('yandexTrApiKey');
      $url = 'https://translate.yandex.net/api/v1.5/tr.json/translate?key=' . urlencode($apiKey) . '&lang=' . urlencode($languages) . '&format=plain' . '&text=' . urlencode($text);
      $content = file_get_contents($url);
      if (empty($content)) {
        throw new \Exception('error calling API');
      }
      $data = json_decode($content);
      if ($data && $data->code == 200) {
        return current($data->text);
      }
    } catch (\Exception $e) {
      // error
    }

    return null;
  }

}