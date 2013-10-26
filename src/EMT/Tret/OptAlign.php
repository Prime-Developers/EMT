<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/10/13
 * Time: 23:46
 */

namespace EMT\Tret;


use EMT\Tret;
/**
 * @see EMT_Tret
 */

class OptAlign extends Tret
{

    public $classes = array(
        'oa_obracket_sp_s' => "margin-right:0.3em;",
        "oa_obracket_sp_b" => "margin-left:-0.3em;",
        "oa_obracket_nl_b" => "margin-left:-0.3em;",
        "oa_comma_b"       => "margin-right:-0.2em;",
        "oa_comma_e"       => "margin-left:0.2em;",
        'oa_oquote_nl' => "margin-left:-0.44em;",
        'oa_oqoute_sp_s' => "margin-right:0.44em;",
        'oa_oqoute_sp_q' => "margin-left:-0.44em;",
    );

    /**
     * Базовые параметры тофа
     *
     * @var array
     */
    public $title = "Оптическое выравнивание";
    public $rules = array(
        'oa_oquote' => array(
            'description'	=> 'Оптическое выравнивание открывающей кавычки',
            'disabled'      => true,
            'pattern' 		=> array(
                '/([a-zа-яё\-]{3,})(\040|\&nbsp\;|\t)(\&laquo\;)/uie',
                '/(\n|\r|^)(\&laquo\;)/ei'
            ),
            'replacement' 	=> array(
                '$m[1] . $this->tag($m[2], "span", array("class"=>"oa_oqoute_sp_s")) . $this->tag($m[3], "span", array("class"=>"oa_oqoute_sp_q"))',
                '$m[1] . $this->tag($m[2], "span", array("class"=>"oa_oquote_nl"))',
            ),
        ),
        'oa_obracket_coma' => array(
            'description'	=> 'Оптическое выравнивание для пунктуации (скобка и запятая)',
            'disabled'      => true,
            'pattern' 		=> array(
                '/(\040|\&nbsp\;|\t)\(/ei',
                '/(\n|\r|^)\(/ei',
                '/([а-яёa-z0-9]+)\,(\040+)/iue',
            ),
            'replacement' 	=> array(
                '$this->tag($m[1], "span", array("class"=>"oa_obracket_sp_s")) . $this->tag("(", "span", array("class"=>"oa_obracket_sp_b"))',
                '$m[1] . $this->tag("(", "span", array("class"=>"oa_obracket_nl_b"))',
                '$m[1] . $this->tag(",", "span", array("class"=>"oa_comma_b")) . $this->tag(" ", "span", array("class"=>"oa_comma_e"))',
            ),
        ),

    );


}