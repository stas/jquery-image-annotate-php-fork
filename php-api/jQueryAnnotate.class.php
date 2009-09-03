<?php
	class jQueryAnnotate {

		function __construct($filename = "data.csv") {
			$this->filename = $filename;
			$this->file = @fopen($filename, "r+");
			$this->items = $this->csv_to_array();
		}

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

		function save() {
			$data = array(
				$_GET["top"],  
				$_GET["left"],  
				$_GET["width"],  
				$_GET["height"], 
				$_GET["text"],  
				"id_".md5($_GET["text"]),
				"true"
			);

			if( file_put_contents( $this->filename, implode("\t",$data)."\n", FILE_APPEND | LOCK_EX ))
				echo '{ "annotation_id": "id_'.md5($_GET["text"]).'" }';
		}

		function delete() {
			$to_delete = array(
				$_GET["top"],
				$_GET["left"],
				$_GET["width"],
				$_GET["height"],
				$_GET["text"],
				$_GET["id"],
			);

			$items = $this->items;
			$i = 0;
			foreach($items as $item) {
				if($item) {
					$item = explode("\t", $item);
				        if(($item[5] == $to_delete[5]) && ($item[6] == "true\n"))
						unset($items[$i]);
				}
				$i++;
			}
			if( file_put_contents( $this->filename, implode("",$items), LOCK_EX ))
				header("HTTP/1.0 200 OK");
		}

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

		function format($t) {
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

		function print_error($m) {
			echo "<pre>$m</pre>\n";
		}

		function __destruct() {
			fclose($this->file);
		}
	}	
?>
