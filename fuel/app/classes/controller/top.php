<?php

class Controller_Top extends Controller_Base
{

	public function action_index()
	{
		$file_handle = fopen("en.csv", "r");
		
		$fp = file("en.csv", FILE_SKIP_EMPTY_LINES);
		
		$lines = count($fp);
		
		$row = 0;
		while (!feof($file_handle) ) {
			$row++;
			$line_of_text = fgets($file_handle);
			$parts = explode('=', $line_of_text);
			
			$exp = explode(',', $parts[0]);
			
			switch ($lines) {
				case "1":
					$header = $exp[1];
					break;
				case "2":
					$subheader = $exp[1];
					break;
				case "3":
					$item = explode('-', $exp[1]);
					$menu1 = $item[0];
					$menu2 = $item[1];
					$menu3 = $item[2];
					$menu4 = $item[3];
					$menu5 = $item[4];
					$menu6 = $item[5];
					break;
				case "4":
					$item = explode('-', $exp[1]);
					$memSettings1 = $item[0];
					$memSettings2 = $item[1];
					break;
				case "5":
					$item = explode('-', $exp[1]);
					$headButton = $item[0];
					break;
				default:
					break;
			}
		
// 			if($row === 1) {
// 				$exp = explode(',', $parts[0]);
// 				$header = $exp[1];
// 			}elseif ($row == 2) {
// 				$exp = explode(',', $parts[0]); 
// 				$subheader = $exp[1];
// 			}elseif($row === 3) {
// 				$exp = explode(',',$parts[0]);
// 				$item = explode('-', $exp[1]); 
// 				$menu1 = $item[0];
// 				$menu2 = $item[1];
// 				$menu3 = $item[2];
// 				$menu4 = $item[3];
// 				$menu5 = $item[4];
// 				$menu6 = $item[5];
// 			}elseif ($row === 4) {
// 				$exp = explode(',',$parts[0]); 
// 				$item = explode('-', $exp[1]);
// 				$memSettings1 = $item[0];
// 				$memSettings2 = $item[1];
// 			}elseif ($row === 5) {
// 				$exp = explode(',',$parts[0]);
// 				$item = explode('-', $exp[1]);
// 				$headButton = $item[0];
// 			}
		}
		fclose($file_handle);
		
		
		View::set_global(array(
				'memSettings1' => $memSettings1,
				'memSettings2' => $memSettings2,
				'menu1' => $menu1,
				'menu2' => $menu2,
				'menu3' => $menu3,
				'menu4' => $menu4,
				'menu5' => $menu5,
				'menu6' => $menu6,
				'headButton' => $headButton,
				'header' => $header,
				'subheader' => $subheader,
		), null, true);
		
		$this->template->title = "Top";
		$view = View::forge("top");
		$this->template->content = $view;
	}
}