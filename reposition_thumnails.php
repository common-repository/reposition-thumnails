<?php
/**
 * @package Reposition Thumnails
 * @author Puggan
 * @version 0.0.2-20130812
 * @filelocation: wp-content/plugins/reposition_thumnails/reposition_thumnails.php
 */
/*
Plugin Name: Reposition Thumnails
Description: Reposition Thumnails plugin for wordpress, for the moments when the center of the picture wasn't that good as thumnail
Version: 0.0.2-20130812
Author: Puggan
Author URI: http://blog.puggan.se
*/

DEFINE("REPOSITION_THUMBNAILS_PLUGIN_VERSION",'0.0.2');

add_action('admin_menu', 'add_menu_reposition_thumnails');

if(isset($_GET['page']) && $_GET['page'] == 'reposition_thumnails_menu')
{
	add_action('admin_print_scripts', 'reposition_thumnails_menu_script');
	add_action('admin_print_styles', 'reposition_thumnails_menu_styles');
}

function add_menu_reposition_thumnails()
{
	add_menu_page( 'Reposition Thumnails', 'Reposition Thumnails', 'manage_options', 'reposition_thumnails_menu', 'menu_reposition_thumnails' );
}

function menu_reposition_thumnails()
{
	if ( !current_user_can( 'manage_options' ) )
	{
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$new_url = FALSE;

	if($_POST['reposition_thumnails'])
	{
		$upload_dir_meta = wp_upload_dir();

		if(!preg_match("#^(?<w>[0-9]+)x(?<h>[0-9]+)$#", trim($_POST['image_size']), $size))
		{
			wp_die("Unknowned image size");
		}

		if(!preg_match("#^[0-9]+([\.,][0-9]+)?$#", $_POST['image_thumb_x']))
		{
			wp_die('Bad data in X');
		}
		if(!preg_match("#^[0-9]+([\.,][0-9]+)?$#", $_POST['image_thumb_y']))
		{
			wp_die('Bad data in Y');
		}
		if(!preg_match("#^[1-9][0-9]*([\.,][0-9]+)?$#", $_POST['image_thumb_w']))
		{
			wp_die('Bad data in W');
		}

		$url = preg_replace("#/\.*/#", '/', $_POST['image_url']);

		if(substr($url, 0, 4) != 'http')
		{
			$url = preg_replace("#/\.*/#", '/', get_bloginfo('url') . '/' . $_POST['image_url']);
		}

		if(substr($upload_dir_meta['baseurl'], 0, 4) == 'http')
		{
			$base_url = preg_replace("#/\.*/#", '/', $upload_dir_meta['baseurl'] . '/');
		}
		else
		{
			$base_url = preg_replace("#/\.*/#", '/', get_bloginfo('url') . '/' . $upload_dir_meta['baseurl'] . '/');
		}

		if(substr($url, 0, strlen($base_url)) != $base_url)
		{
			wp_die("bad image url");
		}
		else
		{
			$path = preg_replace("#/\.*/#", '/', $upload_dir_meta['basedir'] . '/' . substr($url, strlen($base_url)));
		}

		if(!file_exists($path))
		{
			wp_die("Can't find image file");
		}

		if(!preg_match("#^(?<b>.+)\.(?<e>[^\.]+)$#", $path, $path_parts))
		{
			wp_die("Bad file extension");
		}

		$new_path = "{$path_parts['b']}-{$size['w']}x{$size['h']}.{$path_parts['e']}";
		$new_url = substr(trim($_POST['image_url']), 0, -1 -strlen($path_parts['e'])) . "-{$size['w']}x{$size['h']}.{$path_parts['e']}";

		$editor = wp_get_image_editor($path);
		if(!$editor)
		{
			wp_die("No image editor installed");
		}

		$org_size = $editor->get_size();

		if(!$org_size['width'] OR !$org_size['height'])
		{
			wp_die("Bad image size: {$org_size['width']} x {$org_size['height']}");
		}

		$scale = $_POST['image_thumb_w'] / $org_size['width'];
		$src_scale = $org_size['width'] / $_POST['image_thumb_w'];

		$src_x = $_POST['image_thumb_x'] * $src_scale;
		$src_y = $_POST['image_thumb_y'] * $src_scale;
		$src_w = $size['w'] * $src_scale;
		$src_h = $size['h'] * $src_scale;
		$dst_w = $size['w'];
		$dst_h = $size['h'];
		if($src_x + $src_w > $org_size['width'])
		{
			$src_w = $org_size['width'] - $src_x;
			$dst_w = $src_w * $scale;
		}
		if($src_y + $src_h > $org_size['height'])
		{
			$src_h = $org_size['height'] - $src_y;
			$dst_h = $src_h * $scale;
		}
		$editor->crop($src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h);
		$editor->save($new_path);
	}

	echo '<form action="#" method="post">' . PHP_EOL;
	echo '<div class="wrap">' . PHP_EOL;
	echo '<div id="step1">' . PHP_EOL;
	if($new_url)
	{
		echo "<h3>Reposition done</h3>";
		echo "<img src='{$new_url}' />";
	}
	echo '<h3>Select Image</h3>' . PHP_EOL;
	echo '<p>Select image to reposition.</p>' . PHP_EOL;
	echo '<input id="image_url" type="hidden" name="image_url" value="" />' . PHP_EOL;
	echo '<input id="image_url_button" type="button" value=Select Image" onclick="formfield = \'upload_image\'; tb_show(\'\', \'media-upload.php?type=image&amp;tab=library&amp;TB_iframe=true\'); return false;" />' . PHP_EOL;
	echo '</div>' . PHP_EOL;
	echo '<div id="step2" style="display: none;">' . PHP_EOL;
	echo '<h3>Select size and position</h3>' . PHP_EOL;
	echo '<select id="image_size" name="image_size" onchange="update_step2(0);">' . PHP_EOL;
	foreach(array('thumbnail', 'medium', 'large') as $sn)
	{
		$s = array();
		$s['width'] = get_option($sn . '_size_w');
		$s['height'] = get_option($sn . '_size_h');
		echo "<option>{$s['width']}x{$s['height']}</option>" . PHP_EOL;
	}
	global $_wp_additional_image_sizes;
	foreach($_wp_additional_image_sizes as $s)
	{
		echo "<option>{$s['width']}x{$s['height']}</option>" . PHP_EOL;
	}
	echo '</select><br />' . PHP_EOL;
	echo '<label><span>X:</span><input id="image_thumb_x" name="image_thumb_x" value="0" onchange="update_step2()" onkeyup="update_step2()" /></label>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) - 1; update_step2(); return false;" >-</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) + 1; update_step2(); return false;" >+</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) - 10; update_step2(); return false;" >-10</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) + 10; update_step2(); return false;" >+10</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) - 100; update_step2(); return false;" >-100</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_x\'); e.value = parseInt(e.value) + 100; update_step2(); return false;" >+100</button>' . PHP_EOL;
		echo '<br />' . PHP_EOL;
	echo '<label><span>Y:</span><input id="image_thumb_y" name="image_thumb_y" value="0" onchange="update_step2()" onkeyup="update_step2()" /></label>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) - 1; update_step2(); return false;" >-</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) + 1; update_step2(); return false;" >+</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) - 10; update_step2(); return false;" >-10</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) + 10; update_step2(); return false;" >+10</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) - 100; update_step2(); return false;" >-100</button>' . PHP_EOL;
		echo '<button onclick="e = document.getElementById(\'image_thumb_y\'); e.value = parseInt(e.value) + 100; update_step2(); return false;" >+100</button>' . PHP_EOL;
		echo '<br />' . PHP_EOL;
	echo '<label><span>W:</span><input id="image_thumb_w" name="image_thumb_w" value="" onchange="update_step2()" onkeyup="update_step2()" /></label>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(-1); return false;" >-</button>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(+1); return false;" >+</button>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(-10); return false;" >-10</button>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(+10); return false;" >+10</button>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(-100); return false;" >-100</button>' . PHP_EOL;
		echo '<button onclick="change_zoom_width(+100); return false;" >+100</button>' . PHP_EOL;
		echo '<br />' . PHP_EOL;
	echo '<div id="image_box">' . PHP_EOL;
	echo '<img id="image" />' . PHP_EOL;
	echo '<div id="image_thumb_block_top" class="image_thumb_block"></div>' . PHP_EOL;
	echo '<div id="image_thumb_block_left" class="image_thumb_block"></div>' . PHP_EOL;
	echo '<div id="image_thumb_block_right" class="image_thumb_block"></div>' . PHP_EOL;
	echo '<div id="image_thumb_block_bottom" class="image_thumb_block"></div>' . PHP_EOL;
	echo '</div>' . PHP_EOL;
	echo '<input type="submit" value="save" name="reposition_thumnails" />' . PHP_EOL;
	echo '</div>' . PHP_EOL;
	echo '</div>' . PHP_EOL;
	echo '</form>' . PHP_EOL;
	echo "<style>";
	echo <<<CSS_BLOCK
#image_box
{
	position: relative;
	overflow: hidden;
	background-color: red;
}
DIV.image_thumb_block
{
	position: absolute;
	left: 0px;
	top: 0px;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.75);
}
CSS_BLOCK;
	echo "</style>";
}

function reposition_thumnails_menu_script()
{
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_register_script('reposition_thumnails', WP_PLUGIN_URL . '/reposition_thumnails/reposition_thumnails.js', array('media-upload','thickbox'));
	wp_enqueue_script('reposition_thumnails');
}

function reposition_thumnails_menu_styles()
{
	wp_enqueue_style('thickbox');
}
?>
