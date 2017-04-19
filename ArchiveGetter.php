<?php

/**
 * Created by PhpStorm.
 * User: zenbu
 * Date: 18.04.2017
 * Time: 11:40
 */
namespace app;

class ArchiveGetter
{
    private $basePath = "http://volcano.febras.net/archive/";

    private function convertToZeroFormat($num)
    {
        if($num >= 10)
            return strval($num);
        else
            return '0'.strval($num);
    }

    private function generateCalendar($years)
    {
        $calendar = [];
        foreach ($years as $t_year)
            $calendar[$t_year] = [];

        foreach ($calendar as $year => $content) {
            $content = [];
            for($month = 1; $month < 13; $month++) {
                $content[$this->convertToZeroFormat($month)] = [];
                $days_num = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                for($day = 1; $day <= $days_num; $day++) {
                    if($day == 27 && $month == 5)
                        continue;
                    $content[$this->convertToZeroFormat($month)][] = $this->convertToZeroFormat($day);
                }
            }
            $calendar[$year] = $content;
        }

        return $calendar;
    }

    private function getHtml($calendar)
    {
        foreach ($calendar as $year => $content) {
            foreach ($content as $month => $days) {
                foreach ($days as $day) {
                    $link = $this->basePath.$year.'/'.$month.'/'.$day.'/SHV1';
                    $raw_html = file_get_contents($link);
                    file_put_contents('html/'.$year.$month.$day.'.html', $raw_html);
                }
            }
        }
    }

    /**
     * @param $filename
     * @return \DateTime
     */
    private function restoreDateTimeFromFilename($filename)
    {
        $basename = basename($filename, '.jpg');
        $year = substr($basename, 5, 4);
        $month = substr($basename, 9, 2);
        $day = substr($basename, 11, 2);
        $hour = substr($basename, 13, 2);
        $minute = substr($basename, 15, 2);
        $date = \DateTime::createFromFormat("Y-m-d H:i", $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute);
        return $date;
    }

    private function restoreLinkFromDt($dt, $filename)
    {
        $year = $dt->format('Y');
        $month = $dt->format('m');
        $day = $dt->format('d');
        return $this->basePath.$year.'/'.$month.'/'.$day.'/SHV1/'.$filename;
    }

    private function parseWithInterval($interval)
    {
        $res = [];
        $html_files = array_slice(scandir('html/'), 2);
        foreach ($html_files as $html_file) {
            $dom_document = new \DOMDocument();
            $dom_document->loadHTMLFile('html/'.$html_file);
            $href_list = $dom_document->getElementsByTagName('a');
            $current_dt = null;
            foreach ($href_list as $href) {
                if($href->getAttribute('href') != '../') {
                    $dt = $this->restoreDateTimeFromFilename($href->getAttribute('href'));
                    if ($current_dt == null) {
                        $res[] = $this->restoreLinkFromDt($dt, $href->getAttribute('href'));
                        $current_dt = $dt;
                    } else {
                        $diff = date_diff($dt, $current_dt)->format('%i');
                        if ($diff >= $interval) {
                            $res[] = $this->restoreLinkFromDt($dt, $href->getAttribute('href'));
                            $current_dt = $dt;
                        }
                    }
                }
            }
        }
        return $res;
    }

    private function saveLinks($links)
    {
        $res_str = implode(';', $links);
        file_put_contents('txt/links.txt', $res_str);
    }

    public function getLinksWithInterval($interval_mins, $loadHtml=false, $saveLinks=false, $loadImg=false)
    {
        // Сгенерировали календарь выбранного диапазона лет.
        $calendar = $this->generateCalendar([2015]);
        // Если указали флаг, подгружаем HTML.
        if($loadHtml)
            $this->getHtml($calendar);
        // Если указали флаг, генерируем ссылки и сохраняем в файлик.
        if($saveLinks) {
            $result = $this->parseWithInterval($interval_mins);
            $this->saveLinks($result);
        }
        if($loadImg) {
            $raw_links = file_get_contents('txt/links.txt');
            $links = explode(';', $raw_links);
            foreach ($links as $link) {
                $img = 'img/'.basename($link);
                file_put_contents($img, file_get_contents($link));
            }
        }
    }
}

?>