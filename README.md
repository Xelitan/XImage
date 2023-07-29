# XImage
image processing library for PHP

## Features
- requires no installation, no other libraries, no ImageMagick, just PHP GD
- reads and writes TGA, BMP, PNM, PPM, PGM, PBM and all formats supported by PHP GD (jpg, gif, png...)
- lots of filters/effects: sepia, vibrance, contrast, posterize, emboss, sharpen, blur...
- can convert to grayscale and black&white using various dithering methods (Stucki, Sierra, Burkes, Floyd Steinberg...)

## Converting an image from TGA to BMP
```
include 'XImage.php';

$img = new XImage();
$img->load('test.tga')->save('test.bmp');
```
## Applying an effect
```
include 'XImage.php';

$img = new XImage();
$img->load('test.jpg')->scale(2)->sepia()->roundCorner(30)->save('test.png');
```
