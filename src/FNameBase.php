<?php

namespace DF\App\FName;

use DF\App\Helper\RX;

abstract class FNameBase implements FNameValidatorInterface
{
    use GetPropertiesStrict;

    /**
     * Disable placeholders (%) at functions: path, body, ext
     */
    const FLAG_DISABLE_PLACEHOLDER = 1;

    /**
     * Disable smart path handling (removal of adjacent slashes)
     */
    const FLAG_DISABLE_SMARTPATH = 2;

    protected string $placeholderChar = '%';

    protected string $path;
    protected string $body;
    protected string $ext;

    protected string $filenameOriginal;
    protected $flags;

    public function __construct($filename, $flags = null)
    {
        $this->explode($filename);
        $this->validate();

        $this->filenameOriginal = $filename;
        $this->flags = $flags;
    }

    static function make($filename, $flags = null): self
    {
        return new static($filename, $flags);
    }

    static function makeByParts($path, $body, $ext): self
    {
        $fn = new static('');
        $fn->path($path);
        $fn->body($body);
        $fn->ext($ext);

        return $fn;
    }

    protected function validate()
    {
        $this->validatePath($this->path);
        $this->validateBody($this->body);
        $this->validateExtension($this->ext);
    }

    /**
     * Populates filename parts (path, body, ext) by exploding the provided filename
     *
     * @param $filename
     */
    protected function explode($filename)
    {
        $subject = $filename;

        //No path present
        if ( strpos($subject, '/') === false )
            $this->path = '';
        //Get path and remove
        else
            $this->path = (string)RX::pregReturnReplace('~^(?<path>.*/)~', '', $subject, 1);

        //Filename without an extension, filenamebody only
        if ( strpos($subject, '.') === false )
        {
            $this->ext = '';
            $this->body = $subject;

            return;
        }

        //Get extension and remove
        $this->ext = (string)RX::pregReturnReplace('~(?:\.)([^.]+)$~', '', $subject, 1);
        //What remains is the filebody
        $this->body = $subject;
    }

    /**
     * Generates a filename using placeholders:
     *
     * Example filename:
     * /var/www/mancineni_attacks.jpg
     *
     * %A - Full filename => /var/www/mancineni_attacks.jpg
     * %P - Path => /var/www/
     * %B - Filename body => mancineni_attacks
     * %E - Filename extension without dot => jpg
     * %X - Filename extension with dot => .jpg
     *
     * %Pnewfilename%X => /var/www/newfilename.jpg
     *
     * @param string $fstring Generate a filename by using placeholders
     */
    function gen(string $fstring)
    {
        return preg_replace(
            [ '~%A~', '~%P~', '~%B~', '~%E~', '~%X~' ],
            [ (string)$this, $this->path, $this->body, $this->ext, '.' . $this->ext ],
            $fstring);
    }

    function build()
    {
        return $this->path . $this->body . ($this->ext ? '.' . $this->ext : '');
    }

    function set($path, $body, $ext)
    {
        $this->path($path);
        $this->body($body);
        $this->ext($ext);

        return $this;
    }

    function path($path)
    {
        if ($path && $path[-1] != '/')
            $path .= '/';

        $this->parsePathPlaceholder($path, $this->path);
        $this->smartPathCorrection($path);
        $this->validatePath($path);
        $this->path = $path;

        return $this;
    }

    function body($body)
    {
        $this->parsePlaceholder($body, $this->body);
        $this->validateBody($body);
        $this->body = $body;

        return $this;
    }

    function ext($ext)
    {
        $this->parsePlaceholder($ext, $this->ext);

        $parts = explode('.', $ext);

        //if $ext is txt.gz
        if (count($parts) > 1)
        {
            //ext becomes gz
            $ext = array_pop($parts);

            //filebody becomes filebody.txt
            $this->body($this->body . '.' . implode('.', $parts));
        }

        $this->validateExtension($ext);
        $this->ext = $ext;

        return $this;
    }

    protected function parsePlaceholder(&$source, $replacement)
    {
        if (! ($this->flags & self::FLAG_DISABLE_PLACEHOLDER) )
            $source = preg_replace('~'.preg_quote($this->placeholderChar,'~').'~',$replacement, $source);
    }

    protected function parsePathPlaceholder(&$source, $replacement)
    {
        if ($this->flags & self::FLAG_DISABLE_PLACEHOLDER)
            return;

        $r = preg_split('~('.preg_quote($this->placeholderChar,'~').')~',$source,-1, PREG_SPLIT_DELIM_CAPTURE);

        foreach($r as &$part)
            if ($part == $this->placeholderChar)
                $part = $replacement;

        $source = $this->pathJoin(...$r);
    }

    function __toString()
    {
        return $this->build();
    }

    /**
     * Joins path elements together
     *
     * @param mixed ...$elements Path parts
     * @return string
     */
    function pathJoin(...$elements):string
    {
        $s = DIRECTORY_SEPARATOR;
        $path = implode($s, $elements);

        return $path;
    }

    /**
     * Eliminates stacked slashes
     *
     * @param string $path
     */
    private function smartPathCorrection(string &$path)
    {
        if ( !($this->flags & self::FLAG_DISABLE_SMARTPATH ) )
            $path = preg_replace('~/+~','/', $path);
    }
}