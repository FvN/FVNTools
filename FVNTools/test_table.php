<?php
error_reporting( E_ALL );
ini_set("display_errors", 1);

require( "YZKTable.class.php" );

$YZKTable = new YZKTable( );

$YZKTable->ConnectDB( "localhost", "root", "root", "test" );

$sql = "SELECT
	`id`
	`number_customergauge`,
	`Q1`,
	`Q1` AS `score`,
	`Q1_NPS`,
	`date_order`
FROM
	`cgnpsdata_awaiting`";

$headers = array(
	array(
		"label" => "CG-ID",
		"key" => "number_customergauge"
	),
	array(
		"label" => "Score",
		"key" => "Q1"
	),
	array(
		"label" => "Score",
		"key" => "score"
	),
	array(
		"label" => "NPS Type",
		"key" => "Q1_NPS"
	),
	array(
		"label" => "Date",
		"key" => "date_order"
	)
);

$footers = array(
	false, false, "average", "total", false, false
);

// $YZKTable->SetOrder( "id", "ASC" );

$YZKTable->RenderTable( $sql, $headers, $footers );

?>