<?php

	require_once ('includes/myPictureTool.php');

	/**
	*	This class create the ebook.
	*	The way to create an ebbok is simple
	*		- First initialyze our book with NewBook()
	*		- Secondly we have to add each page (aka all manga's pictures) with AddPage() method
	*		- Finally we create the ebook by calling Finalyze(). This method will create some usefull files like content.opf and toc.ncx, then zip the temp folder and rename it.
	*/ 
	class EpubCreator {

		/*---------------
		|	Properties 	|
		---------------*/
		private $_picTool;			// Instance of the picture tool (usefull to resize, split pictures, etc...)
		private $_tmpFolderPath;	// Path to the temporary folder
		private $_pages;			// Array of pages of the book



		/*-------------------
		|					|
		|	PUBLIC METHODS 	|
		|					|
		-------------------*/
		
		/**
		* Constructor
		* 
		* @param
		* @return  
		*/
		public function 	__construct() {
			$this->_tmpFolderPath = './temp/';
			$this->_pages = array();

			// Initialyze picture tool
			$this->_picTool = new myPictureTool($this->_tmpFolderPath);
		}
		/**
		*	Initialyze the creation of a new ebook
		*	
		*	@param:	{String} $bookName 		Book name
		*	@param: {String} $bookDesc 		Book description. Set to default if user doesn't set one
		*	@return: {Bool}		True if the init success.
		*/
		public function 	NewBook($bookName, $bookDesc) {
			// Create temp folder
			if (!mkdir($this->_tmpFolderPath)) {
				return (false);
			}
			else {
				// Copy usefull files
				if (!copy('./samples/mimetype', $this->_tmpFolderPath . 'mimetype')) {
					return (false);
				}
				else if (!copy('./samples/titlepage.xhtml', $this->_tmpFolderPath . 'titlepage.xhtml')) {
					return (false);
				}
				else if (!copy('./samples/style.css', $this->_tmpFolderPath . 'style.css')) {
					return (false);
				}
			}

			return (true);
		}

		/**
		*	Create a page from a picture
		*	
		*	@param:	{String} $picturePath 		Picture path
		*	@return: {Bool}		True if a new page was properly created, false if any error occurs
		*/
		public function 	AddPage($picturePath) {
			$actualPage = count($this->_pages);
			$doublePage = $this->_picTool->IsDoublePage($picturePath);
			$page = '';

			// For a single page
			if (!$doublePage) {
				$picture = $this->_picTool->CreateOnePic($picturePath, $actualPage);
				if (is_null($picture))
					return (false);

				if ($actualPage == 0)
					$this->_pages['titlepage.xhtml'] = $picture;
				else {
					$page = 'manga' . $actualPage . '.html';
					if ($this->priv_createHTMLPage($page, $picture))
						$this->_pages[$page] = $picture;
					else
						return (false);
				}
			}
			else {
				// Create 2 pages with a picture
				$pictures = $this->_picTool->CreateDoublePic($picturePath, $actualPage);
				if (is_null($pictures) || count($pictures) != 2)
					return (false);

				for ($i = 0; $i < 2; $i++) { 
					
					if ($actualPage == 0)
						$this->_pages['titlepage.xhtml'] = $pictures[$i];
					else {
						$page = 'manga' . $actualPage . '.html';
						if ($this->priv_createHTMLPage($page, $pictures[$i]))
							$this->_pages[$page] = $pictures[$i];
						else
							return (false);
					}
					$actualPage++;
				}
				
			}

			return (true);
		}

		/**
		*	Finalyze the ebook. Create usefull files, zip everything and change extension to .epub
		*	
		*	@param:	{String} $epub_name 	Ebook name
		*	@param:	{String} $epub_desc 	Ebook description
		*	@param:	{String} $epub_out	 	Output folder
		*	@return: 
		*/
		public function 	Finalyze($epub_name, $epub_desc, $epub_out) {
			// Create toc.ncx file
			if (!$this->priv_createNcxFile($epub_name))
				return (false);

			// Create content.opf file
			if (!$this->priv_createOpfFile($epub_name, $epub_desc))
				return (false);

			// Zip the temp folder, rename it and move it to the right place
			return ($this->priv_zipAndMove($epub_name, $epub_out));
		}

		/**
		*	In case of error, properly release temp files
		*	
		*	@param:
		*	@return: 
		*/
		public function 	ReleaseTempFolder() {
			// Remove all files in temp folder if exist
			$this->priv_deleteDirectory($this->_tmpFolderPath);
		}




		/*---------------------*\
		|						|
		|	PRIVATE METHODS 	|
		|						|
		\*---------------------*/
		
		/**
		*	Create an HTML page which handle the manga picture
		*	
		*	@param: {String} 	$pageName 	Name of the html file to create
		*	@param: {String} 	$picPath 	Path to the manga picture to insert into the page 
		*	@return: {Bool}		True if the file was properly created, else false
		*/
		private function priv_createHTMLPage($pageName, $picPath) {
			$page_hndl = fopen($this->_tmpFolderPath . $pageName, 'w');

			if ($page_hndl !== false) {
				// Create html file
				fwrite($page_hndl, '<?xml version=\'1.0\' encoding=\'utf-8\'?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"><head><title>Le piti livre page manga</title><link rel="stylesheet" href="style.css" media="all"/></head><body><div>');
				
				// Insert picture
				fwrite($page_hndl, '<img alt="' . $picPath . '" src="' . $picPath . '" /></div></body></html>');
				
				fclose($page_hndl);
			}
			else
				return (false);

			return (true);
		}

		/**
		*	Create ncx file (Table of Content)
		*	
		*	@param: {String} 	$epub_name 	Ebook name
		*	@return: {Bool}		True if the file was properly created, else false
		*/
		private function priv_createNcxFile($epub_name) {
			$indx = 1;
			$totalPages = count($this->_pages);
			$ncx_hndl = fopen($this->_tmpFolderPath . 'toc.ncx', 'w');

			if ($ncx_hndl !== false) {
				fwrite($ncx_hndl, '<?xml version=\'1.0\' encoding=\'utf-8\'?><ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="eng"><head><meta content="0c159d12-f5fe-4323-8194-f5c652b89f5c" name="dtb:uid"/><meta content="2" name="dtb:depth"/></head><docTitle><text>');
				fwrite($ncx_hndl, $epub_name);
				fwrite($ncx_hndl, '</text></docTitle><navMap>');

				// Add all pages in toc.ncx
				foreach ($this->_pages as $html => $pic) {
					fwrite($ncx_hndl, '<navPoint id="a' . $indx . '" playOrder="' . $indx . '"><navLabel><text>' . $epub_name . ' - ' . $indx++ . '/' . $totalPages . '</text></navLabel><content src="' . $html . '"/></navPoint>');
				}

				fwrite($ncx_hndl, '</navMap></ncx>');
				fclose($ncx_hndl);

				return (true);
			}
			else
				return (false);
		}

		/**
		*	Create opf file
		*	
		*	@param:	{String} $epub_name 	Ebook name
		*	@param:	{String} $epub_desc 	Ebook description
		*	@return: {Bool}		True if the file was properly created, else false
		*/
		private function priv_createOpfFile($epub_name, $epub_desc) {
			$indx = 1;
			$picList = '<item href="cover.jpg" id="cover" media-type="image/jpeg"/>';
			$pagesList = '';
			$toc = '<itemref idref="titlepage"/>';

			$opf_hndl = fopen($this->_tmpFolderPath . 'content.opf', 'w');

			if ($opf_hndl !== false) {
				fwrite($opf_hndl, '<?xml version=\'1.0\' encoding=\'utf-8\'?><package xmlns="http://www.idpf.org/2007/opf" version="2.0" unique-identifier="uuid_id"><metadata xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:opf="http://www.idpf.org/2007/opf" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>');
				fwrite($opf_hndl, $epub_name . '</dc:title><dc:description>');

				if (is_null($epub_desc))
					fwrite($opf_hndl, ($epub_name . ' - Ebook created by Manga to Epub'));

				else
					fwrite($opf_hndl, $epub_desc);
	
				fwrite($opf_hndl, '</dc:description><dc:language>en</dc:language><dc:subject>Manga</dc:subject><dc:publisher>Manga to Epub</dc:publisher><dc:creator opf:file-as="Manga to Epub" opf:role="aut">Manga to Epub</dc:creator><dc:identifier id="uuid_id" opf:scheme="uuid">0c159d12-f5fe-4323-8194-f5c652b89f5c</dc:identifier><meta name="cover" content="cover"/></metadata><manifest><item href="style.css" id="css" media-type="text/css"/><item href="titlepage.xhtml" id="titlepage" media-type="application/xhtml+xml"/><item href="toc.ncx" media-type="application/x-dtbncx+xml" id="ncx"/>');

				// Add all pages in content.opf
				foreach ($this->_pages as $html => $pic) {
					
					// If it's not the cover page
					if ($pic != 'cover.jpg') {

						// Add pic
						$picList .= '<item href="' . $pic . '" id="MangaPicture' . $indx . '" media-type="image/jpeg"/>';

						// Add page
						$pagesList .= '<item href="' . $html . '" id="page' . $indx . '" media-type="application/xhtml+xml"/>';
						
						// Add page in toc
						$toc .= '<itemref idref="page' . $indx . '"/>';
					}

					$indx++;
				}

				// Add pic list in content.opf
				fwrite($opf_hndl, $picList);
				// Add pages list in content.opf
				fwrite($opf_hndl, $pagesList);
				// Add toc in content.opf
				fwrite($opf_hndl, '</manifest><spine toc="ncx">');
				fwrite($opf_hndl, $toc);

				// Close
				fwrite($opf_hndl, '</spine><guide><reference href="titlepage.xhtml" type="cover" title="Cover"/></guide></package>');
				fclose($opf_hndl);

				return (true);
			}
			else
				return (false);
		}

		/**
		*	Zip the temporary folder, change its extension to .epub and move it to the right place
		*	
		*	@param: {String} 	$ebookName 	Ebbok name
		*	@param: {String} 	$output 	Directory to move the ebbok
		*	@return: {Bool} 	As usual, True if everything's fine. Else false
		*/
		private function priv_zipAndMove($ebookName, $output) {
			$zip = new ZipArchive;
			$ebookFiles = scandir($this->_tmpFolderPath);

			$ebookName .= '.epub';
			
			if ($zip->open($ebookName, ZipArchive::CREATE) === true) {

				// Add META-INF directory
				// /!\ Actually this directory and its file must be uncompressed. As the regular PHP Zip API doesn't support uncompressed files, we add it as it
				// Note that it can be a problem for some readers
				if (!$zip->addFile('./samples/container.xml', 'META-INF/container.xml')) {
					$zip->close();
					return (false);
				}

				// Copy the file into the zip
				foreach ($ebookFiles as $file) {
					if ($file != '.' && $file != '..') {

						if (!$zip->addFile($this->_tmpFolderPath . $file, $file)) {
							$zip->close();
							return (false);
						}

					}
				}
				// Properly close the zip file
				$zip->close();

				// If the user set an output folder, move the epub into it
				if (!is_null($output)) {
					return (rename($ebookName, $output . $ebookName));
				}
				return (true);

			}
			else
				return (false);
		}

		/**
		*	Delete folder recusively
		*	
		*	@param: {String} 	$dir 	Path to the directory to remove
		*	@return: 
		*/
		private function priv_deleteDirectory($dir) {
			if (is_dir($dir)) {
				$objects = scandir($dir);
				
				foreach ($objects as $object) {

					if ($object != '.' && $object != '..') {

						if (filetype($dir . '/' . $object) == 'dir')
							$this->priv_deleteDirectory($dir . '/' . $object);
						else
							unlink($dir . '/' . $object);
					}
				}

				reset($objects);
				rmdir($dir);
			}
		}


	}
 ?> 