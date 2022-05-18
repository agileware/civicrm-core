<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * @file
 * API for event export in iCalendar format
 * as outlined in Internet Calendaring and
 * Scheduling Core Object Specification
 */
class CRM_Utils_ICalendar {

  /**
   * Escape text elements for safe ICalendar use.
   *
   * @param string $text
   *   Text to escape.
   * @param bool $keep_html
   *   Flag to retain HTML formatting
   *
   * @return string
   */
  public static function formatText($text, $keep_html = FALSE) {
    if(!$keep_html) {
      $text = preg_replace(
        '{ <a [^>]+ \\b href=(?: "( [^"]+ )" | \'( [^\']+ )\' ) [^>]* > ( [^<]* ) </a> }xi',
        '$3 ($1$2)',
        $text
      );

      $text = strip_tags($text);
      $text = str_replace("\\", "\\\\", $text);
      $text = str_replace(',', '\,', $text);
      $text = str_replace(';', '\;', $text);
      $text = str_replace(["\r\n", "\n", "\r"], "\\n ", $text);
    }
    else {
      $text = '<!DOCTYPE HTML PUBLIC ""-//W3C//DTD HTML 3.2//EN""><html><body>' . $text . '</body></html>';
      $text = base64_encode($text);
    }
    $text = implode("\n ", str_split($text, 50));
    return $text;
  }

  /**
   * Restore iCal formatted text to normal.
   *
   * @param string $text
   *   Text to unescape.
   *
   * @return string
   */
  public static function unformatText($text) {
    $text = str_replace('\n ', "\n", $text);
    $text = str_replace('\;', ';', $text);
    $text = str_replace('\,', ',', $text);
    $text = str_replace("\\\\", "\\", $text);
    $text = str_replace("DQUOTE", "\"", $text);
    return $text;
  }

  /**
   * Escape date elements for safe ICalendar use.
   *
   * @param string $date
   *   Date to escape.
   *
   * @param bool $gdata
   *
   * @return string
   *   Escaped date
   */
  public static function formatDate($date, $gdata = FALSE) {

    if ($gdata) {
      return date("Y-m-d\TH:i:s.000",
        strtotime($date)
      );
    }
    else {
      return date("Ymd\THis",
        strtotime($date)
      );
    }
  }

  /**
   * Send the ICalendar to the browser with the specified content type
   * - 'text/calendar' : used for iCal formatted feed
   * - 'text/xml'      : used for gData or rss formatted feeds
   *
   *
   * @param string $calendar
   *   The calendar data to be published.
   * @param string $content_type
   * @param string $charset
   *   The character set to use, defaults to 'us-ascii'.
   * @param string $fileName
   *   The file name (for downloads).
   * @param string $disposition
   *   How the file should be sent ('attachment' for downloads).
   */
  public static function send($calendar, $content_type = 'text/calendar', $charset = 'us-ascii', $fileName = NULL, $disposition = NULL) {
    $config = CRM_Core_Config::singleton();
    $lang = $config->lcMessages;
    CRM_Utils_System::setHttpHeader("Content-Language", $lang);
    CRM_Utils_System::setHttpHeader("Content-Type", "$content_type; charset=$charset");

    if ($fileName) {
      CRM_Utils_System::setHttpHeader('Content-Length', strlen($calendar));
      CRM_Utils_System::setHttpHeader("Content-Disposition", "$disposition; filename=\"$fileName\"");
      CRM_Utils_System::setHttpHeader("Pragma", "no-cache");
      CRM_Utils_System::setHttpHeader("Expires", "0");
      CRM_Utils_System::setHttpHeader("Cache-Control", "no-cache, must-revalidate");
    }

    echo $calendar;
  }

  /**
   * @param array $timezones - Timezone strings
   * @param $date_min
   * @param $date_max
   *
   * @return array
   */
  public static function generate_timezones(array $timezones, $date_min, $date_max) {
    if (empty($timezones)) {
      return [];
    }

    $tz_items = [];

    foreach ($timezones as $tzstr) {
        $timezone = new DateTimeZone($tzstr);

        $transitions = $timezone->getTransitions($date_min, $date_max);

        if (count($transitions) === 1) {
          $transitions[] = array_values($transitions)[0];
        }

        $item = [
          'id' => $timezone->getName(),
          'transitions' => [],
        ];

        $last_transition = array_shift($transitions);

        foreach($transitions as $transition) {
            $item['transitions'][] = [
              'type' => $transition['isdst'] ? 'DAYLIGHT' : 'STANDARD',
              'offset_from' => self::format_tz_offset($last_transition['offset']),
              'offset_to' => self::format_tz_offset($transition['offset']),
              'abbr' => $transition['abbr'],
              'dtstart' => date_create($transition['time'], $timezone)->format("Ymd\THis"),
            ];

          $last_transition = $transition;
        }

        $tz_items[] = $item;
    }

    return $tz_items;
  }

  protected static function format_tz_offset($offset) {
    $offset /= 60;
    $hours = intval($offset / 60);
    $minutes = abs(intval($offset % 60));

    return sprintf('%+03d%02d', $hours, $minutes);
  }

}
