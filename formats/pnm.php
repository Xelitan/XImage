<?php
//License: GNU GPL
//Version: 1.0
//Copyright (c) Xelitan.com

class XImage_PNM
{
	static function load($filename)
	{
		$f = @file_get_contents($filename);
		if ($f == '')
		{
			die('Invalid file');
		}

		$offset = 0;
		$id = substr($f, $offset, 2);
		$offset += 3;

		$isPBM = $isPGM = $isPPM = false;
		
		if ($id == 'P1' or $id == 'P4') $isPBM = true;
		else if ($id == 'P2' or $id == 'P5') $isPGM = true;
		else if ($id == 'P3' or $id == 'P6') $isPPM = true;
		
		$pos = strpos($f, ' ', $offset);
		$wid = 1*substr($f, $offset, $pos-$offset);
		$offset = $pos+1;
		
		$pos = strpos($f, "\n", $offset);
		$hei = substr($f, $offset, $pos-$offset);		
		$offset = $pos+1;

		if (!$isPBM)
		{
			$pos = strpos($f, "\n", $offset);
			$max = substr($f, $offset, $pos-$offset);
			$offset = $pos+1;
		}
		
		$wid *= 1;
		$hei *= 1;
		
		$im = imagecreatetruecolor($wid, $hei);
		
		if ($isPPM)
		{
			for ($y=0; $y<$hei; $y++)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$r = ord($f[$offset]);
					$g = ord($f[$offset+1]);
					$b = ord($f[$offset+2]);
					$offset += 3;
					
					$rgb = ($r << 16) + ($g << 8) + $b;
					
					imagesetpixel($im, $x, $y, $rgb);								
				}
			}
		}
		else if ($isPGM)
		{
			for ($y=0; $y<$hei; $y++)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$g = ord($f[$offset]);
					$offset++;
					
					$rgb = ($g << 16) + ($g << 8) + $g;
					
					imagesetpixel($im, $x, $y, $rgb);								
				}
			}
		}
		else
		{
			$palette = array(0xFFFFFF, 0x000000);
			for ($y=0; $y<$hei; $y++)
			{
				for ($x=0; $x<$wid; $x+=8)
				{
					$g = ord($f[$offset]);
					$offset++;
					
					for ($i=0; $i<8; $i++)
					{				
						$rgb = $palette[ ($g >> $i) & 1];				
						imagesetpixel($im, $x+$i, $y, $rgb);
					}								
				}
			}
		}
						
		return $im;		 
	}
	
	static function save($im, $ext = 'pnm')
	{
		if ($ext === 'pbm') return self::save_pbm($im);
		else if ($ext == 'pgm') return self::save_pgm($im);
		else return self::save_pnm($im);
	}
	
	static function save_pbm($im)
	{
		list($wid, $hei) = array(imagesx($im), imagesy($im));
		
		imagepalettetotruecolor($im);		
		imagefilter($im, IMG_FILTER_GRAYSCALE);
	
		$str = "P4\n{$wid} {$hei}\n";
		for ($y=0; $y<$hei; $y++)
		{
			for ($x=0; $x<$wid; $x+=8)
			{				
				$b = 0;
				for ($i=0; $i<8; $i++)
				{
					$rgb = @imagecolorat($im, $x+$i, $y);
					$g = $rgb & 0xFF;
					if ($g<128)
					{
						$b += 1 << (7-$i);
					}
	
				}
				$str .= chr($b);					
			}
		}
		return $str;
	}
	
	static function save_pgm($im)
	{
		list($wid, $hei) = array(imagesx($im), imagesy($im));
	
		imagepalettetotruecolor($im);	
		imagefilter($im, IMG_FILTER_GRAYSCALE);
	
		$str = "P5\n{$wid} {$hei}\n255\n";
		for ($y=0; $y<$hei; $y++)
		{
			for ($x=0; $x<$wid; $x++)
			{
				$rgb = imagecolorat($im, $x, $y);
				$g = $rgb & 0xFF;
	
				$str .= chr($g);	
			}
		}
		return $str;
	}
	
	static function save_pnm($im)
	{
		list($wid, $hei) = array(imagesx($im), imagesy($im));
		
		imagepalettetotruecolor($im);	
		
		$str = "P6\n{$wid} {$hei}\n255\n";
		for ($y=0; $y<$hei; $y++)
		{
			for ($x=0; $x<$wid; $x++)
			{
				$rgb = imagecolorat($im, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
		
				$str .= chr($r) . chr($g) . chr($b);
			}
		}
		return $str;
	}
}

function imagecreatefrompnm($filename)
{
	$img = XImage_PNM::load($filename);
	return $img;
}

function imagepnm($img, $filename = NULL, $ext)
{
	$body = XImage_PNM::save($img, $ext);
	if ($filename)
	{
		file_put_contents($filename, $body);
	}
	else
	{
		echo $body;
	}
}

function imagecreatefromppm($filename)
{
	return imagecreatefrompnm($filename);
}

function imagecreatefrompgm($filename)
{
	return imagecreatefrompnm($filename);
}

function imagecreatefrompbm($filename)
{
	return imagecreatefrompnm($filename);
}

function imageppm($img, $filename = NULL)
{
	imagepnm($img, $filename, 'ppm');
}

function imagepgm($img, $filename = NULL)
{
	imagepnm($img, $filename, 'pgm');
}

function imagepbm($img, $filename = NULL)
{
	imagepnm($img, $filename, 'pbm');
}