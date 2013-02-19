<?php

require_once '../class.ft_v1.php';

// -- Configuration

// your google email
$google_email='';

// your google password
$google_password='';

// your server api key, from https://code.google.com/apis/console/
$google_apikey='';



// --- end basic configuration -----



// Create a instance
$FT = new FTv1();

// set the directory for the auth key. 
// this should be NOT PUBLIC and writable from the server 
$FT->set_pathkey('/tmp');

// Authentication
$FT->auth($google_email, $google_password);

// print the debug
print_r($FT->debug->auth);

// set app key from https://code.google.com/apis/console/
$FT->key=$google_apikey;

// OR in alternative , use the short method
// $FT = new FTv1($google_email, $google_password, $google_apikey);



// Show your tables

print "<pre>\n";

print "\n\n--- SHOW TABLES---\n\n";
$sql="SHOW TABLES";
$res=$FT->query($sql);
print_r($res);


// Create a table ----------------------

print "\n\n--- CREATE TABLE via API---\n\n";

// Prepare the table
$T = new FT_Table();
$T->name='mytest_01';
$T->description='This is a test from FT php library';
$T->addColumn('id_test', 'NUMBER');
$T->addColumn('str_test', 'STRING');
$T->addColumn('date_test', 'DATETIME');
$T->addColumn('geom', 'LOCATION');
$T->addColumn('income_per_year', 'NUMBER');
$json=$T->getJson();
$newtable=$FT->create_table($json);

print_r($newtable);

$encid=$newtable->tableId;


// Or create a table with a SQL statement


// INSERT some data in the table
print "\n\n--- or CREATE TABLE via SQL statement---\n\n";
$sql="CREATE TABLE mytest_02 (id_test NUMBER, str_test STRING, date_test DATETIME, geom LOCATION)";
$res=$FT->query($sql);
print_r($res);




// INSERT some data in the table
print "\n\n--- INSERT TEST ---\n\n";

$data=array();
$data[]=array(1,'John','2003-12-01','39.15425234,7.145346', 50000);
$data[]=array(2,'Mario','2012-08-05','41.563457,12.7844456', 34000);
$data[]=array(3,'Linda','2007-11-23','40.76673,11.6735673', 90000);
$data[]=array(4,'Paula','1999-02-21','42.4567347,10.574675', 20000);
$data[]=array(5,'Pino','1995-06-20','42.5465326,12.8754768', 23000);


$sql='';

for($i=0;$i<count($data);$i++){
    $sql.="INSERT INTO $encid (id_test, str_test, date_test, geom, income_per_year) 
	VALUES ('".implode("','",$data[$i])."'); ";
}

// Print the SQL
print $sql."\n";

// Exec the query
$q = $FT->query($sql);

var_dump($q);

// print the affected rows
print "Affected rows: ".$FT->affected_rows()."\n";

// print the execution time
print "Execution time: ".$FT->get_exec_time()."\n";



print "\n\n--- SELECT (example 1) ---\n\n";

// SELECT SOME DATA WITH SELECT QUERY
$sql="SELECT * FROM $encid WHERE date_test > '2000-01-01'";
$q=$FT->query($sql);


print $sql."\n\n";
// Num rows
print "Num Rows: ".$FT->num_rows()."\n";

// print the results with associative array
$mat=$FT->fetch_assoc_all();
print_r($mat);


print "\n\n--- SELECT (example 2) ---\n\n";

// SELECT SOME DATA WITH SELECT QUERY
$sql="SELECT * FROM $encid WHERE str_test LIKE 'P%' ";
$q=$FT->query($sql);


print $sql."\n\n";
// Num rows
print "Num Rows: ".$FT->num_rows()."\n";

// print the results with associative array
$mat=$FT->fetch_assoc_all();
print_r($mat);



// UPDATE TEST
print "\n\n--- UPDATE ---\n\n";
$sql="UPDATE $encid SET date_test='".date('Y-m-d')."' WHERE id_test>'2' ";
$q=$FT->query($sql);

// Num rows
print "Affected Rows: ".$FT->affected_rows()."\n";


// SELECT SOME DATA WITH SELECT QUERY
$sql="SELECT * FROM $encid ";
$q=$FT->query($sql);
$mat=$FT->fetch_assoc_all();
print "Records:\n";
print_r($mat);

print "\n\n--- SELECT COUNT(*) ---\n\n";

// SELECT SOME DATA WITH SELECT QUERY
$sql="SELECT COUNT(id_test) as n_records FROM $encid ";
$q=$FT->query($sql);
$mat=$FT->fetch_assoc_all();
print "Records:\n";
print_r($mat);

// DELETE A RECORD
print "\n\n--- DELETE RECORD ---\n\n";

$sql="DELETE FROM $encid WHERE id_test='5'";
$FT->query($sql);

print "\n\n--- SELECT COUNT(id_test) after DELETE ---\n\n";

// SELECT SOME DATA WITH SELECT QUERY
$sql="SELECT COUNT(id_test) as n_records FROM $encid ";
$q=$FT->query($sql);
var_dump($q);
$mat=$FT->fetch_assoc_all();
print "Records:\n";
print_r($mat);





// ------------------ WORKING WITH COLUMNS -------------------

// Show the columns
$res=$FT->list_columns($encid);
print "\n\n--- Show columns ---\n";
print_r($res);

// Create a new column
$mytype='NUMBER';
$myname='my_new_column';
$Column = new FT_Column($myname, $mytype);

$res=$FT->add_column($encid, json_encode($Column));

print "\n\n--- Add a new column ".$myname." (".$mytype.") ---\n";
print_r($res);

// Show the columns
$res=$FT->list_columns($encid);
print "\n\n--- Show columns after insert---\n";
print_r($res);



// retrive the columnID from column name
$columnID =$FT->get_columnId_from_columnName($encid, $myname);

print "Retrive the colum id with the function get_columnId_from_columnName() : ".$columnID."\n";

if(intval($columnID)>0){
    
    // Alter the column
    $mytype2='STRING';
    $myname2='a_new_name';
    $Column2 = new FT_Column($myname2, $mytype2);
    $res=$FT->update_column($encid, json_encode($Column2), $columnID);
    print_r($res);
}
else{
    
    echo "\n\nImpossibile to know the columnId\n";
}

// Show the columns
$res=$FT->list_columns($encid);
print "\n\n--- Show columns after update---\n";
print_r($res);


// delete the new column
$res=$FT->delete_column($encid,$columnID);
print "\n\n--- Delete a column ---\n";
print_r($res);

// Show the columns
$res=$FT->list_columns($encid);
print "\n\n--- Show columns after delete---\n";
print_r($res);


// @todo 
// ------------------ INTERACTIONS WITH TEMPLATES AND STYLES -------------------




// ------------------ INTERACTIONS WITH TEMPLATES AND STYLES -------------------

// Delete the table
print "\n\n--- DROP TABLE ---\n\n";
$result=$FT->delete_table($encid);
print_r($result);


// print debug
print "\n\n--- GENERAL DEBUG ---\n\n";
print_r($FT->debug);

