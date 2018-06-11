<?php
if (isset($_POST['Upload'])) {
    $target_path = "uploads/";
    $target_path = $target_path . basename( $_FILES['uploaded']['name']);
    if(!move_uploaded_file($_FILES['uploaded']['tmp_name'], $target_path)) {
        echo '<pre>';
        echo '您的图片上传失败.';
        echo '</pre>';
    } else {
        echo '<pre>';
        echo $target_path . '文件已经成功上传！';
        echo '</pre>';
    }
}
?>