<?php

session_start();

if(isset($_SESSION['history'])){
    
    echo json_encode(array_values($_SESSION['history']));
}