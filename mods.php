<?php

// Get Categories - DITE (https://tcg.dite-hart.net/)
function get_category( $tcg, $category) {

	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	$category = $sanitize->for_db($category);
	$altname = strtolower(str_replace(' ','',$tcg));

	$result = $database->get_assoc("SELECT `id` FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $result['id'];
	
	$result = $database->get_assoc("SELECT `cards` FROM `cards` WHERE `tcg`='$tcgid' AND `category`='$category' LIMIT 1");
	return $result['cards'];

}

// Pending Cards - DITE (https://tcg.dite-hart.net/)
/*
<div id="needed"><?php show_owned( '$tcg', '$catagory'); ?></div>
*/

function pending_check($tcg, $card){
	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$pending = $database->num_rows("SELECT * FROM `trades` WHERE `tcg`='$tcgid' AND `receiving` LIKE '%$card%'");
	$pending = ($pending === 0 ? false : true);
	return $pending;
}

// Show needed Cards - DITE (https://tcg.dite-hart.net/)
/*
#needed-deck {
  font-weight: normal;
  color: #888888;
  text-align: left;
  display: inline-block;
  width: 31%;
  margin: 0;
  border-top: 1px solid #eeeeee;
  vertical-align: top;
}
#needed-cards {
  font-weight: normal;
  color: #888888;
  text-align: left;
  display: inline-block;
  width: 68%;
  margin: 0;
  border-top: 1px solid #eeeeee;
  vertical-align: top;
}
#needed-trading #needed-cards {
  color: white !important;
}
#needed-pending {
  font-weight: normal;
  color: #FFC03C;
text-shadow: 1px 1px 2px rgba(255, 192, 60, 0.5);
}
#needed {
  text-align: left;
  font-family: calibri;
  font-size: 11px;
}
#needed b, #needed strong {
  text-align: left;
  font-family: calibri;
  font-size: 12px;
  font-weight: normal;
  letter-spacing: 1px;
}

<div id="needed"><?php show_needed( '$tcg', 'collecting', '1', '1'); ?></div>

<div id="needed"><?php show_needed( '$tcg', '$catagory', '$count', '1'); ?></div>
*/

function show_needed( $tcg, $category, $count,$pend = 0, $low = 0) {
  $total = array();
   if (strtolower($category)==='collecting'){
      $database = new Database;
   $sanitize = new Sanitize;
   $tcg = $sanitize->for_db($tcg);
   $tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
   $tcgid = $tcginfo['id'];
   $worth = intval($count);
   $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '0' AND `worth` = '$worth' ORDER BY `sort`, `deck`");
   $cards = '';
   while ( $row = mysqli_fetch_assoc($result) ) {$cards .= $row['cards'].', '; $total[$row['deck']] = $row['count'];}
   $cards = substr($cards,0,-2);} else {	$cards = get_category($tcg, $category); }
   $cards = explode(', ',$cards);
   $cards = array_unique($cards); array_walk($cards, 'trim_value');
     $deck = array( );
     //Get decks
   foreach ($cards as $card) {$deck[ ] = substr($card, 0, -2);}
     $group = array_combine($cards,$deck);
     $deck = array_unique($deck);
   //Results
     foreach ($deck as $check) {
   echo '<span id="needed-deck"><b>'; 
     if(isset($total[$check])){$all = $total[$check];} else{$all = $count;}
     $mine = array();
     $got = array_keys($group, $check);
     foreach ($got as $num) {$mine[ ] = substr($num, -2); }
     $def = range(1,$all);
     $default = array( );
     foreach($def as $no){if($no < 10){$default[ ] = '0'.$no;} else {$default[ ] = $no;}}
     $diff = array_diff($default,$mine);
     foreach($diff as &$might){
       $pending = pending_check($tcg, $check.$might);
       if($pending > 0){
         if($pend === 1){$might = '<u>'.$might.'</u>';}
         else{ $might = '<span id="needed-pending">'.$might.'</span>';}
       }
     }
     $diff = array_filter($diff, 'strlen');
     $need = count($diff);
     if($low === 0 || $need <= $low){
       $diff = implode(', ',$diff);
       echo $check.'</b></span> <span id="needed-cards">'.$diff.'</span><br/>';
     }
   }
 }

 // Show Pending in Keeping - DITE (https://tcg.dite-hart.net/)
/*
<?php show_pendcards('$tcg','$catagory'); ?>
*/
 function show_pendcards($tcg, $category, $pendname='pending') {
	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	$cards = get_category($tcg, $category); $cards = explode(', ',$cards);
	$list = get_additional($tcg, $category);
		if(empty($list)){
			$list = array();
			foreach($cards as $card){$deck = substr($card,0,-2); $list[] = $deck;}
			$list = array_unique($list);
			sort($list);
		} else {$list = explode(', ',$list);}
	$pend = $database->query("SELECT * FROM `trades` WHERE `tcg`='$tcgid'");
	$pending = array();
	// Gets all pending cards
	if(mysqli_num_rows($pend)>0){
		while($p=mysqli_fetch_assoc($pend)){
			if(!empty($p['receivingcat'])){
				$cats = explode(', ',$p['receivingcat']);
				$divide = explode('; ', $p['receiving']);
				for($i=0;$i<count($cats);$i++){
					if($cats[$i]===$category){$pending = array_merge($pending, explode(', ',$divide[$i]));}
				}
			}
			else{
				$divide = explode(', ', $p['receiving']);
				foreach($divide as $pendcard){
					if(in_array(substr($pendcard,0,-2),$list)){$pending[] = $pendcard;}
				}
			}
		}
	}
	array_walk($pending,'trim_value');
	//Makes a deck array with cards.
	$cards = array_unique($cards);
	$cards = array_combine($cards, $cards);
	//Adds related pending cards to category.
	foreach($pending as $check){
		if(empty($cards[$check])){$cards[$check] = $pendname;}
		}
	if ( empty($cards) ) { echo '<p><em>There are currently no cards under this category.</em></p>'; }
	else {
	uksort($cards, 'strcasecmp');
		foreach ( $cards as $title=>$card ) {
			echo '<img src="'.$cardsurl.''.$card.'.'.$format.'" alt="" title="'.$title.'" /> ';
		}
	}
}

?>