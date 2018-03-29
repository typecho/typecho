<?php
if(isset($_POST['file'])){
    $file = './uploads/' . $_POST['file'];
    if(file_exists($file)){
        unlink($file);
    }
}
?>
