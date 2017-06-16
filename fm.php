<?php
#############################################################################
#
#           FILE MANAGER
#
#           By Good Programmer
#           URL: http://usa.goodpanel.ru/file_manager_in_one_file_php
#           Last Changed: 06.2017
#
#############################################################################

#  SETTINGS
   $start_pass = '123456'; // password
// $start_path = '/var/www/site.com/data/www'; // start path
   $start_path = $_SERVER['DOCUMENT_ROOT'];   // start path
// if(empty($_SESSION['admin'])){exit('No session admin');}  // FOR YOU CMS! 

#############################################################################
ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Moscow');
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');
setlocale(LC_ALL, 'en_US.UTF8','en_US.UTF-8'); 
#############################################################################
session_start();
if(!empty($_SESSION['start_pass_error']) and $_SESSION['start_pass_error'] >= 10)
{
	$_SESSION['start_pass_error_time'] = time() + (30 * 60);
	$_SESSION['start_pass_error'] = 0;
}
if(!empty($_SESSION['start_pass_error_time']) and $_SESSION['start_pass_error_time'] > time())
{
	echo '<br><center>The number of attempts has been exceeded, access is blocked for 30 minutes. Opening through '.($_SESSION['start_pass_error_time'] - time()).' sec.</center>';
	exit;
}
if(!empty($_POST['pass']) and $_POST['pass'] == $start_pass)
{
	$_SESSION['start_pass'] = 1;
	$_SESSION['start_pass_error'] = 0;
	$_SESSION['start_pass_error_time'] = 0;
}
if(!empty($_POST['pass']) and $_POST['pass'] != $start_pass)
{
	if(empty($_SESSION['start_pass_error']))
	{
		$_SESSION['start_pass_error'] == 0;
	}
	$_SESSION['start_pass_error']++;
}
if(!empty($_GET['fm_exit']))
{
	$_SESSION['start_pass'] = 0;
}
if(empty($_SESSION['start_pass']))
{
	?>
<style>
@charset "utf-8";
* {margin:0; padding:0; box-sizing:border-box; }
body {height:100%; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px; line-height:130%; text-align:center; color:#444; }
form {display:inline-block; margin:30px auto; padding:30px; background-color:#F0F0F0; }
input, button {padding:10px 20px; font-size:16px; text-align:center; cursor:pointer; }
</style>
<form action="<?=basename(__FILE__)?>" method="post">
<?
if(!empty($_SESSION['start_pass_error']) and $_SESSION['start_pass_error'] > 3)
{
	echo 'There are still attempts: '.(10 - $_SESSION['start_pass_error']).'<br>';
}
?>
<input type="password" name="pass" autofocus />
<br><br>
<button class="button">Log in</button>
</form>
    <?
	exit;
}
#############################################################################
if(empty($start_path))
{
	$start_path = __FILE__;
}
$start_path = dirname($start_path).'/'.basename($start_path);
#############################################################################
function get_size($path) 
{
    $size = 0;
    if(file_exists($path))
	{
        if(is_dir($path)) 
		{
            foreach(scandir($path) as $p) 
			{
                if($p != '.' and $p != '..') 
				{
					$size += get_size($path.'/'.$p);
				}
            }
        }
		else{
			$size = filesize($path);
		}
    }
    return $size;
}
#############################################################################
function format_size($size) 
{
    if ($size>0){
        $j = 0;
        $ext = array(' bytes',' Kb',' Mb',' Gb',' Tb');
        while($size >= pow(1024,$j)){ ++$j;}
        return round($size / pow(1024,$j-1) * 100) / 100 . $ext[$j-1];
    } else return '0 bytes';
}
#############################################################################
function breadcrumbs($path)
{
	global $start_path;	
	$start = $start_path;
	$arr = explode('/', $path);
	$path_to = str_replace(array('//',$start_path), array('/',''), $path, $count);
	if($count == 1)
	{
		$arr = explode('/', $path_to);
	}
	else{
		$start = '';
		$arr = explode('/', $path);			
	}
	if(strlen($path) == strlen($start_path))
	{
		$start = '';
		$arr = explode('/', $start_path);	
	}
	$str = '';
	$pp = '';
	foreach($arr as $dir)
	{
		$pp .= '/'.$dir;
		if(@is_file($start.$pp))
		{
			$str .= '<a href="javascript:void(0);" onclick="open_file(\''.$start.$pp.'\')" title="'.$start.$pp.'">'.$dir.'</a>';				
		}
		else{
			$str .= '<a href="javascript:void(0);" onclick="open_folder(\''.$start.$pp.'\')" title="'.$start.$pp.'">'.$dir.'</a>/';	
		}	
	}	
	return str_replace('//','/',$str);
}
#############################################################################
function del_path($path) 
{
    if(file_exists($path))
	{
        chmod($path,0755);
        if(is_dir($path))
		{
            foreach(scandir($path) as $p) 
			{
                if($p != '.' && $p != '..')
				{
					del_path($path.'/'.$p);
				}
            }
            rmdir($path);
        }
		else{
			unlink($path);
		}
    }
}
#############################################################################
function copy_path($path, $to) 
{
    if(file_exists($path))
	{
		$f = $to.'/'.basename($path);
        if(is_dir($path))
		{
			mkdir($f);
			if(file_exists($f))
			{
				foreach(scandir($path) as $p) 
				{
					if($p != '.' && $p != '..')
					{
						copy_path($path.'/'.$p, $f);
					}
				}
			}
        }
		else{
			copy($path, $f);
		}
    }
}
#############################################################################
function add_zip($zip, $path, $name, $dir = '')
{
		if(file_exists($path))
		{
			if(is_dir($path)) 
			{
				$dir = basename($path);
				if($zip->addEmptyDir($dir)) 
				{
					foreach(scandir($path) as $f) 
					{
						if($f != '.' and $f != '..') 
						{
							add_zip($zip, $path.'/'.$f, $f, $dir);
						}
					}
				}
				else{
					echo 'Error create folder: '.$dir;
				}
			}
			else{
				$zip->addFile($path, $dir.'/'.basename($path));
			}
		}
}
function create_zip($path, $name)
{
	$p = str_replace(basename($path),'',$path);
	$zip = new ZipArchive;
	if($zip->open($p.$name.'.zip', ZIPARCHIVE::CREATE) === true)
	{
		add_zip($zip, $path, $name);
		$zip->close();
		echo '<script type="text/javascript">open_folder(\''.$p.'\')</script>';
	}
	else{
		echo 'Error create zip: '.$p.$name.'.zip';
	}
}
#############################################################################
function list_path($path)
{
	clearstatcache(); 
	$arr_dir = array();
	$arr_file = array();
	if(is_dir($path))
	{
		$n = 0;	
		foreach(scandir($path) as $f)
		{
			//$f = iconv('CP1251', 'UTF-8//TRANSLIT', $f);
			//$encoding = mb_detect_encoding($f);

			//$enc = array('WINDOWS-1251','WINDOWS-1252','CP1251','CP1252','KOI8-R','KOI8-U','UTF-8','UTF-16');

			
			//$f = mb_convert_encoding($f, 'WINDOWS-1251');
			
			if($f != '.' and $f != '..')
			{	
				if(is_dir($path.'/'.$f))
				{
					$arr_dir[$n]['name'] = $f;
					$arr_dir[$n]['path'] = $path.'/'.$f;
					$arr_dir[$n]['type'] = filetype($path.'/'.$f);
					$arr_dir[$n]['date'] = filemtime($path.'/'.$f);
					//$arr_dir[$n]['size'] = get_size($path.'/'.$f);
				}
				else{
					$arr_file[$n]['name'] = $f;
					$arr_file[$n]['path'] = $path.'/'.$f;
					$arr_file[$n]['type'] = filetype($path.'/'.$f);
					$arr_file[$n]['date'] = filemtime($path.'/'.$f);
					//$arr_file[$n]['size'] = filesize($path.'/'.$f);
					$arr_file[$n]['ext'] = strtolower(str_replace('.','',strrchr($f, '.')));
				}
				$n++;
			}
		}
	}	
	asort($arr_dir);
	asort($arr_file);
	return array_merge($arr_dir, $arr_file);
}
#############################################################################
if(!empty($_POST['open_folder']))
{
	$path = $_POST['path'];
	//echo '<a class="right" href="javascript:void(0);" onclick="open_folder(\''.$path.'\')">Reload</a>';
	echo '<h2>'.basename($path).'</h2>';
	echo '<h6>'.breadcrumbs($path).'</h6>';	

	if(!is_dir($path))
	{
		echo '<p>This is not a directory!</p>';
		echo '<a href="javascript:void(0);" onclick="open_file(\''.$path.'\')">Open as file</a>';
		exit;
	}
	echo '<ul class="list_files">';
	$n = 0;
	foreach(list_path($path) as $n => $f)
	{
		if($f['type'] == 'dir')
		{
			echo '<li class="folder">
			<i class="fa fa-folder" aria-hidden="true"></i>
			<a href="javascript:void(0);" onclick="open_folder(\''.$f['path'].'\')">'.$f['name'].'</a>
			<a href="javascript:void(0);" class="hover delete" onclick="del_path(\''.$f['path'].'\')" title="Delete folder">Delete</a>
			<a href="javascript:void(0);" class="hover rename" onclick="rename_file(\''.$f['path'].'\')" title="Rename/Move folder">Rename/Move</a>
			<a href="javascript:void(0);" class="hover copy" onclick="copy_path(\''.$f['path'].'\',\''.str_replace('/'.basename($f['path']),'',$f['path']).'/\')" title="Copy folder">Copy</a>
			<a href="javascript:void(0);" class="hover zip" onclick="create_zip(\''.$f['path'].'\')" title="Create ZIP">ZIP</a>
			</li>';
			$n++;
		}
		else{
			echo '<li>
			<i class="fa fa-file-o" aria-hidden="true"></i> 
			<a href="javascript:void(0);" onclick="open_file(\''.$f['path'].'\')">'.$f['name'].'</a>
			<a href="javascript:void(0);" class="hover delete" onclick="del_file(\''.$f['path'].'\')" title="Delete file">Delete</a>
			<a href="javascript:void(0);" class="hover rename" onclick="rename_file(\''.$f['path'].'\')" title="Rename & Move file">Rename/Move</a>
			<a href="javascript:void(0);" class="hover copy" onclick="copy_path(\''.$f['path'].'\',\''.str_replace('/'.basename($f['path']),'',$f['path']).'/\')" title="Copy file">Copy</a>
			';
			if(!empty($f['ext']) and $f['ext'] == 'zip')
			{
				echo '<a href="javascript:void(0);" class="hover zip" onclick="unzip(\''.$f['path'].'\')" title="UNZIP">UNZIP</a> ';
			}
			else{
				echo '<a href="javascript:void(0);" class="hover zip" onclick="create_zip(\''.$f['path'].'\')" title="Create ZIP">ZIP</a> ';
			}			
			echo '<a href="'.basename(__FILE__).'?download='.$f['path'].'" class="hover download" download title="Download">Download</a>
			</li>';
			$n++;
		}
	}
	echo '</ul>';
	if($n == 0)
	{
		echo '<p>Catalog is empty.</p>';
	}
	echo '<br>';
?>

<table border="0" cellspacing="0" cellpadding="10">
  <tr>
    <td valign="top" style="padding:10px"><a class="button" id="upload_but" href="javascript:void(0);">Upload</a> <span id="upload_status"></span></td>
    <td valign="top" style="padding:10px"><a class="button" href="javascript:void(0);" onClick="new_folder('<?=$path?>')">Create folder</a></td>
    <td valign="top" style="padding:10px"><a class="button" href="javascript:void(0);" onClick="new_file('<?=$path?>')">Create file</a></td>
    <td valign="top" style="padding:10px"><a class="button" href="javascript:void(0);" onClick="rename_file('<?=$path?>')">Rename/Move</a></td>
    <td valign="top" style="padding:10px"><a class="button" href="javascript:void(0);" onClick="copy_path('<?=$path?>','<?=str_replace('/'.basename($path),'',$path)?>/')" title="Copy folder">Copy</a></td>
    <td valign="top" style="padding:10px"><a class="button" href="javascript:void(0);" onClick="create_zip('<?=$path?>')" title="Create ZIP">ZIP</a></td>
  </tr>
</table>

<script type="text/javascript">

$('#upload_but').uploadFile({
url:'<?=$_SERVER['REQUEST_URI']?>',
multiple:true,
fileName:'upload_file',
returnType:'json',
		formData: {
			'upload' : '1',
			'path' : '<?=$path?>'
			},
onSubmit:function(files)
{
	$("#upload_status").html($("#upload_status").html()+"<br/>Submitting:"+JSON.stringify(files));
	//return false;
},
onSuccess:function(files,data,xhr,pd)
{

	$("#upload_status").html($("#upload_status").html()+"<br/>Success for: "+JSON.stringify(data));
	
},
afterUploadAll:function(obj)
{
	$("#upload_status").html($("#upload_status").html()+"<br/>All files are uploaded");	
	open_folder('<?=$path?>');
},
onError: function(files,status,errMsg,pd)
{
	$("#upload_status").html($("#upload_status").html()+"<br/>Error for: "+JSON.stringify(files));
},
onCancel:function(files,pd)
{
	$("#upload_status").html($("#upload_status").html()+"<br/>Canceled  files: "+JSON.stringify(files));
}
}); 

</script>

<?
	echo '<br><hr><br>';
	echo '<h3>Folder info</h3>';
	echo 'Size: '.format_size(get_size($path)).'<br>';
	echo 'Date: '.date('d.m.Y', filemtime($path)).' <small>'.date('H:i:s', filemtime($path)).'</small><br>';
	echo 'CHMOD: '.substr(sprintf('%o', fileperms($path)), -4).'<br>';
	echo 'Group: '.posix_getpwuid(filegroup($path))['name'].' ['.filegroup($path).']<br>';
	echo 'Owner: '.posix_getpwuid(fileowner($path))['name'].' ['.fileowner($path).']<br>';
	exit;
}
#############################################################################
if(!empty($_POST['open_file']))
{
	$path = $_POST['path'];
	echo '<h2>'.basename($path).'</h2>';
	echo '<h6>'.breadcrumbs($path).'</h6>'; 
	if(!is_file($path))
	{
		echo '<p>This is not a file!</p>';
		echo '<a href="javascript:void(0);" onclick="open_folder(\''.$path.'\')">Open as folder</a>';
		exit;
	}	
	$ext = strtolower(str_replace('.','',strrchr($path, '.')));
	if(in_array($ext, array('txt','htm','html','htaccess','php','css','js','xml','json','ini','')))
	{
		$content = file_get_contents($path);	
		$content = htmlspecialchars($content);		
		$mode = $ext; 
		switch($ext)
		{
			case 'js': $mode = 'javascript'; break;
			case 'html': $mode = 'php'; break;
		}
		?>
        <textarea id="file_content" style="width:100%; height:500px" rows="50"><?=$content?></textarea>
		<script>
        var editor = CodeMirror.fromTextArea(document.getElementById('file_content'),{
			lineNumbers: true,
			matchBrackets: true,
			mode: '<?=$mode?>',
			indentUnit: 4,
			indentWithTabs: true
        });		
		document.onkeydown = function(event){
			if(event.ctrlKey == true && event.keyCode == 83)
			{
				save_file('<?=$path?>');
				event.preventDefault();
			}			
		}
        </script>
        <?
		echo '<br>';
		echo '<a class="button" href="javascript:void(0);" onClick="save_file(\''.$path.'\')">Save</a> <span id="save_status">Ctrl + S</span>';	
	}
	elseif(in_array($ext, array('jpg','jpeg','gif','png','bmp')))
	{
		echo '<p><img style="max-width:400px; max-height:400px; background-color:#444; border:#333 1px solid" src="'.basename(__FILE__).'?show_image=1&path='.$path.'" /></p>';	
	}
	elseif(in_array($ext, array('wav','mp3','ogg','oga','m4a')))
	{
		echo '<p><audio controls src="'.basename(__FILE__).'?download='.$path.'"></audio></p>';	
	}
	elseif(in_array($ext, array('mp4','m4v','ogv','avi')))
	{
		echo '<p><video controls src="'.basename(__FILE__).'?download='.$path.'"></video></p>';	
	}
	elseif(in_array($ext, array('zip')))
	{
		$zip = new ZipArchive;
		if($zip->open($path) === true)
		{
			 for($i=0; $i<$zip->numFiles; $i++)
			 {
				 echo $i.') '.$zip->getNameIndex($i).'<br>';
			 }
		}
		else{
			 echo 'Broken archive!';
		}
	}	
	echo '<br>';
	echo '<br><hr><br>';
	echo '<h3>File info</h3>';
	echo 'Size: '.format_size(filesize($path)).'<br>';
	echo 'Date: '.date('d.m.Y', filemtime($path)).' <small>'.date('H:i:s', filemtime($path)).'</small><br>';
	echo 'CHMOD: '.substr(sprintf('%o', fileperms($path)), -4).'<br>';
	echo 'Group: '.posix_getpwuid(filegroup($path))['name'].' ['.filegroup($path).']<br>';
	echo 'Owner: '.posix_getpwuid(fileowner($path))['name'].' ['.fileowner($path).']<br>';
	echo 'Ext: '.$ext.'<br>';
	?>
    <a class="button" href="javascript:void(0);" onClick="rename_file('<?=$path?>')">Rename/Move</a>
    <a class="button" href="<?=basename(__FILE__)?>?download=<?=$path?>" download>Download</a>
    <a class="button" href="javascript:void(0);" onClick="copy_path('<?=$path?>','<?=str_replace('/'.basename($path),'',$path)?>/')">Copy</a>
    <?
	exit;
}
#############################################################################
if(!empty($_POST['save_file']))
{
	$path = $_POST['path'];
	$text = $_POST['text'];	
    $f = fopen($path, 'w');
    fputs($f, $text, strlen($text));
    fclose($f); 	
	echo 'Saved :)';
    echo '<script type="text/javascript">setTimeout(function(){$("#save_status").text("Ctrl + S");}, 3000);</script>';
	exit;
}
#############################################################################
if(!empty($_GET['show_image']))
{
	$path = $_GET['path'];
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT', true, 200);
    header('Content-Type: image/jpeg');
	echo file_get_contents($path);
	exit;
}
#############################################################################
if(!empty($_POST['del_file']))
{
	$path = $_POST['path'];
	if(file_exists($path))
	{
		if(unlink($path))
		{
			echo '<script type="text/javascript">open_folder(\''.str_replace('/'.basename($path),'',$path).'\')</script>';
		}
		else{
			echo '<script type="text/javascript">alert("Error delete file: '.$path.'");</script>';
		}
	}
	exit;
}
#############################################################################
if(!empty($_POST['rename_file']))
{
	$path = $_POST['path'];
	$name = $_POST['name'];
	if(file_exists($path))
	{
		if(rename($path, $name))
		{
			echo '<script type="text/javascript">open_folder(\''.str_replace('/'.basename($path),'',$path).'\')</script>';
		}
		else{
			echo '<script type="text/javascript">alert("Error reneme: '.$path.'");</script>';
		}
	}
	exit;
}
#############################################################################
if(!empty($_POST['copy_path']))
{
	$path = $_POST['path'];
	$to = $_POST['to'];
	copy_path($path, $to);
	if(file_exists($to))
	{
		echo '<script type="text/javascript">alert("Copy successful!");</script>';
	}
	else{
		echo '<script type="text/javascript">alert("Error copy\nfrom: '.$path.'\nto: '.$to.'");</script>';
	}
	exit;
}
#############################################################################
if(!empty($_POST['del_folder']))
{
	$path = $_POST['path'];
	if(file_exists($path))
	{
		del_path($path);
		echo '<script type="text/javascript">open_folder(\''.str_replace('/'.basename($path),'',$path).'\')</script>';
	}
	exit;
}
#############################################################################
if(!empty($_POST['new_folder']))
{
	$path = $_POST['path'];
	$name = $_POST['name'];
	if(!file_exists($path.'/'.$name))
	{
		mkdir($path.'/'.$name);
		echo '<script type="text/javascript">open_folder(\''.$path.'\')</script>';
	}
	else{
		echo '<script type="text/javascript">alert("Folder exists!")</script>';
	}
	exit;
}
#############################################################################
if(!empty($_POST['new_file']))
{
	$path = $_POST['path'];
	$name = $_POST['name'];
	if(!file_exists($path.'/'.$name))
	{
		file_put_contents($path.'/'.$name, '');
		echo '<script type="text/javascript">open_folder(\''.$path.'\')</script>';
	}
	else{
		echo '<script type="text/javascript">alert("File exists!")</script>';
	}
	exit;
}
#############################################################################
if(!empty($_POST['create_zip']))
{
	$path = $_POST['path'];
	$name = $_POST['name'];
	create_zip($path, $name);
	exit;
}
#############################################################################
if(!empty($_POST['upload']))
{
	$path = $_POST['path'];	
	if(!file_exists($path))
	{
		exit('Directory does not exist!');
	}
	if(!empty($_FILES['upload_file']['name']))
	{
		if( move_uploaded_file($_FILES['upload_file']['tmp_name'], $path.'/'.$_FILES['upload_file']['name']) )
		{
			// ok
		}
		else{
			exit('Error loading file in '.$path);
		}
	}
	exit;
}
#############################################################################
if(!empty($_GET['download']))
{
	$path = $_GET['download'];
	$content = file_get_contents($path);
	header('Content-type: '.mime_content_type($path));
	header('Content-disposition: attachment; filename="'.basename($path).'"');
    header('Content-length: '.strlen($content));
    header('Content-transfer-encoding: binary');
    header('Cache-control: no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $content;
	exit;
}
#############################################################################
if(!empty($_POST['unzip']))
{
	$path = $_POST['path'];
	$dir = str_replace('/'.basename($path),'',$path);
	$zip = new ZipArchive;
	if($zip->open($path) === true)
	{
		$zip->extractTo($dir);
		$zip->close();	
		echo '<script type="text/javascript">open_folder(\''.$dir.'\')</script>';
	} 
	else{
		echo '<script type="text/javascript">alert("Unzip error!")</script>';
	}
	exit;
}
#############################################################################
if(!empty($_GET['php_info']))
{
	echo phpinfo();
	exit;
}
#############################################################################
if(!empty($_POST['last_changes']))
{
	$sec = $_POST['hours'] * 60 * 60;	
	$arr = last_changes($start_path, $sec);
	arsort($arr);
	echo '<ul>';
	foreach($arr as $v)
	{
		echo $v;
	}
	echo '</ul>';
	exit;
}
function last_changes($path, $sec)
{
	$arr = array();
	foreach(list_path($path) as $f)
	{
		if(time() - $sec < $f['date'])
		{
			if($f['type'] == 'dir')
			{
				$arr = $arr + last_changes($path.'/'.$f['name'], $sec);
				$arr[$f['date']] = '<li class="folder">'.date('d.m.Y', $f['date']).' <small>'.date('H:i:s', $f['date']).'</small> <i class="fa fa-folder" aria-hidden="true"></i> <a href="javascript:void(0);" onclick="open_folder(\''.$f['path'].'\')" title="'.$f['path'].'">'.$f['name'].'</a></li>';
			}
			else{
				$arr[$f['date']] = '<li>'.date('d.m.Y', $f['date']).' <small>'.date('H:i:s', $f['date']).'</small> <i class="fa fa-file-o" aria-hidden="true"></i> <a href="javascript:void(0);" onclick="open_file(\''.$f['path'].'\')" title="'.$f['path'].'">'.$f['name'].'</a></li>';
			}
		}
	}	
	return $arr;
}
#############################################################################
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FileManager | GoodPanel</title>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css" />
<link rel="shortcut icon" href="data:image/png;base64,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">
<script type="text/javascript" src="//lib.mega8.ru/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="//hayageek.github.io/jQuery-Upload-File/4.0.10/jquery.uploadfile.min.js"></script>
<link rel="stylesheet" href="//hayageek.github.io/jQuery-Upload-File/4.0.10/uploadfile.css">
    <link rel="stylesheet" href="//codemirror.net/lib/codemirror.css" />
    <script src="//codemirror.net/lib/codemirror.js"></script>
    <script src="//codemirror.net/addon/edit/matchbrackets.js"></script>
    <script src="//codemirror.net/mode/htmlmixed/htmlmixed.js"></script>
    <script src="//codemirror.net/mode/xml/xml.js"></script>
    <script src="//codemirror.net/mode/javascript/javascript.js"></script>
    <script src="//codemirror.net/mode/css/css.js"></script>
    <script src="//codemirror.net/mode/clike/clike.js"></script>
    <script src="//codemirror.net/mode/php/php.js"></script>
<style>
@charset "utf-8";
html {overflow-y:scroll; }
* {margin:0; padding:0; box-sizing:border-box; }
body {height:100%; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px; line-height:130%; text-align:center; color:#444; }
table {border:none; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; line-height:130%; }

:focus {outline:none; }
input:focus, 
textarea:focus{outline:1px solid #82CDFF; }

ul, ul li {list-style:none; margin:0; padding:0; }

h1 {font-size:30px; margin:10px 0px; }
h2 {font-size:25px; margin:10px 0px; }
h3 {font-size:20px; margin:10px 0px; }
h6 {font-size:13px; margin:5px 0px 15px 0px; display:inline-block; font-weight:normal; }
h6 a {color:#666; text-decoration:none; display:inline; border-bottom:#999 1px dotted; }
h6 a:hover {text-decoration:none; color:#FF9D00; border-color:#FF9D00; }

a {color:#1a3dc1; text-decoration:underline; }
a:hover {color:#FF9D00; text-decoration:underline; }
p {margin:15px 0; }
img {border:none; -webkit-user-select:none; }

ol {margin:0; padding:0 0 0 20px; }
ul {margin:0; padding:0 0 0 20px; }
li {margin:0; padding:0 0 0 10px; }

.left {float:left; }
.right {float:right; }
.hide {display:none; }
.clear {clear:both; }

.fa {font-size:16px!important; color:#3D4654; }
#doc_status {display:none; position:fixed; top:0; left:45%; padding:15px 20px; background-color:#FFC; }
#j_status {display:none; margin:0 20px; text-align:left; background-color:#FFC; }

#o {display:inline-block; padding:5px; font-weight:normal; color:green; background-color:#CAFFCA; }
#u {display:inline-block; padding:5px; font-weight:normal; color:red; background-color:#FFC6C6; }

iframe {border:#333 2px solid; }

#table, .table {table-layout:fixed; border-collapse:collapse; font-family:Arial, Helvetica, sans-serif;; background-color:#FFF; }
#table td, .table td {padding:7px; border:#CCC 1px dotted; word-wrap: break-word; }
.table a {text-decoration:none; }
.table a:hover {text-decoration:underline; }

input, select, textarea {padding:5px; font-family:Verdana, Geneva, sans-serif; color:#444; border:#999 1px solid; }
textarea {min-width:500px; min-height:60px; resize:vertical; }
label {padding:5px; }
label:hover {cursor:pointer; color:#444; }

.button {display:inline-block; padding:10px 15px; text-decoration:none; font-weight:bold; border:none; cursor:pointer; color:#FFF; background-color:#808080; }
.button:hover {color:#FFF; text-decoration:none; background-color:#5A5A5A; }

.ajax-upload-dragdrop * {text-decoration:none!important; }

.list_files li {padding:5px 0; }
.list_files li.folder a {font-weight:bold; }
.list_files li a {text-decoration:none; }
.list_files li:hover a {color:#FF9D00; }
.list_files li a.hover {display:none; color:#CCC; }
.list_files li:hover a.hover {display:inline-block; }
.list_files li:hover a.hover:hover {color:#F00; }


#catalog {float:left; width:20%; min-height:300px; margin:20px; padding:20px; text-align:left; background-color:#F5F5F5; }
#catalog ul li a {text-decoration:none; }

#content {float:left; width:70%; min-height:300px; margin:20px; padding:20px; text-align:left; background-color:#F5F5F5; }

.CodeMirror {height:500px; }
.cm-builtin, .cm-qualifier {color:#F39!important; }
</style>
</head>
<body>

<div id="doc_status">Loading...</div>
<div id="j_status"></div>

<a class="right" href="?fm_exit=1">Log out</a>
<div id="catalog">
<h1 onClick="location.href='<?=basename(__FILE__)?>'" style="cursor:pointer">FileManager</h1>
<h6><?=breadcrumbs($start_path)?></h6>
<?php
#############################################################################
echo '<ul class="list_files">';
foreach(list_path($start_path) as $n => $f)
{
	if($f['type'] == 'dir')
	{
		echo '<li class="folder"><i class="fa fa-folder" aria-hidden="true"></i> <a href="javascript:void(0);" onclick="open_folder(\''.$f['path'].'\')">'.$f['name'].'</a></li>';
	}
	else{
		echo '<li><i class="fa fa-file-o" aria-hidden="true"></i> <a href="javascript:void(0);" onclick="open_file(\''.$f['path'].'\')">'.$f['name'].'</a></li>';
	}
}
echo '</ul>';
#############################################################################
?>
<br>
    <ul>
        <li>Host: <?=$_SERVER['HTTP_HOST']?></li>
        <li>Server: <?=$_SERVER['SERVER_ADDR']?></li>
        <li>Client: <?=getenv('REMOTE_ADDR')?></li>
        <li><br></li>
        <li><a href="javascript:void(0);" onClick="last_changes()">Last changes</a></li>
        <li><a href="javascript:void(0);" onClick="php_info()">PHP info</a></li>
        <li><a href="http://usa.goodpanel.ru/file_manager_in_one_file_php" target="_blank">Support</a></li>
    </ul>
</div>
<div id="content">
<h1>Hi! :)</h1>
<p>This is free php file manager.</p>
</div>
<script type="text/javascript">
<?
if(!empty($_GET))
{
	foreach($_GET as $k => $v)
	{
		if($k == 'path')
		{
			echo '(\''.$v.'\');';
		}
		else{
			echo $k;
		}
	}
}
?>

function open_folder(path)
{
	history.pushState('','','<?=basename(__FILE__)?>?open_folder=1&path='+path);
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'open_folder=1&path='+path,  
			cache: false,  
			timeout: 60000,
			success: function(html){  			
				$('#content').html(html);  
				$('#doc_status').slideUp(20);
				$('#j_status').hide(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20);
				alert('Server does not respond, try again.');  
			} 
		});
}

function open_file(path)
{	
	history.pushState('','','<?=basename(__FILE__)?>?open_file=1&path='+path);
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'open_file=1&path='+path,  
			cache: false,  
			timeout: 60000,
			success: function(html){  			
				$('#content').html(html); 
				$('#doc_status').slideUp(20);
				$('#j_status').hide(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20);
				alert('Server does not respond, try again.');  
			} 
		});
}

function del_file(path)
{	
	if( confirm('DELETE file?') ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'del_file=1&path='+path,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20);
				$('#j_status').html(html).show(0); 
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function del_path(path)
{	
	if( confirm('DELETE folder and files?') ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'del_folder=1&path='+path,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function new_folder(path)
{	
	var name = prompt('NEW FOLDER\nFolder name:');
	if( name ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'new_folder=1&path='+path+'&name='+name,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function new_file(path)
{	
	var name = prompt('NEW FILE\nFile name:');
	if( name ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'new_file=1&path='+path+'&name='+name,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function rename_file(path)
{	
	var name = prompt('RENAME or MOVE\nNew path/name:', path);
	if( name ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'rename_file=1&path='+path+'&name='+name,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function copy_path(path, to)
{	
	var to = prompt('COPY\nTo path:', to);
	if( to ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'copy_path=1&path='+path+'&to='+to,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function create_zip(path)
{	
	var name = prompt('CREATE ZIP\nZIP name:','zip');
	if( name ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'create_zip=1&path='+path+'&name='+name,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function unzip(path)
{	
	if( confirm('UNZIP file?') ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'unzip=1&path='+path,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#doc_status').slideUp(20); 
				$('#j_status').html(html).show(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

function save_file(path)
{
	$('#save_status').html('Loading...'); 	
	
	//var text = encodeURIComponent($('#file_content').val());
	var text = encodeURIComponent(editor.getValue());
	
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'save_file=1&path='+path+'&text='+text,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#save_status').html(html);
				$('#j_status').hide(0);
			},
			error: function(){					
				$('#save_status').html('');
				alert('Server does not respond, try again.');  
			} 
		});
}

function php_info()
{	
	$('#content').html('<iframe src="<?=basename(__FILE__)?>?php_info=1" width="100%" height="650">Error iframe!</iframe>');
}

function last_changes()
{	
	var hours = prompt('LAST CHANGES\nFor how many hours:','12');
	if( hours ){
	$('#doc_status').slideDown(20); 		
		$.ajax({  
			type: 'POST',  
			url: '<?=basename(__FILE__)?>',  
			data: 'last_changes=1&hours='+hours,  
			cache: false,  
			timeout: 60000,
			success: function(html){ 			
				$('#content').html(html);
				$('#doc_status').slideUp(20);
				$('#j_status').hide(0);
			},
			error: function(){					
				$('#doc_status').slideUp(20); 
				alert('Server does not respond, try again.');  
			} 
		});
	}
}

</script>
</body>
</html>
