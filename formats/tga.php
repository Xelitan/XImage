<?php
//License: GNU GPL
//Version: 1.0
//Copyright (c) Xelitan.com
//Supported: 8bpp grayscale, 24bpp and 32bpp
//Unsupported: palette images (type 1 and 9)

class XImage_TGA
{	
	static function save($im)
	{
		list($width, $height) = array(imagesx($im), imagesy($im));
		
		$tga = "\0\0\2\0\0\0\0\0\0\0\0\0" . pack('vv', $width, $height) . '  ';
		
		for ($y=0; $y<$height; $y++)
		for ($x=0; $x<$width; $x++)
		{
			$rgb = imagecolorat($im, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$a = ($rgb >> 24) & 0x7F;
			$tga .= chr($b).chr($g).chr($r).chr((127-$a)*2);
		}
		 
		return $tga;
	}
	
	static function load($filename)
	{
		$f = @file_get_contents($filename);
		if ($f == '')
		{
			return false;
		}
		$offset = 0;
		$header = substr($f, $offset, 18);
		$offset += 18;
		$header = unpack(	"cimage_id_len/ccolor_map_type/cimage_type/vcolor_map_origin/vcolor_map_len/" .
							"ccolor_map_entry_size/vx_origin/vy_origin/vwidth/vheight/" .
							"cpixel_size/cdescriptor", $header);
	
		switch ($header['image_type'])
		{		
			case 2:		//truecolor, uncompressed
			case 3:		//grayscale, uncompressed		
			case 10:	//truecolor, rle
			case 11:	//grayscale, rle		
						break;
			default:	die('Unsupported TGA format');					
		}
	
		if ($header['pixel_size'] != 8 and $header['pixel_size'] != 24 and $header['pixel_size'] != 32)
		{
			die('Unsupported TGA color depth');	
		}
		
		$bytes = $header['pixel_size'] / 8;
		
		if ($header['image_id_len'] > 0)
		{
			$header['image_id'] = substr($f, $offset, $header['image_id_len']);
			$offset += $header['image_id_len'];
		}
		else
		{
			$header['image_id'] = '';	
		}
		
		$im = imagecreatetruecolor($header['width'], $header['height']);
		imagealphablending($im, 0);
		
		$size = $header['width'] * $header['height'] * $bytes;
		 
		//-- check whether this is NEW TGA or not
		$pos = $offset;
		$newtga = substr($f, strlen($f)-26, 26);
		if (substr($newtga, 8, 16) != 'TRUEVISION-XFILE')
		{
			$newtga = false;
		}
		
		$datasize = strlen($f) - $pos; 
		if ($newtga)
		{
			$datasize -= 26;
		}		
	
		//-- end of check
		$data = substr($f, $offset, $datasize);
		$offset += $datasize;
		   
		if ($header['image_type'] == 10 or $header['image_type'] == 11)
		{
			$data = self::rle_decode($data, $size, $bytes);					
		}
		if (self::bit5($header['descriptor']) == 1)
		{
			$reverse = true;	
		}
		else
		{
			$reverse = false;
		}    
	    
		$i = 0;
		
		//read pixels
		if ($reverse)
		{   
		    for ($y=0; $y<$header['height']; $y++)	
	    	for ($x=0; $x<$header['width']; $x++)
	    	{
	    		if ($bytes == 1)
	    		{
	    			$col = ord($data[$i]) + (ord($data[$i])<<8) + (ord($data[$i])<<16);
	    		}
	    		else
	    		{
					$col = ord($data[$i]) + (ord($data[$i+1])<<8) + (ord($data[$i+2])<<16);
					if ($bytes == 4)
					{
						$col += ((255-ord($data[$i+3]))<<24);
					}
				}
				imagesetpixel($im, $x, $y, $col);
	    		$i += $bytes;
	    	}
	    }
	    else
	    {
	        for ($y=$header['height']-1; $y>=0; $y--)		
	    	for ($x=0; $x<$header['width']; $x++)
	    	{
	    		if ($bytes == 1)
	    		{
	    			$col = ord($data[$i]) + (ord($data[$i])<<8) + (ord($data[$i])<<16);
	    		}
	    		else
	    		{
					$col = ord($data[$i]) + (ord($data[$i+1])<<8) + (ord($data[$i+2])<<16);
					if ($bytes == 4)
					{
						$col += ((255-ord($data[$i+3]))<<24);
					}
				}
				imagesetpixel($im, $x, $y, $col);
	    		$i += $bytes;   		
	    	}
	    }   	    
		
		return $im;
	}
	
	static function bit5($x)
	{
		return ($x & 32) >> 5;	
	}
	
	static function rle_decode($data, $datalen, $bytes)
	{
		$len = strlen($data);	
		$out = '';
		
		$i = 0;
		$k = 0;
		while ($i<$len)
		{
			self::dec_bits(ord($data[$i]), $type, $value);
			if ($k >= $datalen)
			{
				break;
			}
	
			$i++;
			
			if ($type == 0) //raw
			{
				for ($j=0; $j<$bytes*$value; $j++)
				{
					$out .= $data[$j+$i];
					$k++;			
				}
				$i += $value*$bytes;
			}
			else //rle
			{
				for ($j=0; $j<$value; $j++)
				{
					$out .= substr($data, $i, $bytes);
					$k++;				
				}			
				$i += $bytes;
			}	
		}
		return $out;
	}
	
	static function dec_bits($byte, &$type, &$value)
	{
		$type = ($byte & 0x80) >> 7;
		$value = 1 + ($byte & 0x7F);
	}
}

if (!function_exists('imagecreatefromtga'))
{
	function imagecreatefromtga($filename)
	{
		$img = XImage_TGA::load($filename);
		return $img;
	}
}
if (!function_exists('imagetga'))
{
	function imagetga($img, $filename = NULL)
	{
		$body = XImage_TGA::save($img);
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