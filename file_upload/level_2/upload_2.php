<?php
if( isset( $_POST[ 'Upload' ] ) ) {
	$target_path  = "uploads/";
	$target_path .= basename( $_FILES[ 'uploaded' ][ 'name' ] );
	//识别文件类型
	$uploaded_name = $_FILES[ 'uploaded' ][ 'name' ];
	$uploaded_type = $_FILES[ 'uploaded' ][ 'type' ];
	$uploaded_size = $_FILES[ 'uploaded' ][ 'size' ];
	if( ( $uploaded_type == "image/jpeg" || $uploaded_type == "image/png" ) &&
		( $uploaded_size < 100000 ) ) {
		if( !move_uploaded_file( $_FILES[ 'uploaded' ][ 'tmp_name' ], $target_path ) ) {
			echo "<pre>图片上传失败</pre>";
		}
		else {
			echo "<pre>{$target_path} 图片上传成功！</pre>";
		}
	}
	else {
		echo "<pre>只允许上传jpg或者png格式的图片文件,且文件大小不能超过100k</pre>";
	}
}
?>
