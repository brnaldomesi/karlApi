<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/7/19
 * Time: 上午8:32
 */

namespace App\Method;


class KARLDateTime
{
    private $dateTime;
    private $language;
    private $clockType;
    private $monthes;

    const LANG_FR = "fr";
    const LANG_EN = "en";
    const LANG_CN = "cn";

    const DATE_TYPE_12 = "12";
    const DATE_TYPE_24 = "24";

    /**
     * KARLDateTime constructor.
     * @param $timestamp
     */
    public function __construct($timestamp)
    {
        $this->dateTime = new \DateTime("@".$timestamp);
        $this->monthes = $this->enMonth;
    }

    /**
     * @param \DateTimeZone $timezone
     */
    public function setTimezone($timezone)
    {
        $this->dateTime->setTimezone($timezone);
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        switch($language){
            case self::LANG_CN:
                $this->monthes = $this->cnMonth;
                break;
            case self::LANG_EN:
                $this->monthes = $this->enMonth;
                break;
            case self::LANG_FR:
                $this->monthes = $this->frMonth;
                break;
            default :
                $this->monthes = $this->enMonth;
                break;
        }
    }

    /**
     * @param mixed $clockType
     */
    public function setClockType($clockType)
    {
        $this->clockType = $clockType;
    }

    private $enMonth = [
        "",
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December"
    ];
    private $cnMonth = [
        "",
        "一月",
        "二月",
        "三月",
        "四月",
        "五月",
        "六月",
        "七月",
        "八月",
        "九月",
        "十月",
        "十一月",
        "十二月"
    ];
    private $frMonth = [
        "",
        "Janvier ",
        "Février ",
        "Mars ",
        "Avril ",
        "Mai",
        "Juin ",
        "Juillet ",
        "Août",
        "Septembre ",
        "Octobre ",
        "Novembre",
        "Décembre"
    ];

    public function getDate(){
        $date = $this->dateTime->format('Y-n-d');
        $dateArray = explode("-",$date);
        switch ($this->language){
            case self::LANG_EN:
                $dateString = $this->monthes[$dateArray[1]]." ".$dateArray[2]." ".$dateArray[0];
                break;
            case self::LANG_FR:
                $dateString = $dateArray[2]." ".$this->monthes[$dateArray[1]]." ".$dateArray[0];
                break;
            case self::LANG_CN:
                $dateString = $dateArray[0]."年".$this->monthes[$dateArray[1]].$dateArray[2]."日";
                break;
            default:
                $dateString = $dateArray[2]." ".$this->monthes[$dateArray[1]]." ".$dateArray[0];
                break;
        }
        return $dateString;
    }

    public function getTime()
    {
        switch ($this->language){
            case self::LANG_EN:
                $dateString = $this->dateTime->format('h:i a');
                break;
            case self::LANG_FR:
                $dateString =$this->dateTime->format('H:i');
                break;
            case self::LANG_CN:
                $dateString = $this->dateTime->format('h:i');
                $noon = $this->dateTime->format('a');
                if($noon == 'am'){
                    $dateString = "上午 ".$dateString;
                }else{
                    $dateString = "下午 ".$dateString;
                }
                break;
            default:
                $dateString =$this->dateTime->format('H:i');
                break;
        }
        return $dateString;
    }


    public function getFullDateAndTime()
    {
        return $this->getDate()." ".$this->getTime();
    }

    public function getTimezone(){
        return $this->dateTime->format('e');
    }
}