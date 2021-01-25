<?php

  //get the q parameter from URL
  $query=$_GET["q"];
  $newquery = str_replace(" ", "+", $query);
  $myurl="http://localhost:8983/solr/myexample/suggest?q=" . $newquery;

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

  $urlresults = file_get_contents($myurl);
  $results = json_decode($urlresults);
 }
 catch (Exception $e)
 {
 // in production you'd probably log or email this error to an admin
 // and then show a special message to the user but for this example
 // we're going to show the full exception
 die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
 }

 $hint="";
 foreach ($results->suggest->suggest->$query->suggestions as $doc) {
  //if ($hint!="") {
  //      $hint=$hint . "<br />";
  //    }
  foreach ($doc as $field => $value) {
    if ($field == "term") {
      
      $hint=$hint . "<a href='http://localhost:8001/?q=" . $value . "'/a>";
      echo $hint;
      echo $value;
      echo "<br />";
    }
  }
  //echo $doc;
 }




// Set output to "no suggestion" if no hint was found
// or to the correct values
if ($hint=="") {
  $response="no suggestion";
} else {
  $response=$hint;
}

//output the response
echo $response;
?>