<?php
//License: GNU GPL
//Version: 1.0
//Copyright (c) Xelitan.com

class XImage_BMP
{
	static function save($img)
	{
		list($width, $height) = array(imagesx($img), imagesy($img));
		
		$widPad = str_pad('', $width % 4, "\0");
		
		$size = 54 + ($width + $widPad) * $height * 3;
		
		//prepare & save header
		$head['identifier']		= 'BM';
		$head['fileSize']		= pack("V", $size);
		$head['reserved']		= pack("V", 0);
		$head['bitmapData']		= pack("V", 54);
		$head['headerSize']		= pack("V", 40);
		$head['width']			= pack("V", $width);
		$head['height']			= pack("V", $height);
		$head['planes']			= pack("v", 1);
		$head['bitsPerPixel']	= pack("v", 24);
		$head['compression']	= pack("V", 0);
		$head['dataSize']		= pack("V", 0);
		$head['hResolution']	= pack("V", 0);
		$head['vResolution']	= pack("V", 0);
		$head['colors']			= pack("V", 0);
		$head['importantColors']= pack("V", 0);
	
	    $body = '';
	    foreach ($header AS $h)
	    {
	    	$body .= $h;
	    }
	    
		//save pixels
		for ($y=$height-1; $y>=0; $y--)
		{
			for ($x=0; $x<$width; $x++)
			{
				$rgb = imagecolorat($img, $x, $y);
				$r = ($rgb >> 16) & 255;				
				$g = ($rgb >> 8) & 255;
				$b = $rgb & 255;				
				$body .= chr($g).chr($g).chr($r);
			}
			$body .= $widPad;
		}
	
		return $body;			
	}
	
	static function load($filename)
	{
		$f = file_get_contents($filename);
		$offset = 0;

		//read header    
	    $header = substr($f, $offset, 54);
	    $offset += 54;
	    
	    $header = unpack(	'c2identifier/Vfile_size/Vreserved/Vbitmap_data/Vheader_size/' .
							'Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vdata_size/'.
							'Vh_resolution/Vv_resolution/Vcolors/Vimportant_colors', $header);
	
	    if ($header['identifier1'] != 66 or $header['identifier2'] != 77)
	    {
	    	die('Not a valid BMP file');
	    }
	    
	    if (!in_array($header['bits_per_pixel'], array(24, 32, 8, 4, 1)))
	    {
	    	die('Only 1, 4, 8, 24 and 32 bit BMP images are supported');
	    }
	    
		$bps = $header['bits_per_pixel']; //bits per pixel 
	    $wid2 = ceil(($bps/8 * $header['width']) / 4) * 4;
		$colors = pow(2, $bps);
	
	    $wid = $header['width'];
	    $hei = $header['height'];
	
	    $img = imagecreatetruecolor($header['width'], $header['height']);
	
		//read palette
		if ($bps < 9)
		{
			for ($i=0; $i<$colors; $i++)
			{
				$palette[] = unpack('V', substr($f, $offset, 4))[1];
				$offset += 4;
			}
		}
		else
		{
			if ($bps == 32)
			{
				imagealphablending($img, false);
				imagesavealpha($img, true);			
			}
			$palette = array();
		}	
	
		//read pixels    
	    for ($y=$hei-1; $y>=0; $y--)
	    {
			$row = substr($f, $offset, $wid2);
			$offset += $wid2;
					
			$pixels = self::str_split2($row, $bps, $palette);
	    	for ($x=0; $x<$wid; $x++)
	    	{
	    		self::makepixel($img, $x, $y, $pixels[$x], $bps);
	    	}
	    }
		fclose($f);    	    
		
		return $img;
	}
	
	private static function str_split2($row, $bps, $palette)
	{
		switch ($bps)
		{
			case 32:
			case 24:	return str_split($row, $bps/8);
			case  8:	$out = array();
						$count = strlen($row);				
						for ($i=0; $i<$count; $i++)
						{					
							$out[] = $palette[	ord($row[$i])		];
						}				
						return $out;		
			case  4:	$out = array();
						$count = strlen($row);				
						for ($i=0; $i<$count; $i++)
						{
							$roww = ord($row[$i]);						
							$out[] = $palette[	($roww & 240) >> 4	];
							$out[] = $palette[	($roww & 15) 		];
						}				
						return $out;
			case  1:	$out = array();
						$count = strlen($row);				
						for ($i=0; $i<$count; $i++)
						{
							$roww = ord($row[$i]);						
							$out[] = $palette[	($roww & 128) >> 7	];
							$out[] = $palette[	($roww & 64) >> 6	];
							$out[] = $palette[	($roww & 32) >> 5	];
							$out[] = $palette[	($roww & 16) >> 4	];
							$out[] = $palette[	($roww & 8) >> 3	];
							$out[] = $palette[	($roww & 4) >> 2	];
							$out[] = $palette[	($roww & 2) >> 1	];
							$out[] = $palette[	($roww & 1)			];
						}				
						return $out;					
		}
	}
	
	private static function makepixel($img, $x, $y, $str, $bps)
	{
		switch ($bps)
		{
			case 32 :	$a = ord($str[0]);
						$b = ord($str[1]);
						$c = ord($str[2]);
						$d = 256 - ord($str[3]);
						$d = floor($d/2);
						$pixel = ($d << 24) + ($c << 16) + ($b << 8) + $a;
						imagesetpixel($img, $x, $y, $pixel);
						break;
			case 24 :	$a = ord($str[0]);
						$b = ord($str[1]);
						$c = ord($str[2]);
						$pixel = ($c << 16) + ($b << 8) + $a;
						imagesetpixel($img, $x, $y, $pixel);
						break;					
			case 8 :
			case 4 :
			case 1 :	imagesetpixel($img, $x, $y, $str);
						break;
		}
	}
	
	private static function byte3($n)
	{
		return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255);	
	}
	
	private static function undword($n)
	{
		$r = unpack("V", $n);
		return $r[1];
	}
	
	private static function dword($n)
	{
		return pack("V", $n);
	}
	
	private static function word($n)
	{
		return pack("v", $n);
	}
}

if (!function_exists('imagecreatefrombmp'))
{
	function imagecreatefrombmp($filename)
	{
		$img = XImage_BMP::load($filename);
		return $img;
	}
}

if (!function_exists('imagebmp'))
{
	function imagebmp($img, $filename = NULL)
	{
		$body = XImage_BMP::save($img);
		if ($filename)
		{
			file_put_contents($filename, $body);
		}
		else
		{
			echo $body;
		}
	}
}