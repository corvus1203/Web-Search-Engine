<?php ini_set ('memory_limit', -1)?>
<?php

class SpellCorrector {
  private static $NWORDS;
  
  /**
   * Reads a text and extracts the list of words
   *
   * @param string $text
   * @return array The list of words
   */
  private static function  words($text) {
    $matches = array();
    preg_match_all("/[a-z]+/",strtolower($text),$matches);
    return $matches[0];
  }
  
  /**
   * Creates a table (dictionary) where the word is the key and the value is it's relevance 
   * in the text (the number of times it appear)
   *
   * @param array $features
   * @return array
   */
  private static function train(array $features) {
    $model = array();
    $count = count($features);
    for($i = 0; $i<$count; $i++) {
      $f = $features[$i];
      $model[$f] +=1;
    }
    return $model;
  }
  
  /**
   * Generates a list of possible "disturbances" on the passed string
   *
   * @param string $word
   * @return array
   */
  private static function edits1($word) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    $alphabet = str_split($alphabet);
    $n = strlen($word);
    $edits = array();
    for($i = 0 ; $i<$n;$i++) {
      $edits[] = substr($word,0,$i).substr($word,$i+1);     //deleting one char
      foreach($alphabet as $c) {
        $edits[] = substr($word,0,$i) . $c . substr($word,$i+1); //substituting one char
      }
    }
    for($i = 0; $i < $n-1; $i++) {
      $edits[] = substr($word,0,$i).$word[$i+1].$word[$i].substr($word,$i+2); //swapping chars order
    }
    for($i=0; $i < $n+1; $i++) {
      foreach($alphabet as $c) {
        $edits[] = substr($word,0,$i).$c.substr($word,$i); //inserting one char
      }
    }

    return $edits;
  }
  
  /**
   * Generate possible "disturbances" in a second level that exist on the dictionary
   *
   * @param string $word
   * @return array
   */
  private static function known_edits2($word) {
    $known = array();
    foreach(self::edits1($word) as $e1) {
      foreach(self::edits1($e1) as $e2) {
        if(array_key_exists($e2,self::$NWORDS)) $known[] = $e2;       
      }
    }
    return $known;
  }
  
  /**
   * Given a list of words, returns the subset that is present on the dictionary
   *
   * @param array $words
   * @return array
   */
  private static function known(array $words) {
    $known = array();
    foreach($words as $w) {
      if(array_key_exists($w,self::$NWORDS)) {
        $known[] = $w;

      }
    }
    return $known;
  }
  
  
  /**
   * Returns the word that is present on the dictionary that is the most similar (and the most relevant) to the
   * word passed as parameter, 
   *
   * @param string $word
   * @return string
   */
  public static function correct($word) {
    $word = trim($word);
    if(empty($word)) return;
    
    $word = strtolower($word);
    
    if(empty(self::$NWORDS)) {
      
      /* To optimize performance, the serialized dictionary can be saved on a file
      instead of parsing every single execution */
      if(!file_exists('serialized_dictionary.txt')) {
        self::$NWORDS = self::train(self::words(file_get_contents("big.txt")));
        $fp = fopen("serialized_dictionary.txt","w+");
        fwrite($fp,serialize(self::$NWORDS));
        fclose($fp);
      } else {
        self::$NWORDS = unserialize(file_get_contents("serialized_dictionary.txt"));
      }
    }
    $candidates = array(); 
    if(self::known(array($word))) {
      return $word;
    } elseif(($tmp_candidates = self::known(self::edits1($word)))) {
      foreach($tmp_candidates as $candidate) {
        $candidates[] = $candidate;
      }
    } elseif(($tmp_candidates = self::known_edits2($word))) {
      foreach($tmp_candidates as $candidate) {
        $candidates[] = $candidate;
      }
    } else {
      return $word;
    }
    $max = 0;
    foreach($candidates as $c) {
      $value = self::$NWORDS[$c];
      if( $value > $max) {
        $max = $value;
        $word = $c;
      }
    }
    return $word;
  }
  
  
}

?>
<?php
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$rankMethod = isset($_REQUEST['rank']) ? $_REQUEST['rank'] : false;
$results = false;
 $additionalParameters = array(
 'sort' => 'pageRankFile desc'
);
if ($query)
{
 // The Apache Solr Client library should be on the include path
 // which is usually most easily accomplished by placing in the
 // same directory as this script ( . or current directory is a default
 // php include path entry in the php.ini)
 require_once('Apache/Solr/Service.php');
 // create a new solr service instance - host, port, and corename
 // path (all defaults in this example)
 $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

 // if magic quotes is enabled then stripslashes will be needed
 if (get_magic_quotes_gpc() == 1)
 {
 $query = stripslashes($query);
 }
 // in production code you'll always want to use a try /catch for any
 // possible exceptions emitted by searching (i.e. connection
 // problems or a query parsing error)
 try
  {
 //$results = $solr->search($query, 0, $limit);
    if ($rankMethod == "luc" or $rankMethod == false) {
      $results = $solr->search($query, 0, $limit);
    }
    else {
      $results = $solr->search($query, 0, 10, $additionalParameters);
    }
 }
 catch (Exception $e)
 {
 // in production you'd probably log or email this error to an admin
 // and then show a special message to the user but for this example
 // we're going to show the full exception
 die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
 }
}
//------------------------

?>
<html>
 <head>
 <title>CSCI572 HW5</title>
 <script>
function showResult(str) {
  if (str.length==0) {
    document.getElementById("livesearch").innerHTML="";
    document.getElementById("livesearch").style.border="0px";
    return;
  }
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {  // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("livesearch").innerHTML=this.responseText;
      document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","livesearch.php?q="+str,true);
  xmlhttp.send();
}
</script>
 </head>
 <body>
 <form accept-charset="utf-8" method="get">
 <label for="q">Search:</label>
 <input id="q" name="q" type="text" onkeyup="showResult(this.value)" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" >
 <div id="livesearch"></div>
 <input type="radio" id="luc" name="rank" value="luc">
 <label for="luc">Lucene</label>
  <input type="radio" id="pr" name="rank" value="pr">
 <label for="pr">PageRank</label>

 <input type="submit"/>
 </form>
<?php
// display results
if ($results)
{
 $total = (int) $results->response->numFound;
 $start = min(1, $total);
 $end = min($limit, $total);
 if ($total == 0) {
  
  /*if ($query == "socer") {
    echo "Results for socer:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=soccer'>soccer</a>";
  }
  if ($query == "billionaere") {
    echo "Results for billionaere:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=billionaire'>billionaire</a>";
  }
  if ($query == "magazne") {
    echo "Results for magazne:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=magazine'>magazine</a>";
  }
  if ($query == "relocat") {
    echo "Results for relocat:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=relocate'>relocate</a>";
  }
  if ($query == "ackowledge") {
    echo "Results for ackowledge:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=acknowledge'>acknowledge</a>";
  }
  if ($query == "economcs") {
    echo "Results for economcs:";
    echo "<br />";
    echo "Did you mean: ";
    echo "<a href='http://localhost:8001/?q=economics'>economics</a>";
  }*/
  echo "Results for " . $query . ":<br />";
  echo "Did you mean: ";
  echo "<a href='http://localhost:8001/?q=" . SpellCorrector::correct($query) . "'>" . SpellCorrector::correct($query) . "</a>";

 }
?>
 <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
 <ol>
<?php
 // iterate result documents
 foreach ($results->response->docs as $doc)
 {
?>
 <li>
 <table style="border: 1px solid black; text-align: left">
<?php
 // iterate document fields / values
$oid = "NA";
$newid = "NA";
$description = "NA";
$title = "NA";
$og_url = "NA";

 foreach ($doc as $field => $value)
 {
  //if ($field == "id" or $field == "og_description" or $field == "title") {
    if ($field == "id") {
      $oid = $value;
      $newid = str_replace("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/NYTIMES/nytimes/", "", $value);
       $myfile = fopen("/Users/yun-tanghsu/Desktop/2020Fall/572/hw/hw4/NYTIMES/URLtoHTML_nytimes_news.csv", "r") or die("Unable to open file!");

      while(($line = fgets($myfile)) !== false) {
          if (strpos($line, $newid)!==false) {
            $myurl = str_replace($newid, "", $line);
            $myurl = str_replace(",", "", $myurl);
          }
      }
      fclose($myfile);
    } else if ($field == "og_description") {
      $description = $value;
    } else if ($field == "title") {
      $title = $value;
    } else if ($field == "og_url") {
      $og_url = $value;
    }
 }
 if($og_url == ""){
  $og_url = $myurl;
 }
 ?>

 <tr>
 <th><?php echo htmlspecialchars("Title", ENT_NOQUOTES, 'utf-8'); ?></th>
 <td><?php echo "<a href='$og_url'>$title</a>"; ?></td>
 </tr>

 <tr>
 <th><?php echo htmlspecialchars("URL", ENT_NOQUOTES, 'utf-8'); ?></th>
 <td><?php echo "<a href='$og_url'>$og_url</a>"; ?></td>
 </tr>

 <tr>
 <th><?php echo htmlspecialchars("ID", ENT_NOQUOTES, 'utf-8'); ?></th>
 <td><?php echo htmlspecialchars($oid, ENT_NOQUOTES, 'utf-8'); ?></td>
 </tr>

 <tr>
 <th><?php echo htmlspecialchars("Description", ENT_NOQUOTES, 'utf-8'); ?></th>
 <td><?php echo htmlspecialchars($description, ENT_NOQUOTES, 'utf-8'); ?></td>
 </tr>


</table>
 </li>
<?php
 }



?>
 </ol>
<?php
}
?>
 </body>
</html>