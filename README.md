# FName
~ provides an easy way to manipulate filenames. Change paths, filename body and extension in a handy way. 

## Installation
The sure way to include  **FName** in your project is to use  [Composer](https://getcomposer.org/).
```bash
composer require dixflatlinr/fname
```
## The nitty gritty... I'm in a rush
```php
<?php
use \DF\App\FName\FName;
require 'vendor/autoload.php';

$f = FName::make('/easy/path/file.ext');            # /easy/path/file.ext
$f->path('/var/www')->body('%_readme')->ext('txt'); # /var/www/file_readme.txt
```

## General info
A filename has three parts. A **path**, a **filename body**, and an **extension**. You can manipulate each without affecting the others. Each part can be empty.

**PATH**
> `/var/www/`filename.ext
- Always ends in a slash (if omitted, will be inserted)
- Null bytes are not allowed

**FILENAME BODY**
> /var/www/`filename`.ext
- Null bytes and slashes are not allowed

**EXTENSION**
> /var/www/filename.`ext`
- Null bytes, slashes, dots are not allowed

## Examples
```php
<?php
use \DF\App\FName\FName;
require 'vendor/autoload.php';

$filename = '/hevy/path/filebody.ext';
```

### Instancing & querying parts
```php
$f = FName::make($filename);  
$f = new FName($filename);  
$f = FName::makeByParts('/hevy/path/','filebody','ext');  
  
(string)$f; # /hevy/path/filebody.ext
$f->path;   # /hevy/path/
$f->body;   # filebody
$f->ext;    # ext
```
### Manipulating parts
```php
FName::make('/hevy/path/filebody.ext')
->path('/var/www')           # /var/www/filebody.ext
->body('%.indy')             # /var/www/filebody.indy.ext
->ext('mp4');                # /var/www/filebody.indy.mp4

FName::make('')
->set('/','readme','txt');   # /readme.txt
```

### Generate filename using placeholders
```php
FName::make('/var/www/pugs_attacking.jpg')
->gen('%Pnewfilename%X'); # /var/www/newfilename.jpg
/*  
* %A - Full filename => /var/www/pugs_attacking.jpg  
* %P - Path => /var/www/  
* %B - Filename body => pugs_attacking  
* %E - Filename extension without dot => jpg  
* %X - Filename extension with dot => .jpg  
*/
```
### Flags  

#### FLAG_DISABLE_PLACEHOLDER
>Placeholder characters (%) won't be converted to their filename parts 
```php
FName::make('filename.ext', FName::FLAG_DISABLE_PLACEHOLDER)  
 ->body('%leftalone'); # %leftalone.ext  
```
#### FLAG_DISABLE_SMARTPATH
>Adjacent path directory separators will be left as-is  
```php

FName::make('',FName::FLAG_DISABLE_SMARTPATH)  
 ->path('/var/www//slashes///'); #/var/www//slashes///
```

### Quirks
```php
//A path, no filename  
$filename = '/var/www/whatever/';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['/var/www/whatever/','',''], [$f->path, $f->body, $f->ext]);  
  
//A path's last segment without an ending slash is interpreted as a filename  
$filename = '/var/www/whatever';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['/var/www/','whatever',''], [$f->path, $f->body, $f->ext]);  
  
//Full path, with filename, without extension  
$filename = '/var/www/whatever/filename';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['/var/www/whatever/','filename',''], [$f->path, $f->body, $f->ext]);  
  
//Full path, with filename and extension  
$filename = '/var/www/whatever/filename.ext';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['/var/www/whatever/','filename','ext'], [$f->path, $f->body, $f->ext]);
```

```php
//double dots is a filename without extension (body only)  
$filename = '..';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['','..',''], [$f->path, $f->body, $f->ext]);  
  
//single dot is a filename without extension (body only)  
$filename = '.';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['','.',''], [$f->path, $f->body, $f->ext]);  
  
//lots of dots is still considered a filename without extension (body only)  
$filename = '......';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['','......',''], [$f->path, $f->body, $f->ext]);  
  
//lots of dots as body with an added extension - last dot is always consumed when separating the extension  
$filename = '......ext';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing(['','.....','ext'], [$f->path, $f->body, $f->ext]);  

//Multiple dots in filename
$filename = '/var/www/whatever/flying...pugs.plus.the.birds...ext';  
$f = new FName($filename);  
$this->assertEqualsCanonicalizing  
(  
 ['/var/www/whatever/','flying...pugs.plus.the.birds..','ext'],  
  [$f->path, $f->body, $f->ext]  
);
```
