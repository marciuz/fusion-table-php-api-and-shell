<?php

/**
 * Class to use Google Fusion Tables with PHP
 * @author M.Marcello Verona <marcelloverona@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL v.3
 */
class FTv1 {
    
    public $token='';
    
    /**
     * Get the api key from https://code.google.com/apis/console/
     * @var string
     */
    public $key='';
    
    /**
     * Base url of FT API
     * @var url 
     */
    protected $ft_base='https://www.googleapis.com/fusiontables/v1/';
    
    public $raw_out='';
    
    public $error;
    
    public $last_sql='';
    
    protected $exec_time=0;
    
    protected $sql_num_rows=0;
    
    protected $sql_affected_rows=0;
    
    protected $path_keys="/tmp";
    
    public $debug;
    
    /**
     * Construct function. You can add directly your authentication data 
     * @param string $user Your google email
     * @param string $pass Your google password
     * @param type $api_key The api key from Google Api Console 
     * @see https://code.google.com/apis/console/ 
     */
    public function __construct($user='', $pass='', $api_key=''){
	
	$this->debug=new stdClass();
	$this->debug->list=array();
	
	if($user!='' && $pass!=''){
	    $this->auth($user,$pass);
	}
	
	if($api_key!=''){
	    $this->key=$api_key;
	}
    }
    
    private function __json_decode($json){
	
	$json=str_replace("NaN,\n", "null,\n", $json);
	return json_decode($json);
    }
    
    public function set_pathkey($path){
	
	if(is_dir($path) && is_writable($path)){
	    
	    $this->path_keys=$path;
	}
	else{
	    die($path . ' not exists or is not writable from server');
	}
    }
    
    /**
     * Function for authentication to API
     * 
     * @param string $user Google username
     * @param string $pass Google password
     * @param bool $force_renew Force the token renew. If false try to use a file token
     */
    public function auth($user,$pass, $force_renew=false){
	
	$this->debug->auth=array();

	$hash=md5($user.$pass);
	$path_key=$this->path_keys."/$hash.key";

	if(file_exists($path_key) && !$force_renew){
	    $token=file_get_contents($path_key);
	    $this->token=$token;
	    $this->debug->auth[]='Token setted from file';
	}
	else{

	    $auth_uri = 'https://www.google.com/accounts/ClientLogin';
	    $authreq_data = array(
		'Email'=>$user,
		'Passwd'=>$pass,
		'service'=> 'fusiontables',
		'accountType'=> 'HOSTED_OR_GOOGLE');
	
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $auth_uri);
	    curl_setopt($ch, CURLOPT_HEADER, false);

	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $authreq_data);

	    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	    $out = curl_exec($ch);
	    curl_close($ch);

	    $tk0=explode("\n",$out);

	    $res=array();

	    for($i=0;$i<count($tk0);$i++){

		if(strpos($tk0[$i],'=')!==false){

		    list($k,$val)=explode("=",$tk0[$i]);
		    $res[$k]=$val;
		}
	    }

	    if(isset($res['Auth'])){
		
		if($fp=fopen($path_key,"w")){
		    if(is_writable($path_key)){
			fwrite($fp,$res['Auth']);
			fclose($fp);
			$this->token=$res['Auth'];
			$this->debug->auth[]='token: registrato su '.$path_key;
		    }
		    else{
			fclose($fp);
			$this->debug->auth[]='token: file '.$path_key." is NOT writable";
			$this->token=false;
		    }
		}
		else{
		    $this->debug->auth[]='token: errore in registrazione su '.$path_key;
		    $this->token=false;
		}
	    }
	    else{
		$this->token=false;
		$this->debug->auth[]='Var Auth not exists: '.print_r($res,true);
	    }
	    
	    return $out;
	}
    }
    
    /**
     * REST GET
     * @param string $q query string
     * @return object
     */
    private function get($q){
	
	$T0=microtime(true);
	
	// create a new cURL resource
	$ch = curl_init();

	$sep=(strpos($q,'?')===false) ? "?":"&";
	
	curl_setopt($ch, CURLOPT_URL, $this->ft_base . $q . $sep . "key=".$this->key);
	curl_setopt($ch, CURLOPT_HEADER, false);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Authorization: GoogleLogin auth='.$this->token,
	    'Content-Type: application/json'
	    ));
	
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	$this->raw_out = curl_exec($ch);
	curl_close($ch);
	
	$out_json=$this->__json_decode($this->raw_out);
	
	if(isset($out_json->error)){
	    
	    $this->error[]=$out_json->error;
	}
	
	$this->exec_time= microtime(true) - $T0;
	
	return $out_json;
    }
    
    /**
     * Send a REST POST command
     * @param string $q url string
     * @param string $message_body the raw post message body
     * @param string $type (default JSON)
     * @param bool $debug
     * @return object
     */
    private function post($q, $message_body, $type='json', $debug=false){
	
	$T0=  microtime(true);
	
	// create a new cURL resource
	$ch = curl_init();

	$sep=(strpos($q,'?')===false) ? "?":"&";
	
	curl_setopt($ch, CURLOPT_URL, $this->ft_base . $q . $sep . "key=".$this->key);
	curl_setopt($ch, CURLOPT_HEADER, false);
	
	$headers[]='Authorization: GoogleLogin auth='.$this->token;
	if($type=='json'){
	    $headers[]='Content-Type: application/json';
	}
	
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	curl_setopt($ch, CURLOPT_POST, 1);
	
	if(!empty($message_body)){
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $message_body);
	}
	
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	
	$this->raw_out = curl_exec($ch);
	
	if($debug){
	    print_r(curl_getinfo($ch));  // get error info
	    echo "\n\ncURL error number:" .curl_errno($ch); // print error info
	    echo "\n\ncURL error:" . print_r(curl_error($ch), true); 
	}
	
	curl_close($ch);
	
	$out_json=$this->__json_decode($this->raw_out);
	
	if(isset($out_json->error)){
	    
	    $this->error[]=$out_json->error;
	}
	
	$this->exec_time= microtime(true) - $T0;
	
	return $out_json;
    }
    
    /**
     * Send a REST PUT 
     * @param string $q the query string
     * @param string $message_body a message body in JSON
     * @param string $type JSON (default)
     * @param bool $debug 
     * @return object
     */
    private function put($q, $message_body, $type='json', $debug=false){
	
	$T0=  microtime(true);
	
	$sep=(strpos($q,'?')===false) ? "?":"&";
	
	$headers[]='Authorization: GoogleLogin auth='.$this->token;
	
	if($type=='json'){
	    $headers[]='Content-Type: application/json';
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $this->ft_base . $q . $sep . "key=".$this->key);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_HEADER, 0); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message_body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	
        $this->raw_out = curl_exec($ch);
	
	// Debug
	$this->debug->list[]=curl_getinfo($ch);  // get error info
	$this->debug->list[]="cURL error number:" .curl_errno($ch); // print error info
	$this->debug->list[]="cURL error:" . print_r(curl_error($ch), true); 
	
	$out_json=$this->__json_decode($this->raw_out);
	
	if(isset($out_json->error)){
	    $this->error[]=$out_json->error;
	}
	
	$this->exec_time= microtime(true) - $T0;
	
	return $out_json;
    }
    
    
    /**
     * SEND a REST DELETE
     * @param string $q the query string
     * @return object
     */
    private function delete($q){
	
	$T0=  microtime(true);
	
	$sep=(strpos($q,'?')===false) ? "?":"&";
	
	$headers[]='Authorization: GoogleLogin auth='.$this->token;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $this->ft_base . $q . $sep . "key=".$this->key);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $this->raw_out = curl_exec($ch);
	
	// Debug
	$this->debug->list[]=curl_getinfo($ch);  // get error info
	$this->debug->list[]="cURL error number:" .curl_errno($ch); // print error info
	$this->debug->list[]="cURL error:" . print_r(curl_error($ch), true); 
	
	
	$out_json=$this->__json_decode($this->raw_out);
	
	if(isset($out_json->error)){
	    $this->error[]=$out_json->error;
	}
	
	$this->exec_time= microtime(true) - $T0;
	
	return $out_json;
    }
    
    
    /**
     * List the tables 
     * @param int $limit
     * @return object from JSON
     */
    public function list_tables($limit=25){
	
	return $this->get("tables?maxResults=".intval($limit));
    }
    
    /**
     * Get the table schema
     * @param string $encid the ID of table
     * @return object
     */
    public function get_table($encid){
	
	return $this->get("tables/$encid");
    }
    
    /**
     * Delete a table
     * @param string $encid the ID of table
     * @return object 
     */
    public function delete_table($encid){
	
	return $this->delete("tables/$encid");
    }
    
    /**
     * Send a SQL query, with some special wrapper in parsing
     * @param type $sql
     * @return type
     */
    protected function __query($sql){
	
	
	if(preg_match('|^SELECT|i',$sql) 
	|| preg_match('/ *SHOW +TABLES */si', $sql) 
	|| preg_match("/ *DESCRIBE +([A-z0-9_-]+)/si",$sql)){
	    
	    $sql=  urlencode($sql);
	    $json= $this->get("query?sql=".$sql);
	    
	    $this->sql_num_rows= (isset($json->rows)) ? count($json->rows) : -1;
	}
	else{
	    
	    $json= $this->post("query", 'sql='.$sql , null);
	}
	
	return $json;
    }
    
    
    
    /**
     * Create a new table
     * @param string $json_definition (json version of FT_Table)
     * @return object
     */
    public function create_table($json_definition){
	
	return $this->post('tables', $json_definition);
    }
    
    /**
     * Copy a table
     * @param string $encid
     * @return object
     */
    public function copy_table($encid, $name=''){
	
	$table=$this->get_table($encid);
	
	// it is a view?
	if(isset($table->sql)){
	    
	    if($name==''){
		trigger_error('You should have a name to copy a view!',E_USER_WARNING);
		return null;
	    }
	    else{
		
		$sql="CREATE VIEW '".str_replace("'","\\'",$name)."' AS (".$table->sql.")";
		$res=$this->query($sql);
		
		if(isset($res->rows[0][0])){
		    return $res->rows[0][0];
		}
		return $res;
	    }
	}
	
	// copy a base table
	else{
	    
	    $res1=$this->post("tables/$encid/copy",'');
	    
	    if($name==''){
		
		if(isset($res->tableId)){
		    
		    return $res->tableId;
		}
		else{ 
		    return $res1;
		}
	    }
	    else{
		
		$table->name=$name;
		
		$res2=$this->put("tables/$encid",json_encode($table));
		
		if(isset($res2->tableId)){
		    
		    return $res2->tableId;
		}
		else{ 
		    return $res2;
		}
	    }
	}
    }
    
    public function clone_table($encid){
	
	// Copy the table
	$new_encid=$this->copy_table($encid);
	
	if(is_string($new_encid)){
	    // copy the styles
	    $styles=get_styles($encid);

	    foreach($styles->items as $style){

		unset($style->tableId);
		$this->add_style($new_encid, json_encode($style));
	    }

	    // copy the templates
	    $templates=get_templates($encid);

	    foreach($templates->items as $template){

		unset($template->tableId);
		$this->add_template($new_encid, json_encode($template));
	    }
	}
	
	return $new_encid;
    }
    
    public function add_style($encid, $obj_style=''){
	
        return $this->post("tables/$encid/styles", $obj_style);
    }
    
    public function update_style($encid, $obj_style='', $styleId=null){
	
	return $this->put("tables/$encid/styles/$styleId", $obj_style);
    }
    
    /**
     * List the styles of table
     * @param string $encid
     * @param int $max_results
     * @return object
     */
    public function get_styles($encid, $max_results=25){
	
	return $this->get("tables/$encid/styles?maxResults=".$max_results);
    }
    
    public function get_style($encid, $styleId=1){
	
	return $this->get("tables/$encid/styles/$styleId");
    }
    
    public function add_template($encid, $obj_template='',$template_id=null){
	
	// create
	if($template_id==null)
	    return $this->post("tables/$encid/templates", $obj_template);
	
	// update existence
	else
	    return $this->update_template($encid, $obj_template,$template_id);
    }
    
    public function update_template($encid, $obj_template,$template_id){
	
	$template_id=intval($template_id);
	return $this->put("tables/$encid/templates/$template_id", $obj_template);
    }
    
    public function delete_template($encid, $template_id){
	
	return $this->delete("tables/$encid/templates/$template_id");
	
    }
    
    public function delete_all_template($encid){
	
	$templates=$this->get_templates($encid,100);
	
	foreach($templates->items as $template){
	    $this->delete("tables/$encid/templates/$template->templateId");
	}
	
    }
    
    
    public function get_templates($encid, $max_results=25){
	
	return $this->get("tables/$encid/templates?maxResults=".$max_results);
    }
    
    public function get_template($encid, $template_id=1){
	
	return $this->get("tables/$encid/templates/$template_id");
    }
    
    
    public function list_columns($encid){
	
	return $this->get("tables/$encid/columns");
    }
    
    /**
     * Find the encid from table name
     * 
     * @param string $name
     * @param bool $once Find only the first one
     * @return string
     */
    public function find_table($name, $once=true){
	
	if($once)
	    $res=-1;
	else
	    $res=array();
	
	$this->__query("SHOW TABLES");
	
	$obj=$this->__json_decode($this->raw_out);
	
	foreach($obj->rows as $row){
	    
	    if($row[1]==$name){
		
		if($once) return $row[0];
		else $res[]=$row[0];
	    }
	}
	
	return $res;
    }
    
    public function get_columnId_from_columnName($encid,$name=''){
	
	$cols=$this->list_columns($encid);
	
	if(isset($cols->items)){
	    foreach($cols->items as $oo){
		
		if($oo->name==$name){
		    return $oo->columnId;
		}
	    }
	}
	
	return null;
    }
    
    /**
     * Add a column to a table
     * @param string $encid
     * @param string $obj_column json column (like a FT_Column)
     * @return object
     */
    public function add_column($encid, $obj_column){
	
	return $this->post("tables/$encid/columns", $obj_column);
    }
    
    public function update_column($encid, $obj_column, $columnId){
	
	return $this->put("tables/$encid/columns/$columnId", $obj_column);
    }
    
    public function delete_column($encid, $columnId){
	
	return $this->delete("tables/$encid/columns/$columnId");
    }
    
    /**
     * delete a single style, identified by $styleId
     * @param string $encid
     * @param int $styleId
     * @return object
     */
    public function delete_style($encid, $styleId){
	
	return $this->delete("tables/$encid/styles/$styleId");
    }
    
    /**
     * Delete all styles from a table
     * @param string $encid
     */
    public function delete_all_style($encid){
	
	$styles=$this->get_styles($encid, 100);
	
	foreach($styles->items as $style){
	    $this->delete("tables/$encid/styles/$style->styleId");
	}
    }
    
    
    
    
    /**
     * Query wrapper
     * if SELECT or INSERT return the normal FT results
     * else if UPDATE or DELETE create a 2 step procedure
     * 1: retrive the ROWID with regexp
     * 2: exec in loop the UPDATE or DELETE statement
     * 
     * @param string $sql
     * @return mixed
     */
    public function query($sql){
	
	$sql=trim(preg_replace("/(\n|\r)+/"," ",$sql));
	
	// reset affected rows
	$this->sql_affected_rows=0;
	
	// CASE SELECT: EXEC THE NORMAL QUERY
	if(preg_match("|^ *SELECT |i",$sql)){
	    
	    if(preg_match("/%([^%]+)%/", $sql, $ff)){
		
		$encid=$this->find_table($ff[1], true);
		
		if(is_string($encid))
		    $sql=str_replace("%".$ff[1]."%",$encid,$sql);
		
		else if(is_array($encid))
		    $sql=str_replace("%".$ff[1]."%",$encid[0],$sql);
	    }
	    
	    return $this->__query($sql);
	}
	
	// CASE INSERT: EXEC THE NORMAL QUERY
	else if(preg_match("|^ *INSERT |i",$sql)){
	    
	    $result=$this->__query($sql);
	    $this->sql_affected_rows= isset($result->rows) ? count($result->rows) : -1;
	    return $result;
	}
	
	// CASE UPDATE: 2 STEP QUERY
	// Select the rowid for each record and update by ROWID
	else if(preg_match("/^ *UPDATE/i",$sql)){
	    
	    if(preg_match("/^ *UPDATE *([%'A-z0-9_-]+) *SET *(.*?)( *\;*$|( +WHERE *(.*);*))/si" ,$sql, $tokens)){
		
		$TABLE=$tokens[1];
		$SET=$tokens[2];
		
		$rowid_in_where=false;
		
		if(isset($tokens[5])){
		    
		    if(preg_match("/ROWID=(.*)/",$tokens[5])){
			
			$rowid_in_where=true;
		    }
		    
		    $WHERE="WHERE ".$tokens[5];
		    
		}
		else{
		    $WHERE="";
		}

		// skip the double step!
		if($rowid_in_where){
		    
		    $sql_up="UPDATE $TABLE SET $SET $WHERE";
		    $res2=$this->__query($sql_up);
		    if(!isset($res2->error)){
			$this->sql_affected_rows++;
		    }
		}
		// two step query: find the rowid and apply the updates
		else{
		
		    $sql_new="SELECT ROWID FROM ".$TABLE." ".$WHERE;
		    $res1=$this->__query($sql_new);

		    if($res1!==null){

			if(isset($res1->error)){

			}
			else{

			    for($i=0;$i<count($res1->rows);$i++){

				$sql_up="UPDATE $TABLE SET $SET WHERE ROWID='".$res1->rows[$i][0]."'";
				$res2=$this->__query($sql_up);
				if(!isset($res2->error)){
				    $this->sql_affected_rows++;
				}
			    }
			}
		    }
		}
	    }
	}
	
	// CASE DELETE: 2 STEP QUERY
	// Select the rowid for each record and update by ROWID
	else if(preg_match("/^ *DELETE/i",$sql)){
	    
	    if(preg_match("/^ *DELETE +FROM +([A-z0-9_-]+)( *$|( +WHERE +(.+)))/si" ,$sql, $tokens)){
		
		$TABLE=$tokens[1];
		
		if(isset($tokens[4])){
		    
		    $WHERE=" WHERE ".$tokens[4];
		    
		    $sql_new="SELECT ROWID FROM ".$TABLE." ".$WHERE;
		
		    $res1=$this->__query($sql_new);

		    if($res1!==null){

			if(isset($res1->error)){

			    $this->error=$res1->error->message;
			}
			else{

			    for($i=0;$i<count($res1->rows);$i++){

				// echo $res1->rows[$i][0]."\n";

				$sql_up="DELETE FROM $TABLE WHERE ROWID='".$res1->rows[$i][0]."'";

				$res2=$this->__query($sql_up);

				if(!isset($res2->error)){

				    $this->sql_affected_rows++;
				}
			    }
			}
		    }
		}
		else{
		    
		    $sql_up="DELETE FROM $TABLE";
		    
		    $res2=$this->__query($sql_up);

		    if(!isset($res2->error)){

			$this->sql_affected_rows++;
		    }
		}
	    }
	}
	
	// SPECIAL WRAPPER: DROP
	else if(preg_match("/ *DROP +TABLE +(['A-z0-9_-]+)/si", $sql,$tokens)){
	    
	    $TABLE=$tokens[1];
	    
	    $this->raw_out= $this->delete_table($TABLE);
	}
    
	// SPECIAL WRAPPER: COPY
	else if(preg_match("/ *COPY +(['A-z0-9_-]+)/si", $sql,$tokens)){
	    
	    $TABLE=$tokens[1];
	    
	    $this->raw_out= $this->copy_table($TABLE);
	}
	
	// SPECIAL WRAPPER: CREATE
	else if(preg_match("/ *CREATE +TABLE +'?(.*?)'? +\( *([A-z0-9_ ,-]+) *\)/ui", $sql,$tokens)){

	    $table_name=$tokens[1];
	    $tkk=explode(",",$tokens[2]);

	    $Table = new FT_Table();
	    $Table->name=$table_name;

	    if(count($tkk)>0){

		foreach($tkk as $k=>$val){
		    $val=trim($val);

		    list($K,$T)=preg_split("/ +/",$val);
		    $Table->addColumn($K, $T);
		}

		$this->raw_out= $this->create_table($Table->getJson());

	    }
	    else 
		$this->raw_out= -1;
	}
	
	// SPECIAL WRAPPER: FIND
	else if(preg_match("/ *FIND +(TABLE)? *'?(.+)'?/ui", $sql,$ff)){
	    
	    if(isset($ff[2])){
	
		return $this->raw_out=$this->find_table(trim($ff[2]), false);
	    }
	    else{
		return $this->raw_out=-1;
	    }
	}
	
	// DEFAULT WRAPPER: SHOW TABLES, DESCRIBE, ETC...
	else{
	    
	   return $this->__query($sql);
	}
	
    }
    
    /**
     * Return a matrix of all results with numeric key, 
     * like mysql_fetch_row (but all results)
     * If Errors occurs, return -1
     * 
     * @return mixed
     */
    public function fetch_row_all(){
	
	if($this->raw_out!==null){
	    
	    $json=  $this->__json_decode($this->raw_out);
	    
	    return $json->rows;
	}
	else{
	    return -1;
	}
    }
    
    /**
     * Return a matrix of all results with assoc key, 
     * like mysql_fetch_assoc (but all results)
     * If Errors occurs, return -1
     * 
     * @return mixed 
     */
    public function fetch_assoc_all(){
	
	if($this->raw_out!==null){
	    
	    $json=  $this->__json_decode($this->raw_out);
	    
	    $mat=array();
	    
	    if(isset($json->rows)){
	    
		for($i=0;$i<count($json->rows);$i++){

		    for($j=0;$j<count($json->rows[$i]);$j++){

			$mat[$i][ $json->columns[$j] ]= $json->rows[$i][$j];
		    }
		}
	    }
	    
	    return $mat;
	}
	else{
	    return -1;
	}
    }
    
    /**
     * Get the num of rows from the last SELECT query
     * 
     * @return int 
     */
    public function num_rows(){
	
	return $this->sql_num_rows;
    }
    
    /**
     * Get the num of affected rows from the last UPDATE/DELETE query
     * 
     * @todo set the affected rows from INSERT
     * @return int 
     */
    public function affected_rows(){
	
	return $this->sql_affected_rows;
    }
    
    
    /**
     * Return the FT post/get execution time
     * 
     * @return float 
     */
    public function get_exec_time(){
	
	return round($this->exec_time, 4);
    }
    
    
    
}





/**
 * Initialize a Column.
 * You can encode this in json to send a valid FT column object
 * @see FT_Table
 */
class FT_Column {
    
    public $name;
    public $type;
    
    public function __construct($name='',$type=''){
	
	$this->name=$name;
	
	$type=strtoupper(trim($type));
	
	if(in_array($type, array("STRING","DATETIME","NUMBER","LOCATION"))){
	    $this->type=$type;
	}
	else{
	    $this->type="STRING";
	}
    }
}





/**
 * Initialize a Table.
 * You can encode this in json to send a valid FT table object
 */
class FT_Table {
    
    protected $kind='fusiontables#table';
    public $name='';
    public $description='';
    public $isExportable= true;
    public $columns=array();
    public $attribution='';
    public $attribution_link='';
    
    private $error=array();
    
    public function addColumn($name, $type='STRING'){
	
	$allowed_type=array(
	    'STRING',
	    'NUMBER',
	    'DATETIME',
	    'LOCATION',
	);

	$type= strtoupper($type);
	
	if(trim($name)!=''){
	
	    $Column = new FT_Column;

	    $Column->name=$name;
	    $Column->type = (in_array($type, $allowed_type)) ? $type : 'STRING';

	    $this->columns[]=$Column;

	}
	
    }
    
    
    public function getJson(){
	
	if($this->name==''){
	    $this->error[]="The table should have a name!";
	}
	
	if(count($this->columns)==0){
	    
	    $this->error[]="The table should have one column at least!";
	}
	
	return json_encode($this);
    }
}

/**
 * Initialize a style.
 * You can encode this in json to send a valid FT style object
 */
class FT_Styler {
    
    private $kind='fusiontables#styleSetting';
    
    //public $tableId='';
    //public $styleId='';
    
    public $name='My Style';
    
    public $isDefaultForTable=true;
    
    public $markerOptions;
    
    public $column_name='icona';
    
    public function getJson(){
	
	$this->markerOptions=new stdClass();
	
	$this->markerOptions->iconStyler->columnName= $this->column_name;
	
	return json_encode($this);
    }

}

/**
 * Initialize a template.
 * You can encode this in json to send a valid FT template object
 */
class FT_Template {
    
    private $kind='fusiontables#template';
    
    public $isDefaultForTable=true;
    public $body='';
    public $name='template-amministrative';
    public $automaticColumnNames= array();
    
    public function getJson(){
	
	return json_encode($this);
    }

}

