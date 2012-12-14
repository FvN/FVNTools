<?php
session_start( );
error_reporting( E_ALL );
ini_set( "display_errors", 1 );
	
/**
 * @author Yazuake
 * 
 * @desc
 * Establishes a connection to a MySQL database.
 * Sets PDO object in $this->DB.
 * 
 * @param Database Host
 * @param Database User
 * @param Database Password
 * @param Name of the database to connect with
 */
 function ConnectDB( $db_location, $db_user, $db_password, $db_name = false ) {
	
	$db_connection = "mysql:host=$db_location";
	
	if( $db_name != false ) {
		$db_connection .= ";dbname=$db_name";
	}
	
	return new PDO( $db_connection, $db_user, $db_password );
	
}
 
$YZK_DB = ConnectDB( "localhost", "root", "root", "test" );

require( "YZKTable.class.php" );

$YZKTable = new YZKTable( );

$YZKTable->SetZebra( true );

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
	false, false, "average", "total", false
);

$YZKTable->SetShowOptions( true, true, false );

// $YZKTable->SetOrder( "id", "ASC" );

$YZKTable->RenderTable( $sql, $headers, $footers );

?>
<script type="text/javascript" src="jq.js"></script>
<script type="text/javascript" src="YZKTable.js"></script>
<script type="text/javascript">
$( document ).ready( function( ) {
	
	YZKTable.init( );
	
} );
</script>

<?php

?>