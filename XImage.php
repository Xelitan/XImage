<?php
//License: GNU GPL
//Version: 1.0
//Copyright (c) Xelitan.com

foreach (glob('formats/*.php') AS $fname)
{
	include $fname;
}

define('DITHER_NONE', 0);
define('DITHER_ATKINSON', 1);
define('DITHER_FLOYD', 2);
define('DITHER_JARVIS', 3);
define('DITHER_BURKES', 4);
define('DITHER_STUCKI', 5);
define('DITHER_SIERRA2', 6);
define('DITHER_SIERRA3', 7);
define('DITHER_SIERRA4', 8);

class XImage
{
	private $img;
	private $height;
	private $width;
	
	private function _readSize()
	{
		$this->width = imagesx($this->img);
		$this->height = imagesy($this->img);
	}
	
	public function load($filename)
	{
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$fun = "imagecreatefrom$ext";
		if (function_exists($fun))
		{
			$img = @$fun($filename);
		}
		else
		{		
			$img = @imagecreatefromstring(@file_get_contents($filename));
		}
		
		if ($img)
		{
			imagepalettetotruecolor($img);
			$this->img = &$img;
		}

		$this->_readSize();
		
		return $this;
	}
	
	public function save($filename, $compression = -1, $ext = '')
	{
		if ($ext == '')
		{
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if ($ext == 'jpeg' or $ext == 'jpe' or $ext == 'jfif' or $ext == 'jfi')
			{
				$ext = 'jpg';
			}		
		}		
		switch ($ext)
		{
			case 'jpg':	imagejpeg($this->img, $filename, ($compression === -1) ? 80 : $compression);	
						break;
			case 'gif':	imagegif($this->img, $filename);
						break;
			case 'png':	imagepng($this->img, $filename, ($compression === -1) ? 6 : $compression);
						break;
			default   : $fun = "image$ext";
						if (function_exists($fun))
						{
							$fun($this->img, $filename);
						}					
		}
		return $this;
	}
	
	public function show($ext = 'png', $compression = -1)
	{
		$this->save('php://output', $compression, $ext);	
	}	
		
	public function toString($ext = 'png', $compression = -1)
	{
		ob_start();
		$this->show($ext, $compression);
		$str = ob_get_clean();
		
		return $str;
	}
	
	public function bw($dithering = DITHER_ATKINSON)
	{
		imagefilter($this->img, IMG_FILTER_GRAYSCALE);
		
		switch ($dithering)
		{
			case DITHER_ATKINSON:	$fun = '_atkinson'; break;
			case DITHER_JARVIS:		$fun = '_jarvis'; break;
			case DITHER_FLOYD:		$fun = '_floyd'; break;
			case DITHER_STUCKI:		$fun = '_stucki'; break;
			case DITHER_BURKES:		$fun = '_burkes'; break;
			case DITHER_SIERRA2:	$fun = '_sierra2'; break;
			case DITHER_SIERRA3:	$fun = '_sierra3'; break;
			case DITHER_SIERRA4:	$fun = '_sierra4'; break;
			default:				$fun = '';
		}
		
		//copy to an array for faster access
		$arr = array();
		for ($y=-5; $y<$this->height+5; $y++)
		{	
			for ($x=-5; $x<$this->width+5; $x++)
			{
				if ($x < 0 or $y < 0 or $x > $this->width-1 or $y > $this->height-1)
				{
					$gray = 0;
				}
				else
				{					
					$gray = imagecolorat($this->img, $x, $y) & 0xFF;
				}
				$arr[$x][$y] = $gray;
		    }
		}			
		
		for ($y=0; $y<$this->height; $y++)
		{
			for ($x=0; $x<$this->width; $x++)
			{
				$old = min(255, $arr[$x][$y]);
				$new = $old > 127 ? 255 : 0;

				$arr[$x][$y] = $new;		        
		        
				if ($fun != '')
				{
					$error = $old - $new;			        
					$this->$fun($error, $x, $y, $arr);
				}
			}
		}	
		
		for ($y=0; $y<$this->height; $y++)
		{
		    for ($x=0; $x<$this->width; $x++)
			{
				$gray = $arr[$x][$y];		        
				$rgb = ($gray << 16) + ($gray << 8) + $gray;
							        
				imagesetpixel($this->img, $x, $y, $rgb);
			}
		}
		
		return $this;		
	}	

	public function grayscale($bits = 8, $dithering = DITHER_ATKINSON)
	{
		imagefilter($this->img, IMG_FILTER_GRAYSCALE);
		
		if ($bits < 8)
		{
			$bits = 8 - $bits;
			
			switch ($dithering)
			{
				case DITHER_ATKINSON:	$fun = '_atkinson'; break;
				case DITHER_JARVIS:		$fun = '_jarvis'; break;
				case DITHER_FLOYD:		$fun = '_floyd'; break;
				case DITHER_STUCKI:		$fun = '_stucki'; break;
				case DITHER_BURKES:		$fun = '_burkes'; break;
				case DITHER_SIERRA2:	$fun = '_sierra2'; break;
				case DITHER_SIERRA3:	$fun = '_sierra3'; break;
				case DITHER_SIERRA4:	$fun = '_sierra4'; break;
				default:				$fun = '';
			}
			
			//copy to an array for faster access
			$arr = array();
			for ($y=-5; $y<$this->height+5; $y++)
			{	
				for ($x=-5; $x<$this->width+5; $x++)
				{
					if ($x < 0 or $y < 0 or $x > $this->width-1 or $y > $this->height-1)
					{
						$gray = 0;
					}
					else
					{					
						$gray = imagecolorat($this->img, $x, $y) & 0xFF;
					}
					$arr[$x][$y] = $gray;
			    }
			}			
			
			for ($y=0; $y<$this->height; $y++)
			{
				for ($x=0; $x<$this->width; $x++)
				{
					$old = min(255, $arr[$x][$y]);
					$new = ($old >> $bits) << $bits;

					$arr[$x][$y] = $new;		        
			        
					if ($fun != '')
					{
						$error = $old - $new;			        
						$this->$fun($error, $x, $y, $arr);
					}
				}
			}	
			
			for ($y=0; $y<$this->height; $y++)
			{
			    for ($x=0; $x<$this->width; $x++)
				{
					$gray = $arr[$x][$y];		        
					$rgb = ($gray << 16) + ($gray << 8) + $gray;
								        
					imagesetpixel($this->img, $x, $y, $rgb);
				}
			}									
		}
		
		return $this; 
	}
	
	public function emboss()
	{
		imagefilter($this->img, IMG_FILTER_EMBOSS);
		return $this; 
	}

	public function negate()
	{
		imagefilter($this->img, IMG_FILTER_NEGATE);
		return $this; 
	}
	
	public function blur($amount = 1)
	{
		for ($i=0; $i<$amount; $i++)
		{
			imagefilter($this->img, IMG_FILTER_GAUSSIAN_BLUR);
		}
		return $this; 
	}
	
	public function sharpen($amount = 10)
	{
		$v = $amount/10;
		$matrix = array(
					array(0, -$v, 0), 
					array(-$v, 4*$v+1, -$v), 
					array(0, -$v, 0),
				);
		$div = array_sum(array_map('array_sum', $matrix));		
		imageconvolution($this->img, $matrix, $div, 0);
		
		return $this;  
	}	
	
	public function edges()
	{
		imagefilter($this->img, IMG_FILTER_EDGEDETECT);
		return $this; 
	}
	
	public function colorize($r, $g, $b)
	{
		imagefilter($this->img, IMG_FILTER_COLORIZE, $r, $g, $b);
		return $this; 
	}
	
	public function contrast($amount = 10)
	{
		$amount = -$amount * 10;
		imagefilter($this->img, IMG_FILTER_CONTRAST, $amount);
		return $this; 
	}
	
	public function brighten($amount = 20)
	{
		$amount = floor($amount * 2.55);
		imagefilter($this->img, IMG_FILTER_BRIGHTNESS, $amount);
		return $this; 
	}
	
	public function darken($amount = 20)
	{
		$amount = floor(-$amount * 2.55);
		imagefilter($this->img, IMG_FILTER_BRIGHTNESS, $amount);
		return $this; 
	}
	
	public function sepia()
	{
		imagefilter($this->img, IMG_FILTER_GRAYSCALE);
		imagefilter($this->img, IMG_FILTER_COLORIZE, 100, 40, 0);		
		return $this; 
	}
	
	public function noise($amount = 10)
	{
		for ($y=0; $y<$this->height; $y++)
		{	
			for ($x=0; $x<$this->width; $x++)
			{
				$r = rand(0, 100);
				if ($amount > $r)
				{
					$color = rand(0, 0xFFFFFF);
					imagesetpixel($this->img, $x, $y, $color);
				}
		    }
		}		
		return $this; 
	}
	
	public function noisePepper($amount = 10)
	{
		for ($y=0; $y<$this->height; $y++)
		{	
			for ($x=0; $x<$this->width; $x++)
			{
				$r = rand(0, 100);
				if ($amount > $r)
				{
					$color = rand(0,1) == 1 ? 0xFFFFFF : 0x000000;
					imagesetpixel($this->img, $x, $y, $color);
				}
		    }
		}		
		return $this; 
	}
	
	private function _atkinson($error, $x, $y, &$arr)
	{	
		//Bill Atkinson dithering		
		$diff  = (1/8) * $error;

		$arr[$x+1][$y  ] += $diff;
		$arr[$x+2][$y  ] += $diff;
		$arr[$x-1][$y+1] += $diff;
		$arr[$x  ][$y+1] += $diff;
		$arr[$x+1][$y+1] += $diff;
		$arr[$x  ][$y+2] += $diff;
	}
	
	private function _floyd($error, $x, $y, &$arr)
	{	
		//Floyd-Steinberg dithering		
        $diff  = (1/16) * $error;

		$arr[$x + 1][$y    ] += $diff * 7;
		$arr[$x - 1][$y + 1] += $diff * 3;
		$arr[$x    ][$y + 1] += $diff * 5;
		$arr[$x + 1][$y + 1] += $diff * 1;
	}
	
	private function _jarvis($error, $x, $y, &$arr)
	{
		//Jarvis-Judice-Ninke dithering    
		$diff = (1/48) * $error;			
	
		$arr[$x + 1][$y    ] += $diff * 7;
		$arr[$x + 2][$y    ] += $diff * 5;
		$arr[$x - 2][$y + 1] += $diff * 3;
		$arr[$x - 1][$y + 1] += $diff * 5;
		$arr[$x    ][$y + 1] += $diff * 7;
		$arr[$x + 1][$y + 1] += $diff * 5;
		$arr[$x + 2][$y + 1] += $diff * 3;
		$arr[$x - 2][$y + 2] += $diff * 1;
		$arr[$x - 1][$y + 2] += $diff * 3;
		$arr[$x    ][$y + 2] += $diff * 5;
		$arr[$x + 1][$y + 2] += $diff * 3;
		$arr[$x + 2][$y + 2] += $diff * 1;
	}	
	
	private function _sierra2($error, $x, $y, &$arr)
	{	
		//Sierra 2 dithering		
        $diff  = (1/16) * $error;

		$arr[$x+1][$y  ] += $diff * 4;
		$arr[$x+2][$y  ] += $diff * 3;
		$arr[$x-2][$y+1] += $diff * 1;
		$arr[$x-1][$y+1] += $diff * 2;
		$arr[$x  ][$y+1] += $diff * 3;
		$arr[$x+1][$y+1] += $diff * 2;
		$arr[$x+2][$y+1] += $diff * 1;
	}
	
	private function _sierra3($error, $x, $y, &$arr)
	{	
		//Sierra 3 dithering		
        $diff  = (1/32) * $error;

		$arr[$x+1][$y  ] += $diff * 5;
		$arr[$x+2][$y  ] += $diff * 3;
		$arr[$x-2][$y+1] += $diff * 2;
		$arr[$x-1][$y+1] += $diff * 4;
		$arr[$x  ][$y+1] += $diff * 5;
		$arr[$x+1][$y+1] += $diff * 4;
		$arr[$x+2][$y+1] += $diff * 2;
		$arr[$x-1][$y+2] += $diff * 2;
		$arr[$x  ][$y+2] += $diff * 3;
		$arr[$x+1][$y+2] += $diff * 2;
	}

	private function _sierra4($error, $x, $y, &$arr)
	{	
		//Sierra 2-4a dithering		
        $diff  = (1/4) * $error;

		$arr[$x+1][$y  ] += $diff * 2;
		$arr[$x-1][$y+1] += $diff * 1;
		$arr[$x  ][$y+1] += $diff * 1;
	}
	
	private function _stucki($error, $x, $y, &$arr)
	{	
		//Stucki dithering		
        $diff  = (1/42) * $error;

		$arr[$x+1][$y  ] += $diff * 8;
		$arr[$x+2][$y  ] += $diff * 4;
		$arr[$x-2][$y+1] += $diff * 2;
		$arr[$x-1][$y+1] += $diff * 4;
		$arr[$x  ][$y+1] += $diff * 8;
		$arr[$x+1][$y+1] += $diff * 4;
		$arr[$x+2][$y+1] += $diff * 2;
		$arr[$x-2][$y+2] += $diff * 1;
		$arr[$x-1][$y+2] += $diff * 2;
		$arr[$x  ][$y+2] += $diff * 4;
		$arr[$x+1][$y+2] += $diff * 2;
		$arr[$x+2][$y+2] += $diff * 1;
	}
	
	private function _burkes($error, $x, $y, &$arr)
	{	
		//Burkes dithering		
        $diff  = (1/32) * $error;

	 	$arr[$x+1][$y  ] += $diff * 8;
		$arr[$x+2][$y  ] += $diff * 4;
		$arr[$x-2][$y+1] += $diff * 2;
		$arr[$x-1][$y+1] += $diff * 4;
		$arr[$x  ][$y+1] += $diff * 8;
		$arr[$x+1][$y+1] += $diff * 4;
		$arr[$x+2][$y+1] += $diff * 2;
	}	

	public function resize($width, $height)
	{
		$img = imagesale($this->img, $width, $height, IMG_BICUBIC);
		imagedestroy($this->img);
		$this->img = &$img;
		$this->_readSize();
		
		return $this;
	}
	
	public function scale($zoom)
	{
		$z = $zoom/100;
		return $this->resize($this->width*$z, $this->height*$z);
	}	
	
	public function colors()
	{
		return imagecolorstotal($this->img);
	}
	
	public function flipH()
	{
		imageflip($this->img, IMG_FLIP_HORIZONTAL);
		return $this;
	}
	
	public function flipV()
	{
		imageflip($this->img, IMG_FLIP_VERTICAL);
		return $this;
	}
	
	public function rotate($deg)
	{
		if ($deg == 180)
		{
			imageflip($this->img, IMG_FLIP_BOTH);			
			return $this;
		}
		$img = imagerotate($this->img, $deg, 0xFFFFFF);
		imagedestroy($img);
		$this->img = &$img;
		
		return $this;
	}
		
	public function border($width)
	{
		$img = imagecreatetruecolor($this->width + 2*$width, $this->height + 2*$width);
		imagecopy($img, $this->img, $width, $height, 0, 0, $this->width, $this->height);
		imagedestroy($this->img);
		$this->img = &$img;
		$this->_readSize();
		
		return $this;
	}	

	public function mirror()
	{
		$wid2 = $this->width/2;
		
		$img = imagecreatetruecolor($wid2, $this->height);
		imagecopy($img, $this->img, 0,0, 0,0, $wid2, $this->height);
		
		imageflip($img, IMG_FLIP_HORIZONTAL);
		imagecopy($this->img, $img, $wid2,0, 0,0, $wid2, $this->height);
		
		return $this;
	}
	
	public function merge($img, $percent)
	{
		list($width, $height) = array(imagesx($img), imagesy($img));
		imagecopymerge($this->img, $img, 0,0, 0,0, $width, $height, $percent);
		
		return $this;
	}
	
	
	public function crop($x, $y, $width, $height)
	{
		$img = imagecrop($this->img, array('x' => $x, 'y' => $y, 'width' => $width, 'height' => $height));
		
		imagedestroy($this->img);
		$this->img = &$img;	
	}	
	
	public function vibrance($value)
	{
		$value = $value / -10;
		
		for ($y=0; $y<$this->height; $y++)
		{
			for ($x=0; $x<$this->width; $x++)
			{
				$rgb = imagecolorat($this->img, $x, $y);
				
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
	
				$max = max($r, $g, $b);
				$avg = ($r + $g + $b) / 3;
				$amt = ((abs($max - $avg) * 2 / 255) * $value);
				
				$r += ($max !== $r) ? ($max - $r) * $amt : 0;
				$g += ($max !== $g) ? ($max - $g) * $amt : 0;
				$b += ($max !== $b) ? ($max - $b) * $amt : 0;
				
				$r = min(255, max($r, 0));
				$g = min(255, max($g, 0));
				$b = min(255, max($b, 0));						
				
				$rgb = ($r << 16) + ($g << 8) + $b;
				imagesetpixel($this->img, $x, $y, $rgb);			
			}
	    }
	}
	
	public function duotone($idx = 0)
	{
		$palette = array(
			array(0x6f, 0xed, 0x65,    0x1C, 0x19, 0x52),		
			array(0xF0, 0x0E, 0x2E,    0x19, 0x25, 0x50),		
			array(0xFF, 0xF6, 0x39,    0xE3, 0x41, 0x88),
			array(0xFF, 0x64, 0x38,    0x1E, 0x32, 0x65),	
			array(0x51, 0x37, 0x50,    0x94, 0xE0, 0xD4),
			array(0x2F, 0xFF, 0x84,    0x2C, 0x55, 0x83),
		);
		
		list($rr1, $gg1, $bb1, $rr2, $gg2, $bb2) = $palette[$idx];
	
		//gradient
		$grad = array();
		for ($i=0; $i<256; $i++)
		{
			$val = $i/255;
			
			$r = ($rr1 * $val) + ($rr2 * (1-$val));
			$g = ($gg1 * $val) + ($gg2 * (1-$val));
			$b = ($bb1 * $val) + ($bb2 * (1-$val));
				
			$r = min(255, max($r, 0));
			$g = min(255, max($g, 0));
			$b = min(255, max($b, 0));			
							
			$grad[$i] = ($r << 16) + ($g << 8) + $b;
		}
	
		for ($y=0; $y<$this->height; $y++)
		{
			for ($x=0; $x<$this->width; $x++)
			{
				$rgb = imagecolorat($this->img, $x, $y);
				
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
	
				$gray = round(($r + $g + $b)/3);
				$rgb = $grad[$gray];
	
				imagesetpixel($this->img, $x, $y, $rgb);
	      }
	    }	
	}

	public function roll($x, $y)
	{
		$img = imagecreatetruecolor($this->width, $this->height);
		
		$wid = $this->width-1;
		$hei = $this->height-1;
		
		imagecopy($img, $this->img, $x,$y, 0,0, $wid, $hei);
		imagecopy($img, $this->img, -$wid+$x,$y, 0,0, $wid, $hei);
		imagecopy($img, $this->img, $x,-$hei+$y, 0,0, $wid, $hei);
		imagecopy($img, $this->img, -$wid+$x,-$hei+$y, 0,0, $wid, $hei);	
		
		imagedestroy($this->img);
		$this->img = &$img;
	}

	public function setAlpha($alpha)
	{
		imagealphablending($this->img, false);
		imagesavealpha($this->img, true);
		
		for ($y=0; $y<$this->height; $y++)
		{
			for ($x=0; $x<$this->width; $x++)
			{
				$rgb = imagecolorat($this->img, $x, $y);
				$a = imagecolorat($alpha, $x, $y) & 0xFF;
				$a = $a >> 1;

				$rgba = $rgb + ($a << 24);
				imagesetpixel($this->img, $x, $y, $rgba);
			}
		}
	}

	public function roundCorner($radius = 20)
	{	
		$corner2 = imagecreatefromstring(base64_decode(
		'iVBORw0KGgoAAAANSUhEUgAAAfQAAAH0BAMAAAA5+MK5AAAAMFBMVEUAAAD///8QEBDv7++/v78wMDCnp6fPz8+Pj4+Ag
		IBQUFAgICBwcHBAQEDf399gYGAvjMRTAAAGSklEQVR4XuTPMUojYRiA4fcK8g+kCx9eYYcJOUDAhAR+BqzSB10EGxEF20C
		sUk2laSwFIdjaBewigmBpLYFUgvVus83CsgfI+9zgYc/jx9lkMru+Xf+6/zgcP+0r6ulzMnt9f+Rvu17vHK0fxsE/7XT7p
		eY/BG1RvfWnbaung+cAdPV0vlgCvno1rwFfvXNSA756uvgOENZT/w0Q1tNxDcZ6Z57BWC8XAcZ61QQY62UTYKyn0wBjPfV
		rUNarL1DWywaU9fQzcNb7GZT1ogFnvZ9x1lsNOOuXGWe93YCzvsk462kKznpVI60PA2c9TcFZL1ZI692MtD4MnPXUgLNe3
		iGtbzLS+ihw1lMPnPV0g7TeXiGtFyuk9SIjrVcZab27RFrfBtL6MJDWeyCt97DWR1jrA6z1Adb6MKz1q0Ba3wbS+jaQ1n+
		TP8cmbgRhAEa/Fsw/7IwT+2/CyTJwYHC0GLaGLUHgREqEKjiugilhSxCqQDVcqmRLcBkXfK+D15Ov4J3jnX99fdqR1suKt
		B53pPV4YK0vWOt/sdZ7Wus9kdbLirReBtJ6PLDWN6z1M9Z6T2u9rEjr8cBaX7DW37DWp7TW60Bajw+s9T9Y6z2t9bIircc
		da33BWu9prf8YWOsb1nrHWi+7tR4PrPUz1vqU1nocWOsL1npPa70+sdY3rPWOtR5DW1+w1lta63Fgrf/DWi+prX9grc9Y6
		3XX1jes9Y61HkNbX7DWW2rr71jrb1jrMbT1C9Z6SW19w1pvaa3HHWt9xlqPp7Z+wVovqa3fsNa/Y63Hoa3PWOsxtPUZaz1
		Wbf2FtV53bf2CtV5TWz9hrZfU1n9jrU+prd+w1ltq61es9Ya2/q6tN7T1q7be0NZv2vqU2vqGtV5SWz9hrdfU1k9Y63XX1
		l9Y67Fq67+w1mNo6zPWegxt/Sfa+lVbb2jrm7ZeU1t/Ya3HU1uf0dbv2npDW9+09Zra+ifWeqza+oy2fmjrDW39qq3X1NZ
		f/8mfw5q1gQAMwK+F5bpSSCmngaR/9uVLyBQggUzBggIknAQkIAEJJ2GpAiRMyCPhiUovT5Y+h6U/WPqusvQ1LP3J0qew9
		D8sfaks/Tss/cPSp7D0O0tfXiz9V1j6m6VPYekbSy+dpe/D0n+z9HJl6XNY+oOlL5Wlf4elN5Y+VJa+hqW/WfoYlr659Cd
		Ln8LS7yy9XFn6HJZ+YenlytL3Yel3ll46S5/C0jeX/mTpU1j62aV/WPoYlr669DdLHypLX8PSG0vfVZZ+DEt/sPRSWfocl
		n536Z2lj2Hpq0tvLL1Ulj6HpV9cemfpY1j66tIbS18qSz+FpV9cemfpY1j6l0tvLL1Ulr4PS99cemPpS2Xpc1j65tI/LH2
		pLP0Ulv7XpX9Y+hCWfnDpF5feWfoQln506TeX3ln6GJZ+dOkXl/5k6UtY+t6ln116Y+nlxdLHsPSfLv3m0jtLH8LSTy59c
		+mNpZfK0vdh6V8u/ebSO0sfw9IPLv2fS28u/cXSx7D0g0vfXHpz6S+WPoalH1z62aU3l35l6UNY+smln136w6V3lr4LSz+
		59LNLv7n0ztKX/+3XUZmjMBhA0UjYXToEko/pSKgEJFRCJUQCEiIBCUgYCUhAAhJWyPmvg/N4E0ufXPri0ptL31z6ydJzY
		umzS19cenPp3aW/WXpOLH126V8uvbn07tIPlp4TS59denXpzaV3l/5m6Tmx9MmlF5deXfrq0rtLP1n6kFj66NKLS68uvbn
		07tJPlj4klj669OLSq0tvLr279JOlD4mljy59dumLS3+59NWlby59d+kHS8+JpU8uvbj06tIvl/5x6d2lnyx9SCz94dInl
		15cenXpzaVvLn136QdLHxJLzy59dOlPl15cenXpl0tfXfrm0neXfrj0xNKzSx9d+uzSi0uvLv3l0leXvrn026XvLv1g6Tm
		x9IdLn1z67NKLS19cenXpL5feXPrHpW8u/Xbpu0onxy3o34mlZ5c+uvTJpT9demHp5LMG/XLpzaV/XPrm0rtLv136DqKD/
		nbph0v/cemJ7dulZ5f+cOmjS5+CDvZ06SXoYF8uvbr0fy79j0u/XHpz6atL/wTdKuhsW9Clgt6DDvYbdLA76FJB34MuFfQ
		z6GDvoIMdQZcK+k/Qvf4DNAXHiL5SOZMAAAAASUVORK5CYII='));
		imagepalettetotruecolor($corner2);
		
		$wid = $this->width;
		$hei = $this->height;

		$radius = min($radius, 500);
		$radius = min($radius, floor($hei/2));
		$radius = min($radius, floor($wid/2));

		$img = imagecreatetruecolor($wid, $hei);
		$corner = imagecreatetruecolor($radius, $radius);

		//better quality than imagescape
		imagecopyresampled($corner, $corner2, 0,0, 0,0, $radius, $radius, 500,500);
		imagedestroy($corner2);

		imagecopy($img, $corner, 0,0, 0,0, $radius, $radius);

		imageflip($corner, IMG_FLIP_VERTICAL);
		imagecopy($img, $corner, 0,$hei-$radius, 0,0, $radius, $radius);

		imageflip($corner, IMG_FLIP_HORIZONTAL);
		imagecopy($img, $corner, $wid-$radius,$hei-$radius, 0,0, $radius, $radius);

		imageflip($corner, IMG_FLIP_VERTICAL);
		imagecopy($img, $corner, $wid-$radius,0, 0,0, $radius, $radius);


		$this->setAlpha($img);
	}

	public function posterize($colors)
	{
		for ($y=0; $y<$this->height; $y++)
		{
			for ($x=0; $x<$this->width; $x++)
			{
				$rgb = imagecolorat($this->img, $x, $y);

 				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$r = floor($r / $colors) * $colors;
				$g = floor($g / $colors) * $colors;
				$b = floor($b / $colors) * $colors;

				$rgb = $b + ($g << 8) + ($r << 16);

				imagesetpixel($this->img, $x, $y, $rgb);
		    }
		}
	}
}