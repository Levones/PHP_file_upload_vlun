<?php
if( isset( $_POST[ 'Upload' ] ) ) {
	// Check Anti-CSRF token
	checkToken( $_REQUEST[ 'user_token' ], $_SESSION[ 'session_token' ], 'index.php' );
	// File information
	$uploaded_name = $_FILES[ 'uploaded' ][ 'name' ];
	$uploaded_ext  = substr( $uploaded_name, strrpos( $uploaded_name, '.' ) + 1);
	$uploaded_size = $_FILES[ 'uploaded' ][ 'size' ];
	$uploaded_type = $_FILES[ 'uploaded' ][ 'type' ];
	$uploaded_tmp  = $_FILES[ 'uploaded' ][ 'tmp_name' ];
	// Where are we going to be writing to?
	$target_path   = 'uploads/';
	//$target_file   = basename( $uploaded_name, '.' . $uploaded_ext ) . '-';
	$target_file   =  md5( uniqid() . $uploaded_name ) . '.' . $uploaded_ext;
	$temp_file     = ( ( ini_get( 'upload_tmp_dir' ) == '' ) ? ( sys_get_temp_dir() ) : ( ini_get( 'upload_tmp_dir' ) ) );
	$temp_file    .= DIRECTORY_SEPARATOR . md5( uniqid() . $uploaded_name ) . '.' . $uploaded_ext;

	//判断是否是一张图片
	if( ( strtolower( $uploaded_ext ) == 'jpg' || strtolower( $uploaded_ext ) == 'jpeg' || strtolower( $uploaded_ext ) == 'png' ) &&
		( $uploaded_size < 100000 ) &&
		( $uploaded_type == 'image/jpeg' || $uploaded_type == 'image/png' ) &&
		getimagesize( $uploaded_tmp ) ) {
		//重新制作一张图片，抹去任何可能有危害的数据
		if( $uploaded_type == 'image/jpeg' ) {
			$img = imagecreatefromjpeg( $uploaded_tmp );
			imagejpeg( $img, $temp_file, 100);
		}
		else {
			$img = imagecreatefrompng( $uploaded_tmp );
			imagepng( $img, $temp_file, 9);
		}
		imagedestroy( $img );
		//文件转储
		if( rename( $temp_file, ( getcwd() . DIRECTORY_SEPARATOR . $target_path . $target_file ) ) ) {
			$html .= "<pre><a href='${target_path}${target_file}'>${target_file}</a> succesfully uploaded!</pre>";
		}
		else {
			$html .= '<pre>Your image was not uploaded.</pre>';
		}
		//删除所有暂时文件
		if( file_exists( $temp_file ) )
			unlink( $temp_file );
	}
	else {
		//无效文件
		$html .= '<pre>Your image was not uploaded. We can only accept JPEG or PNG images.</pre>';
	}
}
// 添加抗csrf验证
generateSessionToken();
?>
