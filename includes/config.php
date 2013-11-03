<?php

	error_reporting(0);
	// If you want to debug, better to uncomment the next line to have traces
	// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	
	
	################### GLOBAL FONCTIONS ##########################
	/**
	*	Set an error or success message, give it to client then exit the ajax request
	*
	*	@param 	{String} 	$message 	Message to return to the client
	*	@return {Bool} 		$isAnError	True to set an error message, else False (optional - default True)
	*/
	function 	GL_AjaxExit($message, $isAnError = true) {
		$json = array();

		// Set error or success message
		if ($isAnError) {
			$json['error'] = $message;
		}
		else {
			$json['success'] = $message;
		}

		echo (json_encode($json, JSON_FORCE_OBJECT));
		exit;
	}
	
?>