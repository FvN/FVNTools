<?php

error_reporting( E_ALL );
ini_set( "display_errors", true );

class FVNTools {
	
	public function FVNTools( $action = '' ) {
		
		switch( $action ) {
			
			case "json_convert":
				
				$this->JSONConverterForm( );
				
				break;
			
			case "md5_encode":
				
				$this->MD5EncoderForm( );
				
				break;
			
			case "html2php_convert":
				
				$this->HTML2PHPConverterForm( );
				
				break;
				
			default:
				
				$this->ToolBelt( );
				
				break;
			
		}
		
	}
	
	private function ToolBelt( ) {
		echo "<ul>";
			echo "<li><a href=\"?a=json_convert\">JSON Converter</a></li>";
			echo "<li><a href=\"?a=md5_encode\">MD5 Encoder</a></li>";
			echo "<li><a href=\"?a=html2php_convert\">HTML 2 PHP Converter</a></li>";
		echo "</ul>";
	}
	
	private function JSONConverterForm( ) {
		
		if( !empty( $_POST[ 'json_string' ] ) ) {
			// -------------------------------------------------- | START JSON -> PHP
			$json_string = $_POST[ 'json_string' ];
			
			$php_array = json_decode( $json_string );
			$php_array = $this->StdToAssoc( $php_array );
			
			ob_start( );
			
			var_export( $php_array );
			
			$php_array = ob_get_clean( );
			
			
			$json_string = "";
			// -------------------------------------------------- | END
		}
		if( !empty( $_POST[ 'php_array' ] ) ) {
			// -------------------------------------------------- | START PHP -> JSON
			$php_array = $_POST[ 'php_array' ];
			
			$php_array = eval( "return $php_array;" );
			
			$json_string = json_encode( $php_array );
			
			$php_array = "";
			// -------------------------------------------------- | END
		}
		
		echo "<form action='?a=json_convert' method='post'>";
			
			echo "<label>JSON String</label>";
			
			if( isset( $json_string ) ) {
				echo "<textarea name=\"json_string\">$json_string</textarea>";
			} else {
				echo "<textarea name=\"json_string\"></textarea>";
			}
			
			echo "<br/>";
			
			echo "<label>PHP Array</label>";
			
			if( isset( $php_array ) ) {
				echo "<textarea name=\"php_array\">$php_array</textarea>";
			} else {
				echo "<textarea name=\"php_array\"></textarea>";
			}
			
			echo "<input type=\"submit\" />";
		
		echo "</form>";
		
		$this->ReturnLink( );
		
	}

	private function MD5EncoderForm( ) {
		
		if( isset( $_POST[ 'string' ] ) ) {
			
			$string = md5( $_POST[ 'string' ] );
			
		}
		
		echo "<form action='?a=md5_encode' method='post'>";
			
			echo "<label>String to Encode:</label>";
			
			if( isset( $string ) ) {
				echo "<textarea name=\"string\">$string</textarea>";
			} else {
				echo "<textarea name=\"string\"></textarea>";
			}
			
			echo "<br/>";
			
			echo "<input type=\"submit\" />";
		
		echo "</form>";
		
		$this->ReturnLink( );
		
	}
	
	function HTML2PHPConverterForm( ) {
		
		if( isset( $_POST[ 'html' ] ) ) {
			
			$html = $_POST[ 'html' ];
			
			$php = str_replace( "\"", '\"', $html );
	
			$php_parts = explode( "\r\n", $php );
			
			$complete = array( );
			foreach( $php_parts as $part ) {
				$part = "echo \"" . $part . "\";";
				
				$complete[ ] = $part;
			}
			
			$php = implode( "\r\n", $complete );
			
		}
		
		echo "<form action='?a=html2php_convert' method='post'>";
			
			echo "<label>HTML:</label>";
			if( isset( $html ) ) {
				echo "<textarea name=\"html\">$html</textarea>";
			} else {
				echo "<textarea name=\"html\"></textarea>";
			}
			
			echo "<label>PHP Code:</label>";
			if( isset( $php ) ) {
				echo "<textarea readonly>$php</textarea>";
			}
			
			echo "<br/>";
			
			echo "<input type=\"submit\" />";
			
		echo "</form>";
		
		$this->ReturnLink( );
		
	}

	function ReturnLink( ) {
		echo "<a href='FVNTools.php'>Back</a>";
	}

	/**
	 * @author Fabian van Nieuwmegen
	 * 
	 * @desc
	 * Function that recursively converts an StdClass into an associative array.
	 * 
	 * @param String $json			A JSON-encoded string
	 * 
	 * @return Array $non_json		A JSON-decoded array
	 */
	function StdToAssoc( $std_class ) {
		$assoc_array = array( );
		
		foreach( $std_class as $key => $value ) {
			if( is_object( $value ) == true || is_array( $value ) == true ) {
				$assoc_array[ $key ] = nfStdToAssoc( $value );
			} else {
				$assoc_array[ $key ] = $value;
			}
		}
		
		return $assoc_array;
	}
	
}

if( isset( $_GET[ 'a' ] ) ) {
	$a = $_GET[ 'a' ];
} else {
	$a = "";
}

$FVNTools = new FVNTools( $a );

?>