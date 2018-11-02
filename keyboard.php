<?php

require_once("function.php");

$arr = array(
	"type" => "buttons",
	"buttons" => $default_menu,
);

echo json_encode($arr, JSON_UNESCAPED_UNICODE);

?>
