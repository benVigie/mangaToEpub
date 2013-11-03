<?php

	// Needed files
	require_once ('includes/config.php');
	require_once ('includes/epubCreator.php');

	// If we don't get basic informations, abord
	if (!isset($_POST['e_name']) || !isset($_POST['e_pic_folder'])) {
		GL_AjaxExit('Argument missing');
	}

	// When we convert a large number of pictures, the creation of the ebook can be a bit long.
	// To avoid to exit after 30 seconds, we extend this limit to 1mn
	set_time_limit(60);

	// Create a instance of EpubCreator class and remove potential old ebook
	$epubCreator = new EpubCreator();
	$epubCreator->ReleaseTempFolder();

	// Get client's info
	$epub_name = $_POST['e_name'];
	$epub_pic_folder = $_POST['e_pic_folder'];
	$epub_description = (!empty($_POST['e_desc'])) ? $_POST['e_desc'] : null;
	$epub_output = (!empty($_POST['e_out'])) ? $_POST['e_out'] : null;
	
	// Sanity check
	if (($epub_pic_folder[strlen($epub_pic_folder - 1)] != '/') || ($epub_pic_folder[strlen($epub_pic_folder - 1)] != '\\'))
		$epub_pic_folder .= '/';

	if ((!is_null($epub_output)) && (($epub_output[strlen($epub_output - 1)] != '/') || ($epub_output[strlen($epub_output - 1)] != '\\')))
		$epub_output .= '/';


	// Start conversion
	// Check if the repo is valid
	if (is_dir($epub_pic_folder)) {

		// Initialyze new book
		if (!$epubCreator->NewBook($epub_name, $epub_output)) {
			$epubCreator->ReleaseTempFolder();
			GL_AjaxExit('Cannot create new ebook');
		}
		else {
			// List all pictures in the repo
			if ($dh = opendir($epub_pic_folder)) {
			
				// Create a page for each picture
				while (($file = readdir($dh)) !== false) {
					if (filetype($epub_pic_folder . $file) === 'file') {
						
						if (!$epubCreator->AddPage($epub_pic_folder . $file)) {
							$epubCreator->ReleaseTempFolder();
							GL_AjaxExit('Cannot add page in ebook, abord (file [' . $epub_pic_folder . $file . '])');
						}
					}
				}
				closedir($dh);

				// When all pages are ready, create the ebook
				if (!$epubCreator->Finalyze($epub_name, $epub_description, $epub_output)) {
					$epubCreator->ReleaseTempFolder();
					GL_AjaxExit('Error during zip creation');
				}
				
				// Everything's fine, print success message
				GL_AjaxExit('Epub created !', false);
			}
		}

	}
	else {
		GL_AjaxExit('Invalid repository [' . $epub_pic_folder . ']');
	}

?>