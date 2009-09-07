<?php
/**
 * PHP API for jQuery Image Annotation Plugin
 * @author Stas SUSHKOV <stas@nerd.ro>
 * @version 0.2
 * @since PHP 5.2
 */

class jQueryAnnotate {

	/**
	 * Constructor
	 * Opens and reads items from the data file
	 *
	 * @param string $filename of the data file, default is 'data.csv'
	 *
	 * @access public
	 * @return void
	 */
	function __construct($filename = "data.csv") {
		$this->filename = $filename;
		$this->file = @fopen($filename, "r+");
		$this->items = $this->csv_to_array();
	}

	/**
	 * Outputs data file content in a JSON format
	 *
	 * @access public
	 * @return void
	 */
	function get() {
		$items = $this->items;
		echo "[\n";
		foreach($items as $item) {
			if($item) {
				$a = explode("\t", $item);
				echo $this->format($a);
			}
		}
		echo "]\n";
	}

	/**
	 * Adds a new annotation into the data file
	 * All HTML and special characters are stripped out
	 * As a result outputs the new saved id of the annotation
	 *
	 * @access public
	 * @return void
	 */
	function save() {
		$data = array(
			$_GET["top"],  
			$_GET["left"],  
			$_GET["width"],  
			$_GET["height"], 
			$this->html2txt($_GET["text"]),  
			"id_".md5($_GET["text"]),
			"true"
		);
		
		$this->delete($data); // Delete previous entry
		if($data[4] && file_put_contents( $this->filename, implode("\t",$data)."\n", FILE_APPEND | LOCK_EX ))
			echo '{ "annotation_id": "id_'.md5($_GET["text"]).'" }';
	}

	/**
	 * Deletes an annotation from the data file
	 * As a result outputs a 200 HTTP header
	 *
	 * @access public
	 * @return void
	 */
	function delete($to_delete_old = null) {
		if(!$to_delete_old)
			$to_delete = array(
				$_GET["top"],
				$_GET["left"],
				$_GET["width"],
				$_GET["height"],
				$_GET["text"],
				$_GET["id"]
			);

		$items = $this->items;
		$i = 0;
		foreach($items as $item) {
			if($item) {
				$item = explode("\t", $item);
				if( !$to_delete_old ) {
					if( ($item[5] == $to_delete[5]) && ($item[6] == "true\n") )
						unset($items[$i]);
				}
				else {
					// If top and left exist already, than delete them due to exclude clones
					if( ($item[0] == $to_delete_old[0]) && ($item[1] == $to_delete_old[1]) && ($item[6] == "true\n") ) 
						unset($items[$i]);
				}
			}
			$i++;
		}
		if( file_put_contents( $this->filename, implode("",$items), LOCK_EX ))
			header("HTTP/1.0 200 OK");
	}

	/**
	 * Converts the csv content from data file to an array
	 *
	 * @access public
	 * @return array
	 */
	function csv_to_array() {
		if($this->file) {
			$csv_array = array();
			while (!feof($this->file)) {
				$csv_array[] = fgets($this->file);
			}
		}
		else
			$this->print_error("Could not open file.");
		return $csv_array;
	}

	/**
	 * Converts an array to a JSON required format
	 *
	 * @param array $t item from the data file content array
	 * @access public
	 * @return string
	 */
	function format($t) {
		if(is_array($t) && count($t) == 7)
			return "
				{
					\"top\":	$t[0],
					\"left\":	$t[1],
					\"width\":	$t[2],
					\"height\":	$t[3],
					\"text\":	\"$t[4]\",
					\"id\":		\"$t[5]\",
					\"editable\":	$t[6]
				},
			";
	}

	/**
	 * Strip a string from unwanted tags and special characters
	 *
	 * @param string $text to be cleared
	 * @access public
	 * @return string
	 */
	function html2txt($text) {
		$search = array ('@<script[^>]*?>.*?</script>@si',	// Strip out javascript
				 '@<[\/\!]*?[^<>]*?>@si',		// Strip out HTML tags
				 '@([\r\n])[\s]+@',			// Strip out white space
				 '@&(quot|#34);@i',			// Replace HTML entities
				 '@&(lt|#60);@i',
				 '@&(gt|#62);@i',
				 '@&(nbsp|#160);@i',
				 '@&#(\d+);@e');			// evaluate as php

		$replace = array ('',
				 '',
				 '\1',
				 '"',
				 '<',
				 '>',
				 ' ',
				 'chr(\1)');

		return trim(preg_replace($search, $replace, $text));
	}

	/**
	 * Prints a <pre> formated error message
	 *
	 * @param string $m message to be printed as an error
	 * @access public
	 * @return string
	 */
	function print_error($m) {
		die("<pre>$m</pre>\n");
	}

	/**
	 * Destructor
	 * Closes the file the constructor opened.
	 *
	 * @access public
	 * @return void
	 */
	function __destruct() {
		fclose($this->file);
	}
}	
?>
