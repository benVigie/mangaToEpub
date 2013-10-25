<?php

	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	//error_reporting(0);
	
	
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