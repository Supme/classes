<?php
/**
 * Created by PhpStorm.
 * User: Agafonov Alexey (supmea@gmail.com)
 * Date: 15.11.13
 * Time: 10:57
 */


/*
 *
 * Translator::loadTranslation('en', array(
 * 'textfield_1'          => 'This is some Textfield',
 * 'another_textfield '   => 'This is a longer Text showing how this is used',
 * ));
 *
 */

class lang {

    private static $strs = array();
    private static $currlang = 'en_US';

    public static function setFiles($folder = "./lang"){

        $files = scandir($folder);
        foreach ($files as $file){
            if (!is_dir($file)){
                if (empty(self::$strs[$file]))
                    self::$strs[$file] = array();
                if (($handle = fopen("$folder/$file", "r")) !== FALSE) {
                    while (($str = fgetcsv($handle, 1000, "=")) !== FALSE) {
                        self::$strs[$file] = array_merge(self::$strs[$file], array($str[0]=>$str[1]));
                    }
                }
            }
        }
    }

    public static function setArray($lang, $strs){
        if (empty(self::$strs[$lang]))
            self::$strs[$lang] = array();

        self::$strs[$lang] = array_merge(self::$strs[$lang], $strs);
    }

    public static function setDefaultLang($lang){
        self::$currlang = $lang;
    }

    public static function translate($key, $lang=""){
        if ($lang == "") $lang = self::$currlang;
        $str = isset(self::$strs[$lang][$key])?self::$strs[$lang][$key]:"$lang.$key";
        return $str;
    }

    public static function freeUnused(){
        foreach(self::$strs as $lang => $data){
            if ($lang != self::$currlang){
                $lstr = self::$strs[$lang]['langname'];
                self::$strs[$lang] = array();
                self::$strs[$lang]['langname'] = $lstr;
            }
        }
    }

    public static function getLangList(){
        $list = array();
        foreach(self::$strs as $lang => $data){
            $h['name'] = $lang;
            $h['desc'] = self::$strs[$lang]['langname'];
            $h['current'] = $lang == self::$currlang;
            $list[] = $h;
        }
        return $list;
    }

    public static function &getAllStrings($lang){
        return self::$strs[$lang];
    }

}
