<?php

echo('
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex,nofollow,noarchive">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<title>battle_game</title>
	<link href="./battle_game.css" type="text/css" rel="stylesheet">


	<script type="text/javascript" src="./js/iscroll.js"></script>
	<script type="text/javascript">
	var myScroll;
	function loaded() {
		myScroll = new iScroll("wrapper");
	}
	document.addEventListener("touchmove", function (e) { e.preventDefault(); }, false);
	document.addEventListener("DOMContentLoaded", function () { setTimeout(loaded, 200); }, false);
	</script>
</head>
<body>


<header id="header">
	<section id="header">
		<h1>タイトル/header固定テスト</h1>
		<nav></nav>
	</section>
</header>

<div id="wrapper">
	<div id="scroller">
');

?>



