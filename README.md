# PHP文件上传漏洞

## 0x00  漏洞描述

​	在实际开发过程中文件上传的功能时十分常见的，比如博客系统用户需要文件上传功能来上传自己的头像，写博客时需要上传图片来丰富自己的文章，购物系统在识图搜索时也需要上传图片等，文件上传功能固然重要，但是如果在实现相应功能时没有注意安全保护措施，造成的损失可能十分巨大，为了学习和研究文件上传功能的安全实现方法，我将在下文分析一些常见的文件上传安全措施和一些绕过方法。

​	我按照最常见的上传功能--上传图片来分析这个漏洞。为了使漏洞的危害性呈现的清晰明了，我将漏洞防御措施划分为几个不同的等级来作比较

## 0x01  前端HTML页面代码

```php
<!DOCTYPE html>
<html>
<meta charset="utf-8">
<title>
    file_upload_test
</title>
<body>
<form enctype="multipart/form-data" action="upload_1.php" method="POST" />
<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
选择你要上传的图片:
<br />
<input name="uploaded" type="file" /><br />
<br />
<input type="submit" name="Upload" value="上传" />
</form>
</body>
</html>
```

前端的实现代码均为以上。界面如下图：

![Cq0S4e.png](https://s1.ax1x.com/2018/06/10/Cq0S4e.png)

## 0x01  零防御的PHP上传代码

**源代码 upload_0.php**

```php
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
```

这段PHP代码对上传的文件没有任何的过滤，只是将上传的文件直接存储到了网站uploads文件夹下，此时如果我们上传一个一句话木马并通过浏览器访问加上参数的地址或者使用中国菜刀直接连接，就可以为所欲为了。



```php
//一句话木马
<?php eval($_GET['cmd']);?>
```

## 0x01  初级防护-验证文件类型

**源代码 upload_1.php** 

```php
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
```

**防御方法**

初级防御的代码在审查用户上传的文件时加入了“Content-Type”验证，代码会自动识别文件类型并将文件类型以表单的形式进行验证，如果“Content-Type”是image/jpeg或者image/png时文件可以上传 成功，算是初级防御。

**绕过方法**

用BurpSuite截断代理修改数据包的相关字段即可完成绕过，本例上传的文件时shell.php，代码会将此文件的Content-Type识别为application/x-php，直接将application/x-php改为mage/jpeg即可绕过验证，而且对于文件大小的限制也是可以直接修改"MAX_FILE_SIZE"的方式突破限制从而上传更大的文件。

**修改前**![Cq099H.png](https://s1.ax1x.com/2018/06/10/Cq099H.png)

**修改后**

![Cq0C3d.png](https://s1.ax1x.com/2018/06/10/Cq0C3d.png)

![Cq03D0.png](https://s1.ax1x.com/2018/06/10/Cq03D0.png)

## 0x02  一般防护-验证文件后缀

**源代码 upoad_2.php**

```php
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
	if( ( strtolower( $uploaded_ext ) == "jpg" || strtolower( $uploaded_ext ) == "jpeg" || strtolower( $uploaded_ext ) == "png" ) &&
		( $uploaded_size < 100000 ) &&
		getimagesize( $uploaded_tmp ) ) {
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
```

相比较于前一种比价简单的验证content-type的防护方式，一般级别的防护措施换成了验证文件后缀的方式，顺便多说一句，在为了安全性设置一些限制时，使用白名单永远比设置黑名单要安全的多，因为总会有=各种方式绕过黑名单的方式或者是一些针对不同服务器系统或着服务器的特殊解析原理而造成的一些安全隐患。以下是获取文件后缀的代码：

```php
$uploaded_ext  = substr( $uploaded_name, strrpos( $uploaded_name, '.' ) + 1)
```

通过本语句获取文件名中最后一个“.”后的字符识别上传的文件名的后缀，并将后缀存储在一个变量中。

```php
if( ( strtolower( $uploaded_ext ) == "jpg" || strtolower( $uploaded_ext ) == "jpeg" || strtolower( $uploaded_ext ) == "png" ) &&
		( $uploaded_size < 100000 ) &&
		getimagesize( $uploaded_tmp ) )
```

而在if的逻辑判断中，需要上一条语句截取到的文件后缀为“jpg”，“jpeg”或者“png”，切且上传的文件大小不得大于10000b，如果只有这个限制方法的话，可以直接使用burpsuite进行00截断，从而使得在文件后缀验证时通过但是在文件转储的时候忽略掉00之后的内容从而实现后缀欺骗，具体方式如下：

- 假设网站只能上传图片文件并在后台欧了后缀的限制
- 此时你要上传一个shell.php的一句话木马
- 将"shell.php"改为"shell.php 1.png"
- 使用burpsuite截断代理，拦截数据包
- 将"shell.php 1.png"发送至decoder模块，从text模式转换为hex编辑模式，找到"shell.php 1.png"中空格对应的hex值“20”，将20改为00
- 从hex模式恢复为text并将修改过的字符串替换原来报文中的"shell.php 1.png"
- 发送报文，操作成功后会显示文件上传成功

操作成功后会显示文件上传成功，在php版本小于5.3.4的版本中，当Magic_quote_gpc选项为off时，可以在文件名中使用%00截断，所以可以把上传文件命名a1.php%00.png进行绕过，我们用bp抓包检测一下文件类型。 可以发现文件类型是png成功绕过前端，并且到服务器文件会被解析成php文件，因为00后面的被截断了，服务器不解析。

但是在本例中，00截断的方法不再有效，因为if条件中还有一个getimagesize()函数，此函数会自动识别上传的图片的文件头，长宽，mime类型等信息，因此如果上传的文件不是图片将无法上传。绕过这个限制的方法是制作图片马，我是在win环境下制作的，只需准备一个图片大小较小的jpg或者png格式的图片，打开cmd使用命令：

```cmd
copy 1.jpg/b+shell.php 2.jpg
```

来合成一张图片马，如果用二进制编辑器打开此文件会发现一句话木马写到了文件的后面，把这样的文件上传时，由于文件头仍然是jpg的文件头，getimagesize()函数也会正确的返回图片的大小和文类型，因此通这种方式可以绕过getimagesize()函数的限制，再结合00截断即可上传木马并在服务器端将文件解析为php脚本，从而正确执行。

但是如果服务器的PHP版本较高，则无法通过此方法进行漏洞的利用，需要结合文件包含漏洞进行利用。

## 0x03  无解的防护-全方面限制

当然安全只是相对的，没有绝对的安全，一下代码对输入的文件进行了多种方式的审查并进行了重新编码，是目前比较完善了安全防御措施。

**源代码  upload_2.php**

```php
<?php
if( isset( $_POST[ 'Upload' ] ) ) {
	// 检查token
	checkToken( $_REQUEST[ 'user_token' ], $_SESSION[ 'session_token' ], 'index.php' );
	$uploaded_name = $_FILES[ 'uploaded' ][ 'name' ];
	$uploaded_ext  = substr( $uploaded_name, strrpos( $uploaded_name, '.' ) + 1);
	$uploaded_size = $_FILES[ 'uploaded' ][ 'size' ];
	$uploaded_type = $_FILES[ 'uploaded' ][ 'type' ];
	$uploaded_tmp  = $_FILES[ 'uploaded' ][ 'tmp_name' ];
	$target_path   = 'uploads/';
	$target_file   =  md5( uniqid() . $uploaded_name ) . '.' . $uploaded_ext;
	$temp_file     = ( ( ini_get( 'upload_tmp_dir' ) == '' ) ? ( sys_get_temp_dir() ) : ( ini_get( 'upload_tmp_dir' ) ) );
	$temp_file    .= DIRECTORY_SEPARATOR . md5( uniqid() . $uploaded_name ) . '.' . $uploaded_ext;
	//判断是否是一张图片
	if( ( strtolower( $uploaded_ext ) == 'jpg' || strtolower( $uploaded_ext ) == 'jpeg' || strtolower( $uploaded_ext ) == 'png' ) &&
		( $uploaded_size < 100000 ) &&
		( $uploaded_type == 'image/jpeg' || $uploaded_type == 'image/png' ) &&getimagesize( $uploaded_tmp ) ) {
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
```

**上述代码的安全措施：**

- 添加了sessionToken，验证会话身份，用于防止csrf攻击
- 使用md5( uniqid() . $uploaded_name )函数，uniqid()函数是根据当前的时间，生成一个唯一的id，跟大多数随机函数一样，基于时间的随机函数在一定条件下也是可以差生碰撞的，因此本例中采用了md5()函数来保证生成id的唯一性，而且由于md5()函数对上传的文件名进行了重命名，因此无法使用00截断的方式来上传php或者其他恶意脚本文件。
- 以白名单的方式限制上传的文件后缀
- 限定上传的文件大小不得超过10000
- 通过imagecreatefromjpeg()和imagecreatefrompng()函数将上传的图片文件重新写入到一个新的图片文件中，这两个函数会自动将图片中的有害元数据抹除，因此即使黑客上传了一张图片马也会被这个函数过滤成一个纯正的图片。
- imagedestroy( $img )将用户上传的源文件删除
- unlink( $temp_file )删除过滤过程中产生的任何临时文件

## 0x04 个人总结

web漏洞种类繁多，利用方法奇葩而有趣，值得研究和学习
