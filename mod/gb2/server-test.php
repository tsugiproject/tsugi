<?php

$page = $_GET['page']; // get the requested page
$limit = $_GET['rows']; // get how many rows we want to have into the grid
$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
$sord = $_GET['sord']; // get the direction
if(!$sidx) $sidx =1;

$count = 4200;
if( $count >0 ) {
    $total_pages = ceil($count/$limit);
} else {
    $total_pages = 0;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1)

$responce = new stdClass();
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$responce->start = $start;
$responce->limit = $limit;
$i=0;
for ( $i=0; $i < $limit; $i++ ) {
    $id = $i + $start;
    $txt = "Yo ".$id;
    $responce->rows[$i]['id']=$id;
    $responce->rows[$i]['cell']=array($id,$txt,$txt,$txt,$txt,$txt,$txt);
}        
echo json_encode($responce);
