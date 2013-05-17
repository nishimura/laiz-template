<?php

namespace Laiz\Template;

require_once 'Template.php';

class Parser extends Template
{
    private $tagOpened = false;
    private $attrOpend = false;
    private $behaviors = array('b' => 'nl2br',
                               'n' => 'number_format',
                               'u' => 'urlencode');
    private $plains = array('h', 'a');
    private $tagStack = array();
    public function __construct($templateDir = null, $cacheDir = null)
    {
        parent::__construct($templateDir, $cacheDir);
    }

    public function addBehavior($char, $callback, $isPlain = false)
    {
        $this->behaviors[$char] = $callback;
        if ($isPlain)
            $this->plains[] = $char;
    }
    private function parse($buf)
    {
        $length = strlen($buf);
        $ret = '';
        for ($i = 0; $i < $length;){
            $char = $buf[$i];

            if ($char === '<'){
                list($parsed, $len) = $this->parseTag(substr($buf, $i));
            }else if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($buf, $i));
            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }
        return $ret;
    }

    private function parseAttr($tagName, $close, $buf)
    {
        if (!preg_match("/^$close([^$close]*)$close/", $buf, $matches))
            return array($buf[0], 1);

        $val = $matches[1];
        $length = strlen($val);

        $ret = '';
        $append = null;

        for ($i = 0; $i < $length;){
            $char = $val[$i];

            if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($val, $i));

            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }

        return array($close . $ret . $close, $length + 2, $append);
    }

    private function pushPhp($phpcode, $before = false)
    {
        $name = $before ? 'before' : 'after';
        $this->tagStack[count($this->tagStack)-1][$name] =
            '<?php ' . $phpcode . "\n?>\n"
            . $this->tagStack[count($this->tagStack)-1][$name];
    }

    private function popTag($closeTag)
    {
        $index = count($this->tagStack) - 1;
        for ($i = $index; $i >= 0; $i--){
            $tag = array_pop($this->tagStack);
            if ($tag['tag'] === $closeTag){
                return array($tag['before'], $tag['after']);
            }
        }
        return array('', '');
    }
    private function parseTag($buf)
    {
        if (preg_match('|^</ *([[:alnum:]]+) *>|', $buf, $matches)){
            // close tag
            $tagName = $matches[1];
            list($before, $after) = $this->popTag($tagName);
            return array($before . $matches[0] . $after, strlen($matches[0]));
        }
        if (!preg_match('/^< *([[:alnum:]]+)/', $buf, $matches))
            return array($buf[0], 1);

        $tagName = $matches[1];
        $this->tagStack[] = array('tag' => $tagName,
                                  'before' => '', 'after' => '');

        $length = strlen($buf);
        $ret = '';

        $loop = '/^laiz:loop="([[:alnum:]._]+):([[:alnum:]_]+)(:[[:alnum:]_]+)?"/';
        $if = '/^laiz:if(el)?="([[:alnum:]._]+)"/';

        $php = '';
        $form = array('parse' => false,
                      'laiz:form' => null,
                      'type' => '',
                      'name' => '',
                      'value' => '',
                      'valueRaw' => '');
        for ($i = 0; $i < $length;){
            $char = $buf[$i];

            $sub = substr($buf, $i);
            if (preg_match($loop, $sub, $m)){

                $ite = str_replace('.', '->', $m[1]);
                if (isset($m[3])){
                    $v = ltrim($m[3], ':');
                    $php .= "<?php foreach(\$$ite as \$$m[2]=>\$$v): ?>\n";
                }else{
                    $php .= "<?php foreach(\$$ite as \$$m[2]): ?>\n";
                }
                $len = strlen($m[0]);
                $this->pushPhp('endforeach;');
                $parsed = '';

            }else if (preg_match($if, $sub, $m)){
                if ($m[1]){
                    $this->pushPhp('else:');
                }else{
                    $this->pushPhp('endif;');
                }
                $v = str_replace('.', '->', $m[2]);
                $php .= "<?php if (isset(\$$v) && \$$v): ?>\n";
                $len = strlen($m[0]);
                $parsed = '';

            }else if ($this->startsWith($sub, 'laiz:else')){
                $this->pushPhp('endif;');
                $len = strlen('laiz:else');
                $parsed = '';

            }else if (preg_match('/^laiz:form(="[[:alnum:]\.:_]+")?/', $sub, $m)){
                $form['parse'] = true;
                $len = strlen($m[0]);
                if (strtolower($tagName) === 'select')
                    $form['type'] = 'select';
                if (isset($m[1]))
                    $form['laiz:form'] = trim($m[1], '="');
                $parsed = '';

            }else if (preg_match('/^(type=|name=|value=)/', $sub, $m)){
                $ret .= $m[1];
                $i += strlen($m[1]);
                $char = $buf[$i];

                list($parsed, $len, $append) =
                    $this->parseAttr($tagName, $char, substr($buf, $i));
                if ($append)
                    $ret = $append . $ret;
                $form[trim($m[1], '=')] = htmlspecialchars_decode(trim($parsed, $char));
                if ($m[1] === 'value=')
                    $form['valueRaw'] = trim(substr($buf, $i, $len), '"');

            }else if ($char === '"' || $char === "'"){
                list($parsed, $len, $append) =
                    $this->parseAttr($tagName, $char, substr($buf, $i));
                if ($append)
                    $ret = $append . $ret;

            }else if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($buf, $i));

            }else if ($this->startsWith($sub, '/>')){
                $ret .= '/>';
                $i += 2;

                list($before, $after) = $this->popTag($tagName);
                $ret = $before . $ret . $after;
                break;

            }else if ($char === '>'){
                $ret .= $char;
                $i++;
                break;
            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }

        if ($form['parse'])
            $ret = $this->parseForm($ret, $form);

        return array($php . $ret, $i);
    }
    private function parseForm($tagStr, $form)
    {
        if (!isset($form['value']))
            return $tagStr;
        if ($this->startsWith($form['value'], '<?php')){
            if ($form['valueRaw'][0] === '{' &&
                $form['valueRaw'][strlen($form['valueRaw']) - 1] === '}'){
                $formValue = '$' . str_replace('.', '->', substr($form['valueRaw'], 1,
                                                                 strlen($form['valueRaw']) - 2));
            }else{
                return $tagStr;
            }
        }else{
            $formValue = "'" . str_replace("'", "\\'", $form['value']) . "'";
        }

        if (substr($tagStr, -2) === '/>'){
            $ret = substr($tagStr, 0, strlen($tagStr) - 2);
            $close = '/>';
        }else{
            $ret = substr($tagStr, 0, strlen($tagStr) - 1);
            $close = '>';
        }
        $value = '';

        switch ($form['type']){
        case 'checkbox':
        case 'radio':
            $val = $this->nameToValue($form['name']);
            $value = "<?php if(isset($val) && ((is_array($val) && in_array($formValue, $val)) || ($val === true || $val == $formValue))) echo ' checked=\"checked\"';?>\n";
            break;

        case 'select':
            if (!isset($form['laiz:form']))
                break;
            $name = '$' . str_replace('.', '->', $form['laiz:form']);
            $keyStr = '$__laizTemplateKey__';
            $valStr = '$__laizTemplateValue__';
            if (strpos($name, ':') !== false){
                list($a, $key, $value) = explode(':', $name);
                $name = $a;
                $keyStr = "$valStr->$key";
                $valStr .= "->$value";
            }

            $val = $this->nameToValue($form['name']);
            $php = "if(isset($name) && (is_array($name) || $name instanceof \\Traversable)){ foreach($name as \$__laizTemplateKey__ => \$__laizTemplateValue__){"
                . 'echo "<option value=\"";'
                . "echo $keyStr . '\"';"
                . "if ($keyStr == $val)"
                . '  echo " selected=\"selected\"";'
                . "echo \">$valStr</option>\"; }}";

            $this->pushPhp($php, true);
            break;

        default:
            break;
        }
        return $ret . $value . $close;
    }

    private function nameToValue($name)
    {
        $name = str_replace('[]', '', $name);
        $name = str_replace('[', '->', $name);
        $name = str_replace(']', '', $name);
        return '$' . $name;
    }

    private function parseVal($buf)
    {
        if (!preg_match('|^{([[:alnum:]\.:/_]+)}|', $buf, $matches))
            return array($buf[0], 1);

        $name = $matches[1];
        $len = strlen($name) + 2;

        // special replacement
        if (preg_match('|^include:([[:alnum:]/]+\.html)|', $name, $matches)){
            $file = $matches[1];
            $templateFile = $this->templateDir . "/$file";
            $cacheFile = $this->cacheDir . "/$file";
            $ret = '<?php $__laizTemplateParser__ = new Laiz\Template\Parser();'
                . '$__laizTemplateParser__->compile('
                . "'$templateFile', '$cacheFile');"
                . "include '$cacheFile'; ?>\n";
            return array($ret, $len);
        }

        // variables replacement
        $name = str_replace('.', '->', $name);
        if (strpos($name, ':') !== false){
            list($name, $behaviors) = explode(':', $name);
            $val = '$' . $name;

            $isPlain = false;
            foreach ($this->plains as $p){
                if (strpos($behaviors, $p) !== false){
                    $isPlain = true;
                    break;
                }
            }
            if (!$isPlain)
                $val = 'htmlspecialchars($' . $name . ')';

            foreach ($this->behaviors as $behavior => $callback){
                if (strpos($behaviors, $behavior) !== false){
                    $val = $callback . '(' . $val . ')';
                }
            }
        }else {
            $val = 'htmlspecialchars($' . $name . ')';
        }
        $ret = '<?php echo ' .  $val . "; ?>\n";
        return array($ret, $len);
    }
    protected function compileInternal($tmplFile, $cacheFile)
    {
        $tmpl = file_get_contents($tmplFile);
        $tmpl = $this->parse($tmpl);
        return $tmpl;
    }

    private function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle, 0) === 0;
    }
    private function endsWith($haystack, $needle){
        $length = (strlen($haystack) - strlen($needle));
        if($length < 0)
            return false;
        return strpos($haystack, $needle, $length) !== false;
    }
}
