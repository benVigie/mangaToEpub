<?php

	/**
	*	Picture tool. Allow to convert image and resize to the optimimal size
	*/ 
	class myPictureTool {

		/*---------------
		|	PROPERTIES 	|
		---------------*/
		private $_tmpFolderPath;	// Path to the temporary folder
		private $_width;			// Width of the ebook reader ie max picture width
		private $_height;			// Height of the ebook reader ie max picture height


		/*-------------------
		|					|
		|	PUBLIC METHODS 	|
		|					|
		-------------------*/
		
		/**
		* Constructor
		* 
		* @param {String} 	$tmpPath 		Path to the temp folder
		* @param {int} 		$ebook_width 	Max width for picture. Default to 600
		* @param {Int} 		$ebook_height 	Max height for picture. Default to 800
		* @return  
		*/
		public function 	__construct($tmpPath, $ebook_width = 600, $ebook_height = 800) {
			$this->_tmpFolderPath = $tmpPath;
			$this->_width = $ebook_width;
			$this->_height = $ebook_height;
		}

		/**
		*	Create a .jpg picture from an image
		*	
		*	@param:	{String} 	$picPath 		Path to the original picture
		*	@param:	{String} 	$pageNumber 	Number of the current page
		*	@return: {String} 	The name of the picture created, or null if an error occurs
		*/
		public function 	CreateOnePic($picPath, $pageNumber) {
			$outPic = ($pageNumber == 0) ? 'cover.jpg' : 'pic' . $pageNumber . '.jpg';
			
			if ($this->priv_CreateJpg($picPath, $this->_tmpFolderPath . $outPic))
				return ($outPic);
			
			return (null);
		}

		/**
		*	Create two .jpg picture from an image given
		*	
		*	@param:	{String} 	$picPath 		Path to the original picture
		*	@param:	{String} 	$pageNumber 	Number of the current page
		*	@return: {Array} 	Array of the 2 pictures created, or null if an error occurs
		*/
		public function 	CreateDoublePic($picPath, $pageNumber) {
			$picArray = array();
			
			$picArray[] = ($pageNumber == 0) ? 'cover.jpg' : 'pic' . $pageNumber . '.jpg';
			$picArray[] = 'pic' . ++$pageNumber . '.jpg';

			if (!$this->priv_CreateJpg($picPath, $this->_tmpFolderPath . $picArray[0], false))
				return (null);
			else if (!$this->priv_CreateJpg($picPath, $this->_tmpFolderPath . $picArray[1], true))
				return (null);
			
			return ($picArray);
		}

		/**
		*	Return true if the current picture is a double page. We assume that a normal manga page is in portrait orientation
		*	We will need to split the picture in 2 different pages
		*	
		*	@param:	{String} $picPath 		Path to the original picture
		*	@return: {Bool}		True if the current picture is a double page
		*/
		public function 	IsDoublePage($picPath) {
			// Get picture size
			$size = getimagesize($picPath);
			
			if ($size === false) {
				// TODO: Raise an exception ?
			}

			// If width > height, we don't have a portriat picture but a landscape one. So it's a souble page 
			if ($size[0] > $size[1])
				return (true);

			return (false);
		}
		


		/**
		*	Create an optimized .jpg picture from a given manga image
		*
		*	@param: {String}	$original 			Path to the original picture
		*	@param: {String}	$pic 				Path to the new jpg picture
		*	@param: {String}	$splitLeftSide 		If the picture need to be splitted, this boolean will be 
		*											true to keep the left part of the original picture or false to keep the right part.
		*											Default null, keep the whle picture.
		*	@return: {Bool} 	True if the new picture is successfully created, else false
		*/
		function	priv_CreateJpg($original, $pic, $splitLeftSide = null) {
			
			// Get size of the original picture
			$size = GetImageSize($original);
			$src_w = $size[0];
			$src_h = $size[1];

			$x = $y = 0;

			// If we have a double page, we just copy half of the original picture...
			if (!is_null($splitLeftSide)) {
				// ... From 0 for the left part or from the middle of the picture for the right part
				if ($splitLeftSide === false)
					$x = $src_w / 2;

				$src_w -= $src_w / 2;
			}

			// Create new picture 
			$newPic = ImageCreateTrueColor($this->_width, $this->_height);
			
			// Open handle on original picture
			$infos = pathinfo($original);
			switch (strtolower($infos['extension'])) {
				case 'jpg':
				case 'jpeg':
					$src_im = imagecreatefromjpeg($original);
					break;
				case 'png':
					$src_im = imagecreatefrompng($original);
					break;
				case 'gif':
					$src_im = imagecreatefromgif($original);
					break;
				case 'bmp':
					$src_im = imagecreatefromwbmp($original);
					break;
				
				default:
					ImageDestroy($newPic);
					return (false);
			}

			// Resize the picture to the right size
			ImageCopyResampled($newPic, $src_im, 0, 0, $x, $y, $this->_width, $this->_height, $src_w, $src_h);
			
			// Sauve la nouvelle image
			ImageJpeg($newPic, $pic, 85);
			
			// release handles
			ImageDestroy($src_im);
			ImageDestroy($newPic);
			
			return (true);
		}

	}
 ?> 