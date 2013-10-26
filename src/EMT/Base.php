<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/10/13
 * Time: 23:39
 */

namespace EMT;

/**
 * Основной класс типографа Евгения Муравьёва
 * реализует основные методы запуска и рабыоты типографа
 *
 */
class Base
{
    private $_text = "";
    private $inited = false;

    /**
     * Список Трэтов, которые надо применить к типогрфированию
     *
     * @var array
     */
    protected $trets = array() ;
    protected $trets_index = array() ;
    protected $tret_objects = array() ;

    public $ok             = false;
    public $debug_enabled  = false;
    public $logging        = false;
    public $logs           = array();
    public $errors         = array();
    public $debug_info     = array();

    private $use_layout = false;
    private $class_layout_prefix = false;
    private $use_layout_set = false;
    public $disable_notg_replace = false;
    public $remove_notg = false;

    public $settings = array();

    protected function log($str, $data = null)
    {
        if(!$this->logging) return;
        $this->logs[] = array('class' => '', 'info' => $str, 'data' => $data);
    }

    protected function tretLog($tret, $str, $data = null)
    {
        $this->logs[] = array('class' => $tret, 'info' => $str, 'data' => $data);
    }

    protected function error($info, $data = null)
    {
        $this->errors[] = array('class' => '', 'info' => $info, 'data' => $data);
        $this->log("ERROR $info", $data );
    }

    protected function tretError($tret, $info, $data = null)
    {
        $this->errors[] = array('class' => $tret, 'info' => $info, 'data' => $data);
    }

    protected function debug($class, $place, &$after_text, $after_text_raw = "")
    {
        if(!$this->debug_enabled) return;
        $this->debug_info[] = array(
            'tret'  => $class == $this ? false: true,
            'class' => is_object($class)? get_class($class) : $class,
            'place' => $place,
            'text'  => $after_text,
            'text_raw'  => $after_text_raw,
        );
    }



    protected $_safe_blocks = array();


    /**
     * Включить режим отладки, чтобы посмотреть последовательность вызовов
     * третов и правил после
     *
     */
    public function enableDebug()
    {
        $this->debug_enabled = true;
    }

    /**
     * Включить режим отладки, чтобы посмотреть последовательность вызовов
     * третов и правил после
     *
     */
    public function enableLog()
    {
        $this->logging = true;
    }

    /**
     * Добавление защищенного блока
     *
     * <code>
     *  Jare_Typograph_Tool::addCustomBlocks('<span>', '</span>');
     *  Jare_Typograph_Tool::addCustomBlocks('\<nobr\>', '\<\/span\>', true);
     * </code>
     *
     * @param 	string $id идентификатор
     * @param 	string $open начало блока
     * @param 	string $close конец защищенного блока
     * @param 	string $tag тэг
     * @return  void
     */
    private function _add_safe_block($id, $open, $close, $tag)
    {
        $this->_safe_blocks[$id] = array(
            'id' => $id,
            'tag' => $tag,
            'open' =>  $open,
            'close' =>  $close,
        );
    }

    /**
     * Список защищенных блоков
     *
     * @return 	array
     */
    public function getAllSafeBlocks()
    {
        return $this->_safe_blocks;
    }

    /**
     * Удаленного блока по его номеру ключа
     *
     * @param 	string $id идентифиактор защищённого блока
     * @return  void
     */
    public function removeSafeBlock($id)
    {
        unset($this->_safe_blocks[$id]);
    }


    /**
     * Добавление защищенного блока
     *
     * @param 	string $tag тэг, который должен быть защищён
     * @return  void
     */
    public function addSafeTag($tag)
    {
        $open = preg_quote("<", '/'). $tag."[^>]*?" .  preg_quote(">", '/');
        $close = preg_quote("</$tag>", '/');
        $this->_add_safe_block($tag, $open, $close, $tag);
        return true;
    }


    /**
     * Добавление защищенного блока
     *
     * @param 	string $open начало блока
     * @param 	string $close конец защищенного блока
     * @param 	bool $quoted специальные символы в начале и конце блока экранированы
     * @return  bool
     */
    public function addSafeBlock($id, $open, $close, $quoted = false)
    {
        $open = trim($open);
        $close = trim($close);

        if (empty($open) || empty($close))
        {
            return false;
        }

        if (false === $quoted)
        {
            $open = preg_quote($open, '/');
            $close = preg_quote($close, '/');
        }

        $this->_add_safe_block($id, $open, $close, "");
        return true;
    }


    /**
     * Сохранение содержимого защищенных блоков
     *
     * @param   string $text
     * @param   bool $safe если true, то содержимое блоков будет сохранено, иначе - раскодировано.
     * @return  string
     */
    public function safeBlocks($text, $way, $show = true)
    {
        if (count($this->_safe_blocks))
        {
            $safeType = true === $way ? "EMT_Lib::encryptTag(\$m[2])" : "stripslashes(EMT_Lib::decryptTag(\$m[2]))";
            foreach ($this->_safe_blocks as $block)
            {
                $text = preg_replace_callback("/({$block['open']})(.+?)({$block['close']})/s",   create_function('$m','return $m[1].'.$safeType . '.$m[3];')   , $text);
            }
        }

        return $text;
    }

    private function createObject($tret)
    {
        // если класса нету, попытаемся его прогрузить, например, если стандартный
//        if(!class_exists($tret))
//        {
//            if(preg_match("/^EMT_Tret_([a-zA-Z0-9_]+)$/",$tret, $m))
//            {
//                $tname = $m[1];
//                $fname = str_replace("_"," ",$tname);
//                $fname = ucwords($fname);
//                $fname = str_replace(" ",".",$fname);
                //if(file_exists("EMT.Tret.".$fname.".php"))
//                {
//                }
//            }
//        }
        if(!class_exists($tret))
        {
            $this->error("Класс $tret не найден. Пожалуйста, подргузите нужный файл.");
            return null;
        }

        $obj = new $tret();
        $obj->EMT     = $this;
        $obj->logging = $this->logging;
        return $obj;
    }

    private function getShortTret($tretname)
    {
        if(preg_match("/^EMT_Tret_([a-zA-Z0-9_]+)$/",$tretname, $m))
        {
            return $m[1];
        }
        return $tretname;
    }

    private function _init()
    {
        foreach($this->trets as $tret)
        {
            if(isset($this->tret_objects[$tret])) continue;
            $obj = $this->createObject($tret);
            if($obj == null) continue;
            $this->tret_objects[$tret] = $obj;
        }

        if(!$this->inited)
        {
            $this->addSafeTag('pre');
            $this->addSafeTag('script');
            $this->addSafeTag('style');
            $this->addSafeTag('notg');
            $this->addSafeBlock('span-notg', '<span class="_notg_start"></span>', '<span class="_notg_end"></span>');
        }
        $this->inited = true;
    }





    /**
     * Инициализация класса, используется чтобы задать список третов или
     * спсиок защищённых блоков, которые можно использовать.
     * Такде здесь можно отменить защищённые блоки по умлочнаию
     *
     */
    public function init()
    {

    }

    /**
     * Добавить Трэт,
     *
     * @param mixed $class - имя класса трета, или сам объект
     * @param string $altname - альтернативное имя, если хотим например иметь два одинаоковых терта в обработке
     * @return unknown
     */
    public function addTret($class, $altname = false)
    {
        if(is_object($class))
        {
            if(!is_a($class, "EMT_Tret"))
            {
                $this->error("You are adding Tret that doesn't inherit base class EMT_Tret", get_class($class));
                return false;
            }

            $class->EMT     = $this;
            $class->logging = $this->logging;
            $this->tret_objects[($altname ? $altname : get_class($class))] = $class;
            $this->trets[] = ($altname ? $altname : get_class($class));
            return true;
        }
        if(is_string($class))
        {
            $obj = $this->createObject($class);
            if($obj === null)
                return false;
            $this->tret_objects[($altname ? $altname : $class)] = $obj;
            $this->trets[] = ($altname ? $altname : $class);
            return true;
        }
        $this->error("Чтобы добавить трэт необходимо передать имя или объект");
        return false;
    }

    /**
     * Получаем ТРЕТ по идентивикатору, т.е. заванию класса
     *
     * @param unknown_type $name
     */
    public function getTret($name)
    {
        if(isset($this->tret_objects[$name])) return $this->tret_objects[$name];
        foreach($this->trets as $tret)
        {
            if($tret == $name)
            {
                $this->_init();
                return $this->tret_objects[$name];
            }
            if($this->getShortTret($tret) == $name)
            {
                $this->_init();
                return $this->tret_objects[$tret];
            }
        }
        $this->error("Трэт с идентификатором $name не найден");
        return false;
    }

    /**
     * Задаём текст для применения типографа
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->_text = $text;
    }



    /**
     * Запустить типограф на выполнение
     *
     */
    public function apply($trets = null)
    {
        $this->ok = false;

        $this->init();
        $this->_init();

        $atrets = $this->trets;
        if(is_string($trets)) $atrets = array($trets);
        elseif(is_array($trets)) $atrets = $trets;

        $this->debug($this, 'init', $this->_text);

        $this->_text = $this->safeBlocks($this->_text, true);
        $this->debug($this, 'safe_blocks', $this->_text);

        $this->_text = Lib::safeTagChars($this->_text, true);
        $this->debug($this, 'safeTagChars', $this->_text);

        $this->_text = Lib::clearSpecialChars($this->_text);
        $this->debug($this, 'clearSpecialChars', $this->_text);

        foreach ($atrets as $tret)
        {
            // если установлен режим разметки тэгов то выставим его
            if($this->use_layout_set)
                $this->tret_objects[$tret]->setTagLayoutIfnotset($this->use_layout);

            if($this->class_layout_prefix)
                $this->tret_objects[$tret]->setClassLayoutPrefix($this->class_layout_prefix);

            // влючаем, если нужно
            if($this->debug_enabled) $this->tret_objects[$tret]->debug_on();
            if($this->logging) $this->tret_objects[$tret]->logging = true;

            // применяем трэт
            //$this->tret_objects[$tret]->setText(&$this->_text);
            $this->tret_objects[$tret]->setText($this->_text);
            $this->tret_objects[$tret]->apply();

            // соберём ошибки если таковые есть
            if(count($this->tret_objects[$tret]->errors)>0)
                foreach($this->tret_objects[$tret]->errors as $err )
                    $this->tretError($tret, $err['info'], $err['data']);

            // логгирование
            if($this->logging)
                if(count($this->tret_objects[$tret]->logs)>0)
                    foreach($this->tret_objects[$tret]->logs as $log )
                        $this->tretLog($tret, $log['info'], $log['data']);

            // отладка
            if($this->debug_enabled)
                foreach($this->tret_objects[$tret]->debug_info as $di)
                {
                    $unsafetext = $di['text'];
                    $unsafetext = Lib::safeTagChars($unsafetext, false);
                    $unsafetext = $this->safeBlocks($unsafetext, false);
                    $this->debug($tret, $di['place'], $unsafetext, $di['text']);
                }


        }

        if($this->isOn('dounicode'))
        {
            Lib::convert_html_entities_to_unicode($this->_text);
        }

        $this->_text = Lib::safeTagChars($this->_text, false);
        $this->debug($this, 'unsafe_tag_chars', $this->_text);

        $this->_text = $this->safeBlocks($this->_text, false);
        $this->debug($this, 'unsafe_blocks', $this->_text);

        if(!$this->disable_notg_replace)
        {
            $repl = array('<span class="_notg_start"></span>', '<span class="_notg_end"></span>');
            if($this->remove_notg) $repl = "";
            $this->_text = str_replace( array('<notg>','</notg>'), $repl , $this->_text);
        }

        $this->ok = (count($this->errors)==0);
        return $this->_text;
    }

    /**
     * Получить содержимое <style></style> при использовании классов
     *
     * @param bool $list false - вернуть в виде строки для style или как массив
     * @param bool $compact не выводить пустые классы
     * @return string|array
     */
    public function getStyle($list = false, $compact = false)
    {
        $this->_init();

        $res = array();
        foreach ($this->trets as $tret)
        {
            $arr =$this->tret_objects[$tret]->classes;
            if(!is_array($arr)) continue;
            foreach($arr as $classname => $str)
            {
                if(($compact) && (!$str)) continue;
                $clsname = ($this->class_layout_prefix ? $this->class_layout_prefix : "" ).(isset($this->tret_objects[$tret]->class_names[$classname]) ? $this->tret_objects[$tret]->class_names[$classname] :$classname);
                $res[$clsname] = $str;
            }
        }
        if($list) return $res;
        $str = "";
        foreach($res as $k => $v)
        {
            $str .= ".$k { $v }\n";
        }
        return $str;
    }





    /**
     * Установить режим разметки,
     *   EMT_Lib::LAYOUT_STYLE - с помощью стилей
     *   EMT_Lib::LAYOUT_CLASS - с помощью классов
     *   EMT_Lib::LAYOUT_STYLE|EMT_Lib::LAYOUT_CLASS - оба метода
     *
     * @param int $layout
     */
    public function setTagLayout($layout = Lib::LAYOUT_STYLE)
    {
        $this->use_layout = $layout;
        $this->use_layout_set = true;
    }

    /**
     * Установить префикс для классов
     *
     * @param string|bool $prefix если true то префикс 'emt_', иначе то, что передали
     */
    public function setClassLayoutPrefix($prefix )
    {
        $this->class_layout_prefix = $prefix === true ? "emt_" : $prefix;
    }

    /**
     * Включить/отключить правила, согласно карте
     * Формат карты:
     *    'Название трэта 1' => array ( 'правило1', 'правило2' , ...  )
     *    'Название трэта 2' => array ( 'правило1', 'правило2' , ...  )
     *
     * @param array $map
     * @param boolean $disable если ложно, то $map соотвествует тем правилам, которые надо включить
     *                         иначе это список правил, которые надо выключить
     * @param boolean $strict строго, т.е. те которые не в списку будут тоже обработаны
     */
    public function setEnableMap($map, $disable = false, $strict = true)
    {
        if(!is_array($map)) return;
        $trets = array();
        foreach($map as $tret => $list)
        {
            $tretx = $this->getTret($tret);
            if(!$tretx)
            {
                $this->log("Трэт $tret не найден при применении карты включаемых правил");
                continue;
            }
            $trets[] = $tretx;

            if($list === true) // все
            {
                $tretx->activate(array(), !$disable ,  true);
            } elseif(is_string($list)) {
                $tretx->activate(array($list), $disable ,  $strict);
            } elseif(is_array($list)) {
                $tretx->activate($list, $disable ,  $strict);
            }
        }
        if($strict)
        {
            foreach($this->trets as $tret)
            {
                if(in_array($this->tret_objects[$tret], $trets)) continue;
                $this->tret_objects[$tret]->activate(array(), $disable ,  true);
            }
        }

    }


    /**
     * Установлена ли настройка
     *
     * @param string $key
     */
    public function isOn($key)
    {
        if(!isset($this->settings[$key])) return false;
        $kk = $this->settings[$key];
        return ((strtolower($kk)=="on") || ($kk === "1") || ($kk === true) || ($kk === 1));
    }


    /**
     * Установить настройку
     *
     * @param mixed $selector
     * @param string $setting
     * @param mixed $value
     */
    protected function doSet($selector, $key, $value)
    {
        $tret_pattern = false;
        $rule_pattern = false;
        //if(($selector === false) || ($selector === null) || ($selector === false) || ($selector === "*")) $type = 0;
        if(is_string($selector))
        {
            if(strpos($selector,".")===false)
            {
                $tret_pattern = $selector;
            } else {
                $pa = explode(".", $selector);
                $tret_pattern = $pa[0];
                array_shift($pa);
                $rule_pattern = implode(".", $pa);
            }
        }
        Lib::_process_selector_pattern($tret_pattern);
        Lib::_process_selector_pattern($rule_pattern);
        if($selector == "*") $this->settings[$key] = $value;

        foreach ($this->trets as $tret)
        {
            $t1 = $this->getShortTret($tret);
            if(!Lib::_test_pattern($tret_pattern, $t1))	if(!Lib::_test_pattern($tret_pattern, $tret)) continue;
            $tret_obj = $this->getTret($tret);
            if($key == "active")
            {
                foreach($tret_obj->rules as $rulename => $v)
                {
                    if(!Lib::_test_pattern($rule_pattern, $rulename)) continue;
                    if((strtolower($value) === "on") || ($value===1) || ($value === true) || ($value=="1")) $tret_obj->enableRule($rulename);
                    if((strtolower($value) === "off") || ($value===0) || ($value === false) || ($value=="0")) $tret_obj->disableRule($rulename);
                }
            } else {
                if($rule_pattern===false)
                {
                    $tret_obj->set($key, $value);
                } else {
                    foreach($tret_obj->rules as $rulename => $v)
                    {
                        if(!Lib::_test_pattern($rule_pattern, $rulename)) continue;
                        $tret_obj->set_rule($rulename, $key, $value);
                    }
                }
            }
        }
    }


    /**
     * Установить настройки для тертов и правил
     * 	1. если селектор является массивом, то тогда утсановка правил будет выполнена для каждого
     *     элемента этого массива, как отдельного селектора.
     *  2. Если $key не является массивом, то эта настрока будет проставлена согласно селектору
     *  3. Если $key массив - то будет задана группа настроек
     *       - если $value массив , то настройки определяются по ключам из массива $key, а значения из $value
     *       - иначе, $key содержит ключ-значение как массив
     *
     * @param mixed $selector
     * @param mixed $key
     * @param mixed $value
     */
    public function set($selector, $key , $value = false)
    {
        if(is_array($selector))
        {
            foreach($selector as $val) $this->set($val, $key, $value);
            return;
        }
        if(is_array($key))
        {
            foreach($key as $x => $y)
            {
                if(is_array($value))
                {
                    $kk = $y;
                    $vv = $value[$x];
                } else {
                    $kk = $x;
                    $vv = $y;
                }
                $this->set($selector, $kk, $vv);
            }
        }
        $this->doSet($selector, $key, $value);
    }


    /**
     * Возвращает список текущих третов, которые установлены
     *
     */
    public function getTretsList()
    {
        return $this->trets;
    }

    /**
     * Установка одной метанастройки
     *
     * @param string $name
     * @param mixed $value
     */
    public function doSetup($name, $value)
    {

    }


    /**
     * Установить настройки
     *
     * @param array $setupmap
     */
    public function setup($setupmap)
    {
        if(!is_array($setupmap)) return;

        if(isset($setupmap['map']) || isset($setupmap['maps']))
        {
            if(isset($setupmap['map']))
            {
                $ret['map'] = $test['params']['map'];
                $ret['disable'] = $test['params']['map_disable'];
                $ret['strict'] = $test['params']['map_strict'];
                $test['params']['maps'] = array($ret);
                unset($setupmap['map']);
                unset($setupmap['map_disable']);
                unset($setupmap['map_strict']);
            }
            if(is_array($setupmap['maps']))
            {
                foreach($setupmap['maps'] as $map)
                {
                    $this->setEnableMap
                        ($map['map'],
                            isset($map['disable']) ? $map['disable'] : false,
                            isset($map['strict']) ? $map['strict'] : false
                        );
                }
            }
            unset($setupmap['maps']);
        }


        foreach($setupmap as $k => $v) $this->doSetup($k , $v);
    }




}