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
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','','','',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //A path's last segment without an ending slash is interpreted as a filename
        $filename = '/var/www/whatever';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/','whatever','','whatever',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //Full path, with filename, without extension
        $filename = '/var/www/whatever/filename';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','filename','','filename',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //Full path, with filename and extension
        $filename = '/var/www/whatever/filename.ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/whatever/','filename','ext','filename.ext','ext'], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //Full path, with filename and compound extension
        $filename = '/var/www/what.ever/.../filename.1.2.3.ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['/var/www/what.ever/.../','filename.1.2.3','ext','filename.1.2.3.ext','1.2.3.ext'], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);
    }

    public function test_FName__Weirdos(): void
    {
        //double dots is a filename without extension (body only)
        $filename = '..';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','..','','..',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //single dot is a filename without extension (body only)
        $filename = '.';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','.','','.',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //lots of dots is still considered a filename without extension (body only)
        $filename = '......';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','......','','......',''], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //lots of dots as body with an added extension - last dot is always consumed when separating the extension
        $filename = '......ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing(['','.....','ext','......ext','ext'], [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]);

        //Multiple dots in filename
        $filename = '/var/www/what.ever/manci...neni.meg.a.madarak...ext';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing
        (
            ['/var/www/what.ever/','manci...neni.meg.a.madarak..','ext','manci...neni.meg.a.madarak...ext','ext'],
            [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]
        );

        //Unicode madness...
        $filename = '/var/w™ww/whateverḊḋḞ/very😎😏...ne🌎🌏ni.meg.a.mУФХadarak...ぴふべ';
        $f = new FName($filename);
        $this->assertEqualsCanonicalizing
        (
            ['/var/w™ww/whateverḊḋḞ/','very😎😏...ne🌎🌏ni.meg.a.mУФХadarak..','ぴふべ','very😎😏...ne🌎🌏ni.meg.a.mУФХadarak...ぴふべ','ぴふべ'],
            [$f->path, $f->body, $f->ext, $f->filename, $f->extLong]
        );
    }

    public function test_FName__Generate(): void
    {
        //Placeholder testing
        $filename = '/var/lib/mysql/path.unc/multi-master.info.ext';
        $f = new FName($filename);

        $this->assertEquals($filename, $f->gen('%A'));

        $this->assertEqualsCanonicalizing(
            [$f->gen('%P'),$f->gen('%B'),$f->gen('%E'),$f->gen('%X'), $f->gen('%F'), $f->gen('%L')],
            [$f->path, $f->body, $f->ext, '.' . $f->ext, 'multi-master.info.ext','info.ext']);

        $this->assertEquals('/var/lib/mysql/path.unc/verynew_multi-master.info_Filename.ext', $f->gen('%Pverynew_%B_Filename%X'));

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

        //Filename - manipulate ext and body together, by providing a filename
        $filename = 'var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $this->assertEquals('multi-master.info', $f->filename);

        $f->filename('another.cool.filename');
        $this->assertEquals('another.cool.filename', $f->filename);
        $this->assertEquals('var/lib/mysql/', $f->path);
        $this->assertEquals('another.cool', $f->body);
        $this->assertEquals('filename', $f->ext);

        $f->filename('.....');
        $this->assertEquals(['var/lib/mysql/', '.....', '', '.....'], [$f->path, $f->body, $f->ext, $f->filename]);

        $f->filename('.....ext');
        $this->assertEquals(['var/lib/mysql/', '....', 'ext', '.....ext'], [$f->path, $f->body, $f->ext, $f->filename]);
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
        $filename = '/var/lib/mysql/multi-master.info.2';
        $fn = FName::make($filename);
        $this->assertEquals($filename, (string)$fn);

        $newFilename = '/totally/new/filename.ext';
        $this->assertEquals($newFilename, (string)$fn->reset($newFilename));

        //Upon reseting the instance, make sure that all parts
        //even ones that are not present in the new filename have proper values

        $this->assertEquals(
            ['/totally/new/','filename','ext','filename.ext','ext'],
            [$fn->path, $fn->body, $fn->ext, $fn->filename, $fn->extLong]);

        $fn->reset('/just/a.h/path/');
        $this->assertEquals(
            ['/just/a.h/path/','','','',''],
            [$fn->path, $fn->body, $fn->ext, $fn->filename, $fn->extLong]);

        $fn->reset('filename.long.ext');
        $this->assertEquals(
            ['','filename.long','ext','filename.long.ext','long.ext'],
            [$fn->path, $fn->body, $fn->ext, $fn->filename, $fn->extLong]);
    }

    public function test_FName__pathParts(): void
    {
        //Stacked slashes will be removed - per smartpath handling
        $s = '/very/long/path////alma.dot/tralalaa/muu/file.ext1.ext2.ext3';
        $f = new FName($s);
        $this->assertEquals('/very/long/path/alma.dot/tralalaa/muu/', $f->pathParts());

        //First part inherits the leading slash, if present
        $this->assertEquals(
            ['/very/','long/','path/','alma.dot/','tralalaa/','muu/'],
            $f->pathParts(0, null, true, true));

        //Leading slash added to first element
        $this->assertEquals(
            ['very','long','path','alma.dot','tralalaa','muu'],
            $f->pathParts(0, null, true, false));

        $this->assertEquals(
            ['long','path','alma.dot','tralalaa'],
            $f->pathParts(1, -1, true, false));

        $this->assertEquals(
            ['muu'],
            $f->pathParts(-1, null, true, false));

        $this->assertEquals(
            'tralalaa/muu/',
            $f->pathParts(-2, null));

        $this->assertEquals(
            '/very/long/path/alma.dot/',
            $f->pathParts(0,-2));

        $this->assertEquals(
            'long/path/alma.dot/',
            $f->pathParts(1,-2));

        $this->assertEquals(
            '/very/long/',
            $f->pathParts(0,2));

        $s = 'very/long/path////alma.dot/tralalaa/muu/';
        $f = new FName($s);
        $this->assertEquals(
            ['very/','long/','path/','alma.dot/','tralalaa/','muu/'],
            $f->pathParts(0, null, true, true));
        $this->assertEquals('very/long/',$f->pathParts(0,2));
    }

    public function test_FName__Placeholders(): void
    {
        //Changing filename
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $f->filename('%2');
        $this->assertEquals('/var/lib/mysql/multi-master.info2', (string)$f);

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
        //no wildcards

        //Changing filename
        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename, FName::FLAG_DISABLE_PLACEHOLDER);
        $f->filename('%2');
        $this->assertEquals('/var/lib/mysql/%2', (string)$f);

        //Changing extension
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

    public function test_FName__Filename_Exception_Invalid_chars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $filename = '/var/lib/mysql/multi-master.info';
        $f = new FName($filename);
        $f->filename('/var/lib/whatever');
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
