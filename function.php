<?php

$default_menu = array(
"오늘의 학식을 알려주세요.", 
"'오늘 협성대 학식은 무엇인가요?' 소개", 
"무엇으로 만들었나요?"
);

function db_open() { mysql_connect("데이터베이스HOST", "데이터베이스ID", "데이터베이스PW"); mysql_select_db("데이터베이스NAME"); }
function db_close() { mysql_close(); }

// 메시지만 보낼때
function sendMessage($msg)
{
	global $default_menu;

	$msg = str_replace("\\r", "\r", $msg);
	$msg = str_replace("\\n", "\n", $msg);

	$arr = array(
		"message" => array("text" => $msg),
		"keyboard" => array(
							"type" => "buttons", 
							"buttons" => $default_menu,
							),
	);

	echo json_encode($arr, JSON_UNESCAPED_UNICODE);
}

// 메시지와 버튼을 보낼때
function sendMessageWithButton($msg, $btn)
{
	$msg = str_replace("\\r", "\r", $msg);
	$msg = str_replace("\\n", "\n", $msg);

	$arr = array(
		"message" => array("text" => $msg),
		"keyboard" => array(
							"type" => "buttons", 
							"buttons" => $btn,
							),
	);

	echo json_encode($arr, JSON_UNESCAPED_UNICODE);
}

// 소개
function intro()
{
	global $default_menu;

	$msg = "안녕하세요! '오늘 협성대 학식은 무엇인가요?' 만든사람입니다~~

이것은 오늘의 학식을 자동으로 긁어와서 알려주는 서비스입니다.
심심해서 만들어봤는데, 심심하면 없앨(?) 예정입니다~~
다른 기능은 글쎄요..? 넣기 귀찮네요.

만든사람:
세무회계학+컴퓨터공학 13학번 오준석
페이스북: https://www.facebook.com/smartse0k
이메일: js940922@gmail.com

메이가 최고야 ♥
그러므로 그대도 저와 함께 메이를 감상합시다! 짠~";

	$imgurl = "https://i.imgur.com/OSn0LlP.jpg";

	$arr = array(
		"message" => array(
							"text" => $msg, 
							"photo" => array(
											"url" => $imgurl,
											"width" => 640,
											"height" => 640,
											)
							),
		"keyboard" => array(
							"type" => "buttons", 
							"buttons" => $default_menu,
							),
	);

	echo json_encode($arr, JSON_UNESCAPED_UNICODE);
}

// 오늘의 메뉴조회 (파싱)
function parsemenu($html)
{
	if( strpos($html, "식단") === FALSE ) return false;
	if( strpos($html, "<h5>") === FALSE ) return false;

	$data = $html;
	$data = str_replace("\\r", "", $data);
	$data = str_replace("\\n", "", $data);
	$data = str_replace("\r", "", $data);
	$data = str_replace("\n", "", $data);
	$data = str_replace("&amp;", "&", $data);
	$data = str_replace("&nbsp;", " ", $data);

	$isFirst = true; // 처음의 h5면 날짜이므로
	$parse = "";

	$tok = explode("<h5>", $data); // 식당별
	
	for( $i = 1; $i < count($tok); $i++ ) {
		if( $isFirst == true ) {
			$parse .= "< " . substr( $tok[$i], 0, strpos($tok[$i], "</h5>") ) . " >"; // 날짜
			$isFirst = false;
			continue;
		}

		$parse .= "\r\n\r\n▶ " . substr( $tok[$i], 0, strpos($tok[$i], "</h5>") ); // 식당

		$tok2 = explode("<strong>", $tok[$i]); // 메뉴별
		for( $jj = 1; $jj < sizeof($tok2); $jj++ ) {
			$temp = $tok2[$jj];
			$parse .= "\r\n" . substr($temp, 0, strpos($temp, "</strong>")); // 이름
			$pos1 = strpos($temp, "<p>" );
			$pos2 = strpos($temp, "</p>" );
			$parse .= "\r\n" . substr($temp, $pos1 + 3, $pos2 - $pos1 - 3); // 메뉴
		}
	}

	return $parse;
}

// 오늘의 메뉴조회
function showMenu()
{
	// 우선 DB에 저장된 정보를 불러온다.
	db_open();

	$data = mysql_fetch_array( mysql_query("SELECT * FROM `whatismenuofuhs` WHERE `head` = 'menu';") );
	$regdate = strtotime( $data["regdate"] ); // 등록된 시간
	$now = strtotime("now"); // 현재 시간
	$diff = ($now - $regdate) / 60; // 차이

	$regdate = date("Y-m-d", $regdate); // 등록된 날짜
	$now = date("Y-m-d", $now); // 현재 날짜

	$data = $data["data"];
	
	// 데이터를 가져온지 60분이 지났거나 날짜가 바뀌었다면 새로 가져온다
	if( $diff > 60 || ($regdate != $now) ) {
		$date = DATE("Y.m.d"); // 오늘 날짜

		// cURL을 이용하여 홈페이지 탐색
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "http://hsmart.uhs.ac.kr/user/drwa/menu_search.jsp?regdate=$date");
		curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36");
		$result = curl_exec($curl);
		$retcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		// 처음엔 실패했다고 가정시키고, 성공하면 그 아래에서 메시지가 변한다.
		$data = "학식 메뉴를 불러오지 못하고 있어요! 다시 시도 해주세요.

만약 지속적으로 문제가 발생한다면 만든이에게 연락을 부탁드리겠습니다!
페이스북: https://www.facebook.com/smartse0k
이메일: js940922@gmail.com";

		if( $retcode == 200 )
			if( ($data = @parsemenu($result)) != false ) {
				$data = mysql_real_escape_string($data);
				mysql_query("UPDATE `whatismenuofuhs` SET `data` = '$data', `regdate` = now() WHERE `head` = 'menu';");
			}
	}

	db_close();

	sendMessage($data);
}

// 무엇으로 어떻게 
function showHowToMade()
{
	$msg = 
"'오늘 협성대 학식은 무엇인가요?'는 Apache, PHP로 기능이 구현되어 있으며, 학교 홈페이지의 접속 폭주를 방지하기 위해 MySQL 데이터베이스에 최신 식단 정보를 저장합니다.

또한 github에 소스코드와 가이드가 업로드 되어 있으니 관심 있으신 개발자와 궁금하신 여러분은 아래 링크에서 확인이 가능합니다.
https://github.com/smartse0k/uhsmenu";

	sendMessage($msg);
}

?>
