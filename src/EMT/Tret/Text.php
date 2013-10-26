<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/10/13
 * Time: 23:48
 */

namespace EMT\Tret;


use EMT\Tret;
/**
 * @see EMT_Tret
 */

class Text extends Tret
{
    public $classes = array(
        'nowrap'           => 'word-spacing:nowrap;',
    );

    /**
     * Базовые параметры тофа
     *
     * @var array
     */
    public $title = "Текст и абзацы";
    public $rules = array(
        'auto_links' => array(
            'description'	=> 'Выделение ссылок из текста',
            'pattern' 		=> '/(\s|^)(http|ftp|mailto|https)(:\/\/)([^\s\,\!\<]{4,})(\s|\.|\,|\!|\?|\<|$)/ieu',
            'replacement' 	=> '$m[1] . $this->tag(substr($m[4],-1)=="."?substr($m[4],0,-1):$m[4], "a", array("href" => $m[2].$m[3].(substr($m[4],-1)=="."?substr($m[4],0,-1):$m[4]))) . (substr($m[4],-1)=="."?".":"") .$m[5]'
        ),
        'email' => array(
            'description'	=> 'Выделение эл. почты из текста',
            'pattern' 		=> '/(\s|^|\&nbsp\;|\()([a-z0-9\-\_\.]{2,})\@([a-z0-9\-\.]{2,})\.([a-z]{2,6})(\)|\s|\.|\,|\!|\?|$|\<)/e',
            'replacement' 	=> '$m[1] . $this->tag($m[2]."@".$m[3].".".$m[4], "a", array("href" => "mailto:".$m[2]."@".$m[3].".".$m[4])) . $m[5]'
        ),
        'no_repeat_words' => array(
            'description'	=> 'Удаление повторяющихся слов',
            'disabled'      => true,
            'pattern' 		=> array(
                '/([а-яё]{3,})( |\t|\&nbsp\;)\1/iu',
                '/(\s|\&nbsp\;|^|\.|\!|\?)(([А-ЯЁ])([а-яё]{2,}))( |\t|\&nbsp\;)(([а-яё])\4)/eu',
            ),
            'replacement' 	=> array(
                '\1',
                '$m[1].($m[7] === EMT_Lib::strtolower($m[3]) ? $m[2] : $m[2].$m[5].$m[6] )',
            )
        ),
        'paragraphs' => array(
            'description'	=> 'Простановка параграфов',
            'function'	=> 'build_paragraphs'
        ),
        'breakline' => array(
            'description'	=> 'Простановка переносов строк',
            'function'	=> 'build_brs'
        ),
    );


    /**
     * Расстановка защищенных тегов параграфа (<p>...</p>) и переноса строки
     *
     * @return  void
     */
    protected function build_paragraphs()
    {
        if (!preg_match('/\<\/?' . self::BASE64_PARAGRAPH_TAG . '\>/', $this->_text)) {
            $this->_text = str_replace("\r\n","\n",$this->_text);
            $this->_text = str_replace("\r","\n",$this->_text);
            $this->_text = '<' . self::BASE64_PARAGRAPH_TAG . '>' . $this->_text . '</' . self::BASE64_PARAGRAPH_TAG . '>';
            //$this->_text = $this->preg_replace_e('/([\040\t]+)?(\n|\r){2,}/e', '"</" . self::BASE64_PARAGRAPH_TAG . "><" .self::BASE64_PARAGRAPH_TAG . ">"', $this->_text);
            //$this->_text = $this->preg_replace_e('/([\040\t]+)?(\n){2,}/e', '"</" . self::BASE64_PARAGRAPH_TAG . "><" .self::BASE64_PARAGRAPH_TAG . ">"', $this->_text);
            $this->_text = $this->preg_replace_e('/([\040\t]+)?(\n)+[\040\t]*(\n)+/e', '"</" . self::BASE64_PARAGRAPH_TAG . "><" .self::BASE64_PARAGRAPH_TAG . ">"', $this->_text);
            $this->_text = str_replace('<' . self::BASE64_PARAGRAPH_TAG . '></' . self::BASE64_PARAGRAPH_TAG . '>', "", $this->_text);
        }
    }

    /**
     * Расстановка защищенных тегов параграфа (<p>...</p>) и переноса строки
     *
     * @return  void
     */
    protected function build_brs()
    {
        if (!preg_match('/\<' . self::BASE64_BREAKLINE_TAG . '\>/', $this->_text)) {
            $this->_text = str_replace("\r\n","\n",$this->_text);
            $this->_text = str_replace("\r","\n",$this->_text);
            //$this->_text = $this->preg_replace_e('/(\n|\r)/e', '"<" . self::BASE64_BREAKLINE_TAG . ">"', $this->_text);
            $this->_text = $this->preg_replace_e('/(\n)/e', '"<" . self::BASE64_BREAKLINE_TAG . ">"', $this->_text);
        }
    }
}