# XImage
image processing library for PHP

## Converting an image from TGA to BMP
```
include 'XImage.php';

$img = new XImage();
$img->load('test.tga')->save('test.bmp');
```
