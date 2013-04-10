<?php

namespace Laiz\Template;

class Template
{
    protected $templateDir = 'template';
    protected $cacheDir = 'cache';
    private $ext = 'html';
    private $vars;
    private $path;

    public function __construct($templateDir = null, $cacheDir = null)
    {
        if ($templateDir)
            $this->templateDir = $templateDir;
        if ($cacheDir)
            $this->cacheDir;

        if (!is_writeable($this->cacheDir))
            throw new \RuntimeException("$cacheDir directory is not writeable.");

        $this->vars = new \StdClass();
    }
    public function setExtension($ext)
    {
        $this->ext = $ext;
    }
    public function setVars($vars)
    {
        $this->vars = $vars;
    }
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    public function getPath()
    {
        if ($this->path !== null)
            return $this->path;
        return basename($_SERVER['SCRIPT_FILENAME'], '.php');
    }
    public function show($vars = null)
    {
        if ($vars === null)
            $vars = $this->vars;
        $file = $this->getPath() . '.' . $this->ext;
        $tmplFile = $this->templateDir . '/' . $file;
        $cacheFile = $this->cacheDir . '/' . $file;

        $this->compile($tmplFile, $cacheFile);

        $this->showCache($cacheFile, $vars);
    }
    protected function compile($tmplFile, $cacheFile)
    {
        if (file_exists($cacheFile) && filemtime($tmplFile) <= filemtime($cacheFile)){
            return;
        }

        if (!file_exists($tmplFile))
            throw new \RuntimeException("$tmplFile not found.");

        $tmpl = file_get_contents($tmplFile);

        // include feature
        $incPattern = '|{include:([[:alnum:]/]+\.html)}|';
        if (preg_match($incPattern, $tmpl)){
            $incReplace =
                '<?php $this->compile(\'' .
                $this->templateDir . '/$1\', \'' .
                $this->cacheDir . '/$1\'' . ');' .
                ' include \'' . $this->cacheDir . '/' . '$1\'; ?>';
            $tmpl = preg_replace($incPattern, $incReplace, $tmpl);
        }

        // simple variables
        $tmpl = preg_replace('/(\{[[:alnum:]]+)\.([[:alnum:]]+(:[a-z]+)?\})/', '$1->$2', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):h\}/', '<?php echo $$1; ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):b\}/', '<?php echo nl2br(htmlspecialchars($$1)); ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*)\}/', '<?php echo htmlspecialchars($$1); ?>', $tmpl);

        $this->file_force_contents($cacheFile, $tmpl);
    }

    // http://php.net/function.file-put-contents.php#84180
    protected function file_force_contents($path, $contents,
                                           $flag = 0, $context = null)
    {
        $parts = explode('/', $path);
        $file = array_pop($parts);
        $dir = '.';
        foreach($parts as $part)
            if(!is_dir($dir .= "/$part")) mkdir($dir);
        return file_put_contents("$dir/$file", $contents, $flag, $context);
    }
    private function showCache($__cacheFile__, $__vars__)
    {
        foreach ($__vars__ as $k => $v)
            $$k = $v;
        include $__cacheFile__;
    }
}
