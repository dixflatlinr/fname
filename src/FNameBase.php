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
    protected string $filename; //Helper so filebody+ext (basename) can be easily accessed
    protected string $extLong; //Helper: long extension (compounded.filename.ext1.2.3 => ext1.2.3)

    protected string $filenameOriginal;
    protected $flags;

    public function __construct($filename, $flags = null)
    {
        $this->reset($filename, $flags);
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

    function reset($filename, $flags = null)
    {
        $this->path = '';
        $this->body = '';
        $this->ext = '';
        $this->filename = '';
        $this->extLong = '';

        $this->explode($filename);
        $this->validate();

        $this->filenameOriginal = $filename;
        $this->flags = $flags;

        return $this;
    }

    /**
     * Populates filename parts (path, body, ext) by exploding the provided filename
     *
     * @param $filename
     */
    protected function explode($filename)
    {
        $leftover = '';
        [$path, $leftover] = $this->getPath($filename);
        [$body, $ext] = $this->getFileParts($leftover);

        $this->smartPathCorrection($path);

        $this->path($path);
        $this->body($body);
        $this->ext($ext);

        $this->feedHelperProperties();
    }

    protected function validate()
    {
        $this->validatePath($this->path);
        $this->validateBody($this->body);
        $this->validateExtension($this->ext);

        $this->validateBasename($this->ext);
        $this->validateLongExtension($this->extLong);
    }

    /**
     * Returns the compound extension  (filebody.ext1.2.3 => ext1.2.3)
     *
     * @return array|mixed|null
     */
    protected function getLongExtension():string
    {
        return RX::pregReturn('~(?:\.)([^/.]+(\.[^/.]+)*)$~', $this->filename, 1) ?? '';
    }

    /**
     * Get file body and extension from a filename
     *
     * @param string $filename
     * @return string ['body', 'ext']
     */
    protected function getFileParts(string $filename):array
    {
        $leftover = $filename;

        //Filename without an extension, filenamebody only
        if ( strpos($filename, '.') === false )
        {
            $ext = '';
            $body = $leftover;
        }
        else
        {
            //Get extension and remove
            $ext = (string)RX::pregReturnReplace('~(?:\.)([^.]+)$~D', '', $leftover, 1);
            //What remains is the filebody
            $body = $leftover;
        }

        return [$body, $ext];
    }

    /**
     * Get path part of filename and the pathless filename (leftovers)
     *
     * @param $filename
     * @return array ['filename', 'pathlessFilename']
     */
    protected function getPath(string $filename): array
    {
        $leftover = $filename;

        //No path present
        if ( strpos($leftover, '/') === false )
            $path = '';
        //Get path and remove
        else
            $path = (string)RX::pregReturnReplace('~^(?<path>.*/)~', '', $leftover, 1);

        return [$path, $leftover];
    }

    /**
     * Generates a filename using placeholders:
     *
     * Example filename:
     * /var/www/mancineni_attacks.ext.jpg
     *
     * %A - Full filename => /var/www/mancineni_attacks.jpg
     * %P - Path => /var/www/
     * %F - Filename (body and ext) => mancineni_attacks.jpg
     * %B - Filename body => mancineni_attacks
     * %E - Filename extension without dot => jpg
     * %X - Filename extension with dot => .jpg
     * %L - Filename long extension => ext.jpg
     *
     * %Pnewfilename%X => /var/www/newfilename.ext.jpg
     *
     * @param string $fstring Generate a filename by using placeholders
     */
    function gen(string $fstring)
    {
        //opt: all the values here are auto acquired for each substitution
        return preg_replace(
            [ '~%A~', '~%P~', '~%F~', '~%B~', '~%E~', '~%X~','~%L~'],
            [ (string)$this, $this->path, $this->filename,  $this->body, $this->ext, ($this->ext ? '.' : '') . $this->ext, $this->extLong ],
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
        $this->feedHelperProperties();

        return $this;
    }

    function body($body)
    {
        $this->parsePlaceholder($body, $this->body);
        $this->validateBody($body);
        $this->body = $body;
        $this->feedHelperProperties();

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
        $this->feedHelperProperties();

        return $this;
    }

    function filename($filename)
    {
        $this->parsePlaceholder($filename, $this->filename);

        $this->validateBasename($filename);
        [$path, $leftover] = $this->getPath($filename);
        [$this->body, $this->ext] = $this->getFileParts($leftover);

        $this->feedHelperProperties();
    }

    /**
     * Get parts of a path. Works like array_slice
     *
     * @param int $offset
     * @param int|null $length
     * @param bool $asArray Return the path parts as an array
     * @param bool $withSlashes when asArray=true, whether array items will include slashes
     * @return false|string|string[]
     */
    function pathParts(int $offset = 0, ?int $length = null, bool $asArray = false, bool $withSlashes = true)
    {
        $d = DIRECTORY_SEPARATOR;

        $rx = "~({$d}?[^{$d}]+{$d})~";
        $parts = RX::pregReturnAll($rx, $this->path, 0);

        $out = array_slice($parts, $offset, $length);

        if ($asArray)
            if ($withSlashes === false)
                return array_map(function($v){ return preg_replace('~/~is','',$v); }, $out);
            else
                return $out;

        $pathNew = implode('', $out);
        $this->validatePath($pathNew);

        return $pathNew;
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

    /**
     * Populates helper vars, like, filename
     */
    public function feedHelperProperties()
    {
        $this->filename = $this->body . ($this->ext ? '.' : '') . $this->ext;
        $this->extLong = $this->getLongExtension();
    }

    /**
     * Returns an array of all of the file parts
     *
     * @return array Of file parts
     */
    function getParts()
    {
        return
            [
                'path' => $this->path,
                'body' => $this->body,
                'ext' => $this->ext,
                'filename' => $this->filename,
            ];
    }
}