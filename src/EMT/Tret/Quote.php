<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/10/13
 * Time: 23:47
 */

namespace EMT\Tret;


use EMT\Lib;
use EMT\Tret;
/**
 * @see EMT_Tret
 */

class Quote extends Tret
{
    /**
     * Базовые параметры тофа
     *
     * @var array
     */
    public $title = "Кавычки";


    public $rules = array(
        'quotes_outside_a' => array(
            'description'	=> 'Кавычки вне тэга <a>',
            'pattern' 		=> '/(\<%%\_\_.+?\>)\"(.+?)\"(\<\/%%\_\_.+?\>)/s',
            'replacement' 	=> '"\1\2\3"'
        ),

        'open_quote' => array(
            'description'	=> 'Открывающая кавычка',
            'pattern' 		=> '/(^|\(|\s|\>)(\"|\\\")(\S+)/iue',
            'replacement' 	=> '$m[1] . self::QUOTE_FIRS_OPEN . $m[3]'
        ),
        'close_quote' => array(
            'description'	=> 'Закрывающая кавычка',
            'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\")+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|$)/uie',
            'replacement' 	=> '$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"") ) . $m[4]'
        ),
        'close_quote_adv' => array(
            'description'	=> 'Закрывающая кавычка особые случаи',
            //'pattern' 		=> '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\"|\&laquo\;)+)(\<.+?\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\s|\)|\<\/|$)/uie',
            'pattern' 		=>
                array(
                    '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)((\"|\\\"|\&laquo\;)+)(\<[^\>]+\>)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
                    '/([a-zа-яё0-9]|\.|\&hellip\;|\!|\?|\>|\)|\:)(\s+)((\"|\\\")+)(\s+)(\.|\&hellip\;|\;|\:|\?|\!|\,|\)|\<\/|$| )/uie',
                ),
            'replacement' 	=>
                array(
                    '$m[1] . str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[2],"\"")+substr_count($m[2],"&laquo;") ) . $m[4]. $m[5]',
                    '$m[1] .$m[2]. str_repeat(self::QUOTE_FIRS_CLOSE, substr_count($m[3],"\"")+substr_count($m[3],"&laquo;") ) . $m[5]. $m[6]',
                ),
        ),
        'open_quote_adv' => array(
            'description'	=> 'Открывающая кавычка особые случаи',
            'pattern' 		=> '/(^|\(|\s|\>)(\"|\\\")(\s)(\S+)/iue',
            'replacement' 	=> '$m[1] . self::QUOTE_FIRS_OPEN .$m[4]'
        ),
        'quotation' => array(
            'description'	=> 'Внутренние кавычки-лапки и дюймы',
            'function' => 'build_sub_quotations'
        ),
    );

    protected function inject_in($pos, $text)
    {
        for($i=0;$i<strlen($text);$i++) $this->_text[$pos+$i] = $text[$i];
    }

    protected function build_sub_quotations()
    {
        global $__ax,$__ay;
        $okposstack = array('0');
        $okpos = 0;
        $level = 0;
        $off = 0;
        while(true)
        {
            $p = Lib::strposEx($this->_text, array("&laquo;", "&raquo;"), $off);
            if($p===false) break;
            if($p['str'] == "&laquo;")
            {
                if($level>0) if(!$this->isOn('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_OPEN);
                $level++;
            }
            if($p['str'] == "&raquo;")
            {
                $level--;
                if($level>0) if(!$this->isOn('no_bdquotes')) $this->inject_in($p['pos'], self::QUOTE_CRAWSE_CLOSE);
            }
            $off = $p['pos']+strlen($p['str']);
            if($level == 0)
            {
                $okpos = $off;
                array_push($okposstack, $okpos);
            } elseif($level<0) // уровень стал меньше нуля
            {
                if(!$this->isOn('no_inches'))
                {
                    do{
                        $lokpos = array_pop($okposstack);
                        $k = substr($this->_text, $lokpos, $off-$lokpos);
                        $k = str_replace(self::QUOTE_CRAWSE_OPEN, self::QUOTE_FIRS_OPEN, $k);
                        $k = str_replace(self::QUOTE_CRAWSE_CLOSE, self::QUOTE_FIRS_CLOSE, $k);
                        //$k = preg_replace("/(^|[^0-9])([0-9]+)\&raquo\;/ui", '\1\2&Prime;', $k, 1, $amount);

                        $amount = 0;
                        $__ax = preg_match_all("/(^|[^0-9])([0-9]+)\&raquo\;/ui", $k, $m);
                        $__ay = 0;
                        if($__ax)
                        {
                            $k = preg_replace_callback("/(^|[^0-9])([0-9]+)\&raquo\;/ui",
                                create_function('$m','global $__ax,$__ay; $__ay++; if($__ay==$__ax){ return $m[1].$m[2]."&Prime;";} return $m[0];'),
                                $k);
                            $amount = 1;
                        }



                    } while(($amount==0) && count($okposstack));

                    // успешно сделали замену
                    if($amount == 1)
                    {
                        // заново просмотрим содержимое
                        $this->_text = substr($this->_text, 0, $lokpos). $k . substr($this->_text, $off);
                        $off = $lokpos;
                        $level = 0;
                        continue;
                    }

                    // иначе просто заменим последнюю явно на &quot; от отчаяния
                    if($amount == 0)
                    {
                        // говорим, что всё в порядке
                        $level = 0;
                        $this->_text = substr($this->_text, 0, $p['pos']). '&quot;' . substr($this->_text, $off);
                        $off = $p['pos'] + strlen('&quot;');
                        $okposstack = array($off);
                        continue;
                    }
                }
            }


        }
        // не совпало количество, отменяем все подкавычки
        if($level != 0 ){

            // закрывающих меньше, чем надо
            if($level>0)
            {
                $k = substr($this->_text, $okpos);
                $k = str_replace(self::QUOTE_CRAWSE_OPEN, self::QUOTE_FIRS_OPEN, $k);
                $k = str_replace(self::QUOTE_CRAWSE_CLOSE, self::QUOTE_FIRS_CLOSE, $k);
                $this->_text = substr($this->_text, 0, $okpos). $k;
            }
        }
    }

}