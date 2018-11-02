<?php

require_once("function.php");

$rawdata = file_get_contents("php://input");
$data = json_decode( $rawdata, true );
$userkey = $data["user_key"];
$content = $data["content"];

switch( $content )
{
	case "오늘의 학식을 알려주세요.":
		showMenu();
		break;
	case "'오늘 협성대 학식은 무엇인가요?' 소개":
		intro();
		break;
	case "무엇으로 만들었나요?":
		showHowToMade();
		break;
	case "처음 메뉴로 돌아갈래요":
		sendMessage("처음 메뉴로 이동했어요~! 아래에서 보고싶은 정보를 터치해주세요.");
		break;
	default:
		sendMessage("앗! 보내주신 [$content]는 아직 안 만들어져있네요...! 대신 첫 메뉴를 다시 보여드릴게요 ^ㅡ^!");
		break;
}

?>
