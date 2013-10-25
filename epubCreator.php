<?php

	require_once ('includes/config.php');
	
	if (!isset($_POST['e_name']) || !isset($_POST['e_pics'])) {
		GL_AjaxExit('Argument missing');
	}
	else {
		GL_AjaxExit('Todo: convert', false);
	}


?>