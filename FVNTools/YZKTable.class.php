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
	private $page_links = 10;
	
	/**
	 * @desc
	 * The id of the current page.
	 */
	private $debug = false;
	
	/**
	 * @desc
	 * Whether to print zebra-stripes or not.
	 */
	private $zebra = false;
	
	/**
	 * @desc
	 * Whether we're in AJAX mode or not.
	 * AJAX mode returns data in JSON, instead of printing.
	 */
	private $ajax_mode = false;
	
	/**
	 * @desc Which option fields to show or not.
	 */
	private $show_options = array(
		"pagination" => true,
		"results_per_page" => true,
		"search" => true,
		"csv_export" => true
	);
	
	/**
	 * @desc
	 * Whether to export all results, or not.
	 * If not, only export current page.
	 */
	private $export_all = true;
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Constructor. Takes $_GET parameters and allocates them into the class.
	 */
	public function YZKTable( ) {
		
		global $YZK_DB;
		
		$this->DB = $YZK_DB;
		
		$this->RouteRequests( );
	}
	
	private function RouteRequests( ) {
		
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
		
		if( isset( $_GET[ 'yzk_search' ] ) ) {
			$this->search = $_GET[ 'yzk_search' ];
		}
		
		if( isset( $_GET[ 'yzk_export' ] ) ) {
			
			if( $_GET[ 'yzk_export' ] == "page" ) {
				
				$this->export_all = false;
				
			} else {
				
				$this->export_all = true;
				
			}
		}
		
		if( isset( $_GET[ 'yzk_act' ] ) ) {
			
			$action = $_GET[ 'yzk_act' ];
			
			switch( $action ) {
				
				case "export_csv":
					$this->ExportCSV( );
					break;
				
			}
			
		}
		
		if( isset( $_GET[ 'yzk_ajax' ] ) ) {
			if( $_GET[ 'yzk_ajax' ] == "true" ) {
				$this->ajax_mode = true;
			} else {
				$this->ajax_mode = false;
			}
		}
		
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Handles the whole process of getting the data and printing it.
	 * 
	 * @param MySQL SELECT Query
	 * @param Array of headers. Should contain label and key for each entry. Each column should be defined.
	 * @param Array of what to do with footers (average, total or false). Each column should be defined.
	 */
	public function RenderTable( $query, $headers = false, $footers = false ) {
		
		if( $this->ajax_mode == true ) {
			$query = $_SESSION[ 'YZK_LAST_BASE_QUERY' ];
			
			$query = $this->PrepareQuery( $query );
			
			$results = $this->ExecuteQuery( $query );
			$data = $results[ 'results' ];
			
			$this->JSONTable( $data, $headers, $footers );
		} else {
			$query = $this->PrepareQuery( $query );
			
			$results = $this->ExecuteQuery( $query );
			$data = $results[ 'results' ];
			
			$this->PrintPagination( );
			
			$this->PrintTable( $data, $headers, $footers );
			
			$this->PrintPagination( );
		}
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Does all preparations needed to execute the given query.
	 * Gets total amount of results, etc.
	 * 
	 * @param MySQL query
	 * 
	 * @return MySQL query
	 */
	private function PrepareQuery( $query ) {
		
		$_SESSION[ 'YZK_LAST_BASE_QUERY' ] = $query;
		
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
		
		if( $this->search == false ) {
			if( $this->current_page <= 1 ) {
				$start_limit = 0;
			} else {
				$start_limit = ( $this->current_page * $this->results_per_page );
			}
			$end_limit = $this->results_per_page;
			
			$query = $query . " LIMIT $start_limit, $end_limit";
		}
		
		$_SESSION[ 'YZK_LAST_QUERY' ] = $query;
		
		return $query;
		
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
	 * 
	 * @return Array containing all results, no. of rows and no. of columns. Boolean (false) on failure.
	 */
	public function ExecuteQuery( $query, $associative_results = true ) {
		
		if( $this->debug == true ) {
			echo "
			<!---
				Parsed Query:
				
				$query
			--->
			";
		}
		
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
		
		if( $this->search != false ) {
			$search_results = array( );
			
			$c = 0;
			foreach( $results as $row_key => $columns ) {
				
				$show_row = false;
				
				foreach( $columns as $col_key => $col_value ) {
					if( strpos( $col_value, $this->search ) !== false ) {
						$show_row = true;
					}
				}
				
				if( $show_row == true ) {
					$search_results[ $c ] = $columns;
				}
				
				$c++;
			}
			
			$results = $search_results;
			
			$rows = count( $search_results );
			
			$this->total_results = $rows;
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
	 * Prints pagination and other options for the table.
	 * Uses class variable show_options to determine which option fields to show.
	 */
	private function PrintPagination( ) {
		
		$link = $this->ParseURL( array( "yzk_ajax" => "true" ) );
		
		if( $this->show_options[ 'results_per_page' ] == true || $this->show_options[ 'search' ] == true ) {
			echo "<form class=\"yzk_controls\" action=\"$link\" method=\"GET\">";
			if( $this->order_subject != false ) {
				echo "<input type=\"hidden\" name=\"yzk_os\" value=\"$this->order_subject\" />";
			}
			echo "<input type=\"hidden\" name=\"yzk_od\" value=\"$this->order_direction\" />";
			echo "<input type=\"hidden\" name=\"yzk_cp\" value=\"1\" />";
			
			if( $this->show_options[ 'csv_export' ] == true ) {
				$export_link = $this->ParseURL( array( "yzk_act" => "export_csv" ) );
				
				echo "<a target=\"_blank\" href=\"$export_link\">Export all results as CSV</a>";
				
				$export_link = $this->ParseURL( array( "yzk_act" => "export_csv", "yzk_export" => "page" ) );
				echo "<a target=\"_blank\" href=\"$export_link\">Export current page as CSV</a>";
			}
			
			if( $this->show_options[ 'results_per_page' ] == true ) {
				echo "<div class=\"yzk_results_per_page_controls\">";
					echo "<label for=\"yzk_rpp_select\">Results per page:</label>";
					echo "<select id=\"yzk_rpp_select\" name=\"yzk_rpp\">";
						echo "<option value=\"10\">10</option>";
						echo "<option value=\"25\">25</option>";
						echo "<option value=\"50\">50</option>";
						echo "<option value=\"100\">100</option>";
						echo "<option value=\"250\">250</option>";
					echo "</select>";
				echo "</div>";
			}
			
			if( $this->show_options[ 'search' ] == true ) {
				echo "<div class=\"yzk_search_controls\">";
					echo "<label for=\"yzk_search_field\">Search:</label>";
					echo "<input type=\"text\" name=\"yzk_search\" id=\"yzk_search_field\" value=\"$this->search\" />";
				echo "</div>";
			}
			
			echo "<input type=\"submit\"/>";
			echo "</form>";
		}
		
		if( $this->show_options[ 'pagination' ] == true ) {
			echo "<div class=\"yzk_pagination\">";
				echo "<span class=\"yzk_showing_results\">Showing $this->results_per_page of the $this->total_results results.</span>";
				
				$pagination_url = $this->ParseURL( array( "yzk_cp" => 1, "yzk_rpp" => $this->results_per_page, "yzk_ajax" => "true" ) );
				echo "<a class=\"yzk_page_link yzk_first_page\" href=\"$pagination_url\"> &laquo; </a>";
				
				$previous_page = ( $this->current_page - 1 );
				
				if( $previous_page > 0 ) {
					
					$pagination_url = $this->ParseURL( array( "yzk_cp" => $previous_page, "yzk_rpp" => $this->results_per_page, "yzk_ajax" => "true" ) );
					echo "<a class=\"yzk_page_link yzk_previous_page\" href=\"$pagination_url\"> &lsaquo; </a>";
					
				}
				
				if( $this->total_results > $this->results_per_page && $this->search == false ) {
					
					$this->total_pages = ceil( $this->total_results / $this->results_per_page );
					
					$halfway = floor( $this->page_links / 2 );
					
					for( $c = 1; $c < $this->page_links; $c++ ) {
						
						if( $this->current_page > $halfway && $this->current_page < ( $this->total_pages - $halfway ) ) {
							
							if( $c < ( $halfway ) ) {
								
								$p = ( $this->current_page - ( $halfway - $c ) );
								
							} elseif( $c > $halfway ) {
								
								$p = ( $this->current_page + ( $c ) - $halfway );
								
							} else {
								
								$p = $this->current_page;
								
							}
							
						} elseif( $this->current_page >= ( $this->total_pages - $halfway ) ) {
							
							$p = ( $this->total_pages - $this->page_links + $c );
							
						} else {
							
							$p = $c;
							
						}
						
						$pagination_url = $this->ParseURL( array( "yzk_cp" => $p, "yzk_rpp" => $this->results_per_page, "yzk_ajax" => "true" ) );
						
						if( $this->current_page == $p ) {
							echo "<strong class=\"yzk_current_page\"> $p </strong>";
						} else {
							echo "<a class=\"yzk_page_link\" href=\"$pagination_url\"> $p </a>";
						}
						
					}
					
					$next_page = ( $this->current_page + 1 );
					
					if( $next_page < $this->total_pages ) {
						
						$pagination_url = $this->ParseURL( array( "yzk_cp" => $next_page, "yzk_rpp" => $this->results_per_page, "yzk_ajax" => "true" ) );
						echo "<a class=\"yzk_page_link yzk_next_page\" href=\"$pagination_url\"> &rsaquo; </a>";
						
					}
					
					$pagination_url = $this->ParseURL( array( "yzk_cp" => ( $this->total_pages - 1 ), "yzk_rpp" => $this->results_per_page, "yzk_ajax" => "true" ) );
					echo "<a class=\"yzk_page_link yzk_last_page\" href=\"$pagination_url\"> &raquo; </a>";
				}
			
			echo "</div>";
			
		}
		
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
		
		echo "<table class=\"yzk_table\">";
		
		if( !is_array( $data ) || count( $data ) == 0 ) {
			echo "<tr><td>No data to display.</td></tr>";
		} else {
			
			$footer_row = array( );
			
			if( is_array( $headers ) && count( $headers ) != 0 ) {
				
				echo "<thead>";
					
					echo "<tr>";
					foreach( $headers as $header ) {
						
						$direction_indicator = "";
						
						if( $this->order_direction == "ASC" ) {
							$direction = "DESC";
						} elseif( $this->order_direction == "DESC" ) {
							$direction = "ASC";
						} else {
							$direction = $this->order_direction;
						}
						
						if( $this->order_subject == $header[ 'key' ] ) {
							
							if( $direction == "DESC" ) {
								$direction_indicator = " <strong class=\"yzk_order_indicator yzk_order_asc\">&and;</strong>";
							} else {
								$direction_indicator = " <strong class=\"yzk_order_indicator yzk_order_desc\">&or;</strong>";
							}
							
						}
						
						$link = $this->ParseURL( array( "yzk_od" => $direction, "yzk_os" => $header[ 'key' ], "yzk_ajax" => "true" ) );
						
						echo "<td><a href=\"$link\">{$header[ 'label' ]}$direction_indicator</a></td>";
					}
					echo "</tr>";
				
				echo "</thead>";
				
			}
			
			echo "<tbody>";
			
			$r = 0;
			
			foreach( $data as $row_key => $columns ) {
				
				if( $this->zebra == true ) {
					if( $r % 2 == 0 ) {
						
						$class = " class=\"yzk_even\" ";
						
					} else {
						
						$class = " class=\"yzk_odd\" ";
						
					}
				} else {
					$class= "";
				}
				
				echo "<tr $class>";
				
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
				
				$r++;
				
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
	
	private function JSONTable( $data = false, $headers = false, $footers = false ) {
		
		if( !is_array( $data ) || count( $data ) == 0 ) {
			echo json_encode( array( "error" => "No data to display." ) );
		} else {
			
			$return_data = array( );
			
			$footer_row = array( );
			
			$row_counter = 0;
			
			if( is_array( $headers ) && count( $headers ) != 0 ) {
				
				foreach( $headers as $header ) {
					
					if( $this->order_direction == "ASC" ) {
						$direction = "DESC";
					} elseif( $this->order_direction == "DESC" ) {
						$direction = "ASC";
					} else {
						$direction = $this->order_direction;
					}
					
					$link = $this->ParseURL( array( "yzk_od" => $direction, "yzk_os" => $header[ 'key' ], "yzk_ajax" => "true" ) );
					
					$return_data[ "header" ][ "columns" ][ ] = array(
						"link" => $link,
						"value" => $header[ 'label' ]
					);
				}
				
			}
			
			$r = 0;
			
			foreach( $data as $row_key => $columns ) {
				
				if( $this->zebra == true ) {
					if( $r % 2 == 0 ) {
						$class = "yzk_even";
					} else {
						$class = "yzk_odd";
					}
				} else {
					$class= "";
				}
				
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
					
					$return_data[ "rows" ][ $row_counter ][ "columns" ][ ] = $column_value;
					
				}
				
				$return_data[ "rows" ][ $row_counter ][ "class" ] = $class;
				
				$r++;
				$row_counter++;
				
			}
			
			if( is_array( $footers ) && count( $footers ) != 0 ) {
				
				foreach( $footer_row as $footer_key => $footer_column ) {
					
					if( $footers[ $footer_key ] != false ) {
						if( $footers[ $footer_key ] == "average" ) {
							$return_data[ "footer" ][ "columns" ][ ] = round( $footer_column[ 'total' ] / $footer_column[ 'amount' ] );
						} elseif( $footers[ $footer_key ] == "total" ) {
							$return_data[ "footer" ][ "columns" ][ ] = $footer_column[ 'total' ];
						}
					} else {
						$return_data[ "footer" ][ "columns" ][ ] = "";
					}
					
				}
				
				
			}
			
		}
		
		echo json_encode( $return_data );
		
		exit;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Parses the URL to prevent duplicate parameters.
	 * 
	 * @param Array of parameters (key = parameter name, value = parameter value) to add/replace on the URL.
	 * @param The URL to parse (Default: REQUEST_URI)
	 * 
	 * @return URL
	 */
	private function ParseURL( $params = array( ), $url = false ) {
		
		if( $url == false ) {
			$url = $_SERVER[ "REQUEST_URI" ];
		}
		
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
			
			$url_params = array( );
			
			foreach( $params as $p_key => $p_val ) {
				$url_params[ ] = "$p_key=$p_val";
			}
			
			$url_parts[ 1 ] = implode( "&", $url_params );
			
			$url = implode( "?", $url_parts );
			
		}
		
		return $url;
		
	}
	
	private function ExportCSV( ) {
		
		$csv_download_dir = "downloads/";
		$csv_file_name = "CSV-Download-" . date( "Ymd-His" ) . ".csv";
		
		// Write file
		if( $this->export_all == true ) {
			$sql = $_SESSION[ 'YZK_LAST_BASE_QUERY' ];
		} else {
			$sql = $_SESSION[ 'YZK_LAST_QUERY' ];
		}
		
		$data = $this->ExecuteQuery( $sql );
		
		// $file_handler = fopen( $csv_download_dir . $csv_file_name, "w+" );
		
		header( "Content-Type: application/csv" );
		header( "Content-Disposition: attachment; filename=$csv_file_name" );
		header( "Pragma: no-cache" );
		
		foreach( $data[ "results" ] as $rows ) {
			$line = "";
			
			$c = 0;
			foreach( $rows as $column ) {
				$line .=  "\"$column\",";
				
				$c++;
			}
			
			$line = substr( $line, 0, -1 );
			
			$line .= "\n";
			// echo "\n";
			
			echo $line;
			// fwrite( $file_handler, $line );
		}
		
		
		// fclose( $file_handler );
		
		// header( "Content-Type: application/csv" );
		// header( "Content-Disposition: attachment; filename=$csv_file_name" );
		// header( "Pragma: no-cache" );
		// readfile( $csv_download_dir . $csv_file_name );
		
		exit;
	}
	
	private function ExportXML( ) {
		
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Sets results_per_page class variable
	 */
	public function SetResultsPerPage( $results_per_page = 25 ) {
		$this->results_per_page = $results_per_page;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Gets results_per_page class variable
	 */
	public function GetResultsPerPage( ) {
		return $this->results_per_page;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Sets order_direction and order_subject class variables
	 */
	public function SetOrder( $column_name, $order_direction = "ASC", $order_type = "ABC" ) {
		
		$this->order_subject = $column_name;
		$this->order_direction = $order_direction;
		
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Gets order related class variables
	 */
	public function GetOrder( ) {
		return array(
			"subject" => $this->order_subject,
			"direction" => $this->order_direction,
			"type" => $this->order_type
		);
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Sets search class variable
	 */
	public function SetSearch( $search = false ) {
		$this->search = $search;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Gets search class variable
	 */
	public function GetSearch( ) {
		return $this->search;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Sets zebra class variable
	 */
	public function SetZebra( $zebra = false ) {
		$this->zebra = $zebra;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Gets zebra class variable
	 */
	public function GetZebra( ) {
		return $this->zebra;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Sets show_options class variable
	 */
	public function SetShowOptions( $pagination = true, $results_per_page = true, $search = true, $csv_export = true ) {
		$this->show_options[ 'pagination' ] = $pagination;
		$this->show_options[ 'results_per_page' ] = $results_per_page;
		$this->show_options[ 'search' ] = $search;
		$this->show_options[ 'csv_export' ] = $csv_export;
	}
	
	/**
	 * @author Yazuake
	 * 
	 * @desc
	 * Gets show_options class variable
	 */
	public function GetShowOptions( ) {
		return $this->show_options;
	}
	
}

?>