<?php

class Eabi_Ipenelo_Calendar_Helper_Dateparser {

    public static function parse($format, $string) {
        $timestamp = time();
        switch ($format) {
            case 'Y-m-d H:i:s':
                $string = str_replace(array('-', ':'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 6) {
                    return false;
                }
                $timestamp = mktime($parts[3], $parts[4], $parts[5], $parts[1], $parts[2], $parts[0]);
                break;
            case 'Y-m-d H:i':
                $string = str_replace(array('-', ':'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 5) {
                    return false;
                }
                $timestamp = mktime($parts[3], $parts[4], 0, $parts[1], $parts[2], $parts[0]);
                break;
            case 'Y-m-d':
                $string = str_replace(array('-'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 3) {
                    return false;
                }
                $timestamp = mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
                break;
            case 'H:i':
                $string = str_replace(array(':'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 2) {
                    return false;
                }
                $timestamp = mktime($parts[0], $parts[1]);
                break;
            case 'd.m.Y':
                $string = str_replace(array('.'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 3) {
                    return false;
                }
                $timestamp = mktime(0, 0, 0, $parts[1], $parts[0], $parts[2]);
                break;
            case 'm/d/Y':
                $string = str_replace(array('/'), ' ', $string);
                $parts = explode(' ', $string);
                if (count($parts) != 3) {
                    return false;
                }
                $timestamp = mktime(0, 0, 0, $parts[0], $parts[1], $parts[2]);
                break;
            default:
                return false;
                break;
        }
        if ($timestamp == false) {
            return false;
        }
        return new DateTime('@' . $timestamp);
//		return DateTime::createFromFormat($format, $string);
    }

}