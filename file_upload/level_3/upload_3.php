<?php
if( isset( $_POST[ 'Upload' ] ) ) {
	$target_path  = "uploads/";
	$target_path .= basename( $_FILES[ 'uploaded' ][ 'name' ] );
	//记录文件信息
	$uploaded_name = $_FILES[ 'uploaded' ][ 'name' ];
	$uploaded_ext  = substr( $uploaded_name, strrpos( $uploaded_name, '.' ) + 1);
	$uploaded_size = $_FILES[ 'uploaded' ][ 'size' ];
	$uploaded_tmp  = $_FILES[ 'uploaded' ][ 'tmp_name' ];
	//识别文件后缀
	if( ( strtolower( $uploaded_ext ) == "jpg" || strtolower( $uploaded_ext ) == "jpeg" || strtolower( $uploaded_ext ) == "png" ) &&( $uploaded_size < 100000 ) &&getimagesize( $uploaded_tmp ))
 {
		if( !move_uploaded_file( $uploaded_tmp, $target_path ) ) {
			echo "<pre>图片上传识别.</pre>";
		}
		else {
			echo "<pre>{$target_path} 图片上传成功!</pre>";
		}
	}
	else {
		echo "<pre>只能上传格式为jpg和png的图片.</pre>";
	}
}
?>
