<?php

declare(strict_types=1);

use DF\App\FName\FName;
use PHPUnit\Framework\TestCase;

final class FNameTest extends TestCase
{
    public function setUp(): void
    {

    }

    public function test_FName(): void
    {
        //A path, no filename
        $filename = '/var/www/whatever/';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','','',''], [$f->path, $f->body, $f->ext, $f->filename]);

        //A path's last segment without an ending slash is interpreted as a filename
        $filename = '/var/www/whatever';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/','whatever','','whatever'], [$f->path, $f->body, $f->ext, $f->filename]);

        //Full path, with filename, without extension
        $filename = '/var/www/whatever/filename';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','filename','','filename'], [$f->path, $f->body, $f->ext, $f->filename]);

        //Full path, with filename and extension
        $filename = '/var/www/whatever/filename.ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','filename','ext','filename.ext'], [$f->path, $f->body, $f->ext, $f->filename]);
    }

    public function test_FName__Weirdos(): void
    {
        //double dots is a filename without extension (body only)
        $filename = '..';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','..','','..'], [$f->path, $f->body, $f->ext, $f->filename]);

        //single dot is a filename without extension (body only)
        $filename = '.';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','.','','.'], [$f->path, $f->body, $f->ext, $f->filename]);

        //lots of dots is still considered a filename without extension (body only)
        $filename = '......';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','......','','......'], [$f->path, $f->body, $f->ext, $f->filename]);

        //lots of dots as body with an added extension - last dot is always consumed when separating the extension
        $filename = '......ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','.....','ext','......ext'], [$f->path, $f->body, $f->ext, $f->filename]);

        //Multiple dots in filename
        $filename = '/var/www/whatever/manci...neni.meg.a.madarak...ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing
        (
            ['/var/www/whatever/','manci...neni.meg.a.madarak..','ext','manci...neni.meg.a.madarak...ext'],
            [$f->path, $f->body, $f->ext, $f->filename]
        );

        //Unicode madness...
        $filename = '/var/w™ww/whateverḊḋḞ/very😎😏...ne🌎🌏ni.meg.a.mУФХadarak...ぴふべ';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing
        (
            ['/var/w™ww/whateverḊḋḞ/','very😎😏...ne🌎🌏ni.meg.a.mУФХadarak..','ぴふべ','very😎😏...ne🌎🌏ni.meg.a.mУФХadarak...ぴふべ'],
            [$f->path, $f->body, $f->ext, $f->filename]
        );
    }

    public function test_FName__Generate(): void
    {
        //Placeholder testing
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);

        $this->assertEquals($filename, $f->gen('%A'));

        $this->assertEqualsCanonicalizing(
            [$f->gen('%P'),$f->gen('%B'),$f->gen('%E'),$f->gen('%X'), $f->gen('%F')],
            [$f->path, $f->body, $f->ext, '.' . $f->ext, 'multi-master.info']);

        $this->assertEquals('/var/lib/mysql/verynew_multi-master_Filename.info', $f->gen('%Pverynew_%B_Filename%X'));

        //Using %X on a filename without extension
        $filename = '/var/lib/mysql/filebodyonly';
        $f = new FName($filename);

        $this->assertEqualsCanonicalizing(
            [
                $f->gen('%X'), $f->gen('%B%X')
            ],
            [
                '','filebodyonly'
            ]
        );
    }

    public function test_FName__Elements(): void
    {
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $this->assertEquals('multi-master.info', $f->filename);

        //Changing extension
        $f->ext('data');
        $this->assertEquals('/var/lib/mysql/multi-master.data', (string)$f);
        $this->assertEquals('multi-master.data', $f->filename);

        //Changing path
        $f->path('');
        $this->assertEquals('multi-master.data', (string)$f);
        $this->assertEquals('multi-master.data', $f->filename);

        //Removing extension
        $f->ext('');
        $this->assertEquals('multi-master', (string)$f);
        $this->assertEquals('multi-master', $f->filename);

        //Removing body, the file will be very empty, this is also possible
        $f->body('');
        $this->assertEquals('', (string)$f);
        $this->assertEquals('', $f->filename);


        //Rebuilding file - path must always end with a slash, and path must provide this
        $f->path('/x/y')->body('filebody')->ext('ext');
        $this->assertEquals('/x/y/filebody.ext', (string)$f);
        $this->assertEquals('filebody.ext', $f->filename);

        //Rebuilding file with set shorthand
        $f->set('/a/b/','c','d');
        $this->assertEquals('/a/b/c.d', (string)$f);
        $this->assertEquals('c.d', $f->filename);
    }

    public function test_FName__Static_constructor(): void
    {
        $filename = '/var/lib/mysql/multi-master.info';
        $this->assertEquals($filename, (string)FName::make($filename));

        $filename = '/var/lib/mysql/multi-master.info';
        $this->assertEquals($filename, (string)FName::makeByParts('/var/lib/mysql','multi-master','info'));
    }

    public function test_FName__Reset(): void
    {
        $filename = '/var/lib/mysql/multi-master.info';
        $fn = FName::make($filename);
        $this->assertEquals($filename, (string)$fn);

        $newFilename = '/totally/new/filename.ext';
        $this->assertEquals($newFilename, (string)$fn->reset($newFilename));
    }

    public function test_FName__Placeholders(): void
    {
        //Changing extension
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $f->ext('%2');
        $this->assertEquals('/var/lib/mysql/multi-master.info2', (string)$f);

        $f = new FName($filename);
        $f->ext('%.gz');
        $this->assertEquals('multi-master.info', (string)$f->body);
        $this->assertEquals('gz', (string)$f->ext);
        $this->assertEquals('/var/lib/mysql/multi-master.info.gz', (string)$f);

        //Changing path
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $f->path('%deeper/');
        $this->assertEquals('/var/lib/mysql/deeper/multi-master.info', (string)$f);

        //Changing body
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $f->body('%-megaking');
        $this->assertEquals('/var/lib/mysql/multi-master-megaking.info', (string)$f);
    }

    public function test_FName__Placeholders_disabled(): void
    {
        //Changing extension - no wildcards
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename, FName::FLAG_DISABLE_PLACEHOLDER);
        $f->ext('%2');
        $this->assertEquals('/var/lib/mysql/multi-master.%2', (string)$f);

        //Changing path
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename, FName::FLAG_DISABLE_PLACEHOLDER);
        $f->path('%deeper/');
        $this->assertEquals('%deeper/multi-master.info', (string)$f);

        //Changing body
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename, FName::FLAG_DISABLE_PLACEHOLDER);
        $f->body('%-megaking');
        $this->assertEquals('/var/lib/mysql/%-megaking.info', (string)$f);
    }

    public function test_FName__Smartpath_disabled(): void
    {
        //Changing extension - no wildcards
        $path = '/very//cluttered///path/uh';
        $f = (new FName('', FName::FLAG_DISABLE_SMARTPATH))->path($path);

        //Note that the ending slash is always added
        $this->assertEquals('/very//cluttered///path/uh/', (string)$f);
    }

    public function test_FName__Exception_Invalid_chars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $filename = '/var/lib/mysql/multi-mas'.chr(0).'ter.info';
        $f = new FName($filename);
    }

    function extensionValues()
    {
        return [['ex/ty'],['ex'.chr(0).'ty']];
    }

    /**
     * @dataProvider extensionValues
     */
    public function test_FName__Exception_Invalid_extension($errValue): void
    {
        $this->expectException(InvalidArgumentException::class);

        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);

        $f->ext($errValue);
    }

    public function test_FName__Exception_Invalid_body(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);

        $f->body('ex' . chr(0) . 't/y');
    }
}
