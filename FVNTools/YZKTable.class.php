<?php

/**
 * @author Yazuake
 * 
 * @desc
 * Class that gives an enhanced html table.
 */
class YZKTable {
	
	/**
	 * @desc
	 * MySQL PDO Object.
	 */
	private $DB = false;
	
	/**
	 * @desc
	 * What column to order.
	 */
	private $order_subject = false;
	
	/**
	 * @desc
	 * The direction of the order.
	 * Valid directions are "ASC", "DESC" or "RAND"
	 */
	private $order_direction = "ASC";
	
	/**
	 * @desc
	 * The type of the order.
	 * Valid types are: "NUM", "ABC" or "DATE"
	 */
	private $order_type = "";
	
	/**
	 * @desc
	 * String to search for in results.
	 */
	private $search = "";
	
	/**
	 * @desc
	 * The amount of results to show on one page.
	 */
	private $results_per_page = 25;
	
	/**
	 * @desc
	 * The id of the current page.
	 */
	private $current_page = 1;
	
	/**
	 * @desc
	 * The id of the current page.
	 */
	private $total_results = 0;
	
	/**
	 * @desc
	 * The id of the current page.
	 */
	private $total_pages = 1;
	
	/**
	 * @desc
	 * The id of the current page.
	 */
	private $debug = false;
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Constructor. Takes $_GET parameters and allocates them into the class.
	 */
	public function YZKTable( ) {
		
		if( isset( $_GET[ 'debug' ] ) ) {
			
			$this->debug = true;
			
		}
		
		if( isset( $_GET[ 'yzk_os' ] ) ) {
			$os = $_GET[ 'yzk_os' ];
			
			$this->order_subject = $os;
		}
		
		if( isset( $_GET[ 'yzk_od' ] ) ) {
			$od = strtolower( $_GET[ 'yzk_od' ] );
			
			if( $od == 'asc' || $od == 'desc' || $od == 'rand' ) {
				$this->order_direction = strtoupper( $od );
			}
		}
		
		if( isset( $_GET[ 'yzk_rpp' ] ) ) {
			$rpp = $_GET[ 'yzk_rpp' ];
			
			if( is_numeric( $rpp ) == true ) {
				$this->results_per_page = $rpp;
			}
		}
		
		if( isset( $_GET[ 'yzk_cp' ] ) ) {
			$cp = $_GET[ 'yzk_cp' ];
			
			if( is_numeric( $cp ) == true ) {
				$this->current_page = $cp;
			}
		}
		
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Runs the given query and returns array containing the results, no. of rows and no. of fields.
	 * 
	 * @param The query to execute.
	 * 
	 * @param Whether to return the results in an associative array (Default = true).
	 */
	public function ExecuteQuery( $query, $associative_results = true ) {
		
		echo "<!---
			Parsed Query:
			
			$query
		--->";
		
		$result_set = $this->DB->prepare( $query );
		$query_result = $result_set->execute( );
		
		if( $query_result == false ) {
			return false;
		}
		
		$rows = $result_set->rowCount( );
		$fields = $result_set->columnCount( );
		
		if( $associative_results == false ) {
			$result_set->setFetchMode( PDO::FETCH_NUM );
		} else {
			$result_set->setFetchMode( PDO::FETCH_ASSOC );
		}
		
		$results = array( );
		
		for( $c = 0; $c < $rows; $c++ ) {
			$results[ $c ] = $result_set->fetch( );
		}
		
		return array(
			"results" => $results,
			"num_rows" => $rows,
			"num_fields" => $fields
		);
		
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Prints the table, based on the data
	 * 
	 * @param Array containing all data to be printed.
	 * The array should contain every row, which contains every column.
	 * Empty columns should also be defined, with an empty string.
	 * 
	 * @param Array containing the header labels. Empty labels should be an empty string.
	 * 
	 * @param Array containing footer rules.
	 * Footer rules are:
	 * "total"			Prints the sum of the column in the column's footer.
	 * "average"		Prints the average of the column in the column's footer.
	 * false			Prints nothing in the column's footer.
	 * 
	 * @example
	 * $data = array(
	 * 	0 => array(
	 * 		"John",
	 * 		"Doe",
	 * 		35,
	 * 		10
	 * 	)
	 * );
	 * 
	 * $headers = array(
	 * 	"First Name", "Last Name", "Age", "Number of Products"
	 * );
	 * 
	 * $footers = array(
	 * 	false, false, "average", "total"
	 * )
	 */
	private function PrintTable( $data = false, $headers = false, $footers = false ) {
		
		echo "<table class=\"YZKTable\">";
		
		if( !is_array( $data ) || count( $data ) == 0 ) {
			echo "<tr><td>No data to display.</td></tr>";
		} else {
			
			$footer_row = array( );
			
			if( is_array( $headers ) && count( $headers ) != 0 ) {
				
				echo "<thead>";
				
					echo "<tr>";
					foreach( $headers as $header ) {
						if( $this->order_direction == "ASC" ) {
							$direction = "DESC";
						} elseif( $this->order_direction == "DESC" ) {
							$direction = "ASC";
						} else {
							$direction = $this->order_direction;
						}
						
						$link = $this->ParseURL( array( "yzk_od" => $direction, "yzk_os" => $header[ 'key' ] ) );
						
						echo "<td><a href=\"$link\">{$header[ 'label' ]}</a></td>";
					}
					echo "</tr>";
				
				echo "</thead>";
				
			}
			
			echo "<tbody>";
			
			foreach( $data as $row_key => $columns ) {
				
				echo "<tr>";
				
				$c = 0;
				
				foreach( $columns as $column_key => $column_value ) {
					
					if( !isset( $footer_row[ $c ] ) ) {
						$footer_row[ $c ] = array(
							"total" => 0,
							"amount" => 0
						);
					}
					
					if( is_numeric( $column_value ) ) {
						$footer_row[ $c ][ "total" ] += $column_value; 
						$footer_row[ $c ][ "amount" ]++; 
					}
					
					
					
					echo "<td>$column_value</td>";
					
					$c++;
					
				}
				
				echo "</tr>";
				
			}
			
			echo "</tbody>";
			
			if( is_array( $footers ) && count( $footers ) != 0 ) {
				
				echo "<tfoot>";
				
				echo "<tr>";
				
				foreach( $footer_row as $footer_key => $footer_column ) {
					
					if( $footers[ $footer_key ] != false ) {
						
						if( $footers[ $footer_key ] == "average" ) {
							
							echo "<td>" . round( $footer_column[ 'total' ] / $footer_column[ 'amount' ] ) . "</td>";
							
						} elseif( $footers[ $footer_key ] == "total" ) {
							
							echo "<td>" . $footer_column[ 'total' ] . "</td>";
							
						}
						
					} else {
						
						echo "<td></td>";
						
					}
					
				}
				
				echo "</tr>";
				
				echo "</tfoot>";
				
			}
			
		}
		
		echo "</table>";
		
	}

	private function PrintPagination( ) {
		
		echo "Showing $this->results_per_page of the $this->total_results results.";
		
		$link = $this->ParseURL( );
		
		echo "<form id=\"yzk_controls\" action=\"$link\" method=\"GET\">";
		if( $this->order_subject != false ) {
			echo "<input type=\"hidden\" name=\"yzk_os\" value=\"$this->order_subject\" />";
		}
		echo "<input type=\"hidden\" name=\"yzk_od\" value=\"$this->order_direction\" />";
		echo "<input type=\"hidden\" name=\"yzk_cp\" value=\"1\" />";
		echo "<select name=\"yzk_rpp\">";
			echo "<option value=\"10\">10</option>";
			echo "<option value=\"25\">25</option>";
			echo "<option value=\"50\">50</option>";
			echo "<option value=\"100\">100</option>";
			echo "<option value=\"250\">250</option>";
		echo "</select>";
		
		echo "<input type=\"submit\"/>";
		echo "</form>";
		
		if( $this->total_results > $this->results_per_page ) {
			
			$this->total_pages = ceil( $this->total_results / $this->results_per_page );
			
			for( $p = 1; $p < $this->total_pages; $p++ ) {
				
				$pagination_url = $this->ParseURL( array( "yzk_cp" => $p, "yzk_rpp" => $this->results_per_page ) );
				
				echo "<a href=\"$pagination_url\"> $p </a>";
				
			}
			
		}
		
	}
	
	private function ParseURL( $params = array( ) ) {
		
		$url = $_SERVER[ "REQUEST_URI" ];
		$url_parts = explode( "?", $url );
		
		if( isset( $url_parts[ 1 ] ) ) {
			
			$url_params = explode( "&", $url_parts[ 1 ] );
			
			foreach( $url_params as $key => $url_param ) {
				
				$url_param = explode( "=", $url_param );
				
				if( array_key_exists( $url_param[ 0 ], $params ) ) {
					unset( $url_params[ $key ] );
				}
				
			}
			
			foreach( $params as $p_key => $p_val ) {
				$url_params[ ] = "$p_key=$p_val";
			}
			
			$url_parts[ 1 ] = implode( "&", $url_params );
			
			$url = implode( "?", $url_parts );
			
		} else {
				
			$url = $url_parts[ 0 ];
			
		}
		
		return $url;
		
	} 
	
	public function ConnectDB( $db_location, $db_user, $db_password, $db_name = false ) {
		
		$db_connection = "mysql:host=$db_location";
		
		if( $db_name != false ) {
			$db_connection .= ";dbname=$db_name";
		}
		
		$this->DB = new PDO( $db_connection, $db_user, $db_password );
		
	}
	
	public function SetResultsPerPage( ) {
		
	}
	
	public function GetResultsPerPage( ) {
		
	}
	
	public function SetOrder( $column_name, $order_direction = "ASC" ) {
		
		$this->order_subject = $column_name;
		$this->order_direction = $order_direction;
		
	}
	
	public function GetOrder( ) {
		
	}
	
	public function SetSearch( ) {
		
	}
	
	public function GetSearch( ) {
		
	}
	
	private function SearchData( ) {
		
	}
	
	private function ExportCSV( ) {
		
	}
	
	private function ExportXML( ) {
		
	}
	
	private function PrepareQuery( $query ) {
		
		$total_query = "SELECT COUNT( * ) FROM ( $query ) AS `yzk_origin`;";
		$result_set = $this->DB->prepare( $total_query );
		$query_result = $result_set->execute( );
		
		$this->total_results = $result_set->fetchColumn( 0 );
		
		if( $this->results_per_page > $this->total_results ) {
			$this->results_per_page = $this->total_results;
		}
		
		if( $this->order_subject != false ) {
			$query = $query . " ORDER BY $this->order_subject $this->order_direction ";
		}
		
		if( $this->current_page <= 1 ) {
			$start_limit = 0;
		} else {
			$start_limit = ( $this->current_page * $this->results_per_page );
		}
		$end_limit = $this->results_per_page;
		
		$query = $query . " LIMIT $start_limit, $end_limit";
		
		return $query;
		
	}
	
	public function RenderTable( $query, $headers = false, $footers = false ) {
		
		$query = $this->PrepareQuery( $query );
		
		$results = $this->ExecuteQuery( $query );
		$data = $results[ 'results' ];
		
		$this->PrintPagination( );
		
		$this->PrintTable( $data, $headers, $footers );
		
		$this->PrintPagination( );
	}
	
}

?>