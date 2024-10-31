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

jQuery(document).ready(
	function()
	{
		window.send_to_editor = function send_to_editor(img_html)
		{
			img_src = img_html.match(/src="([^"]+)"/)[1];
			img_src_test = img_src.match(/^(.+)-[0-9]+x[0-9]+(\.[^\.]+)$/);
			if(img_src_test)
			{
				img_src = img_src_test[1] + img_src_test[2];
			}
			document.getElementById('image_url').value = img_src;
			tb_remove();
			document.getElementById('image').src = img_src;
			document.getElementById('step1').style.display = 'none';
			document.getElementById('step2').style.display = 'block';
			update_step2();
		}

		document.getElementById('image_box').addEventListener('DOMMouseScroll', image_scroll, true);
		document.getElementById('image_box').addEventListener('mousedown', image_drag_start, true);
		document.getElementById('image_box').addEventListener('mousemove', image_drag, true);
		document.getElementById('image_box').addEventListener('mouseup', image_drag_stop, true);
		
	}
);

var image_thumb_zoom_width;

function update_step2()
{
	if(set_zoom_width(0))
	{
		return true;
	}
	
	var image_thumb_size = document.getElementById('image_size').value;
	var image_thumb_size_parts = image_thumb_size.match(/^([0-9]+)x([0-9]+)$/);
	var image_thumb_width = parseInt(image_thumb_size_parts[1]);
	var image_thumb_height = parseInt(image_thumb_size_parts[2]);
	var image_thumb_x = parseFloat(document.getElementById('image_thumb_x').value);
	var image_thumb_y = parseFloat(document.getElementById('image_thumb_y').value);

	if(!image_thumb_zoom_width)
	{
		image_thumb_zoom_width = image_thumb_width;
		document.getElementById('image_thumb_w').value = image_thumb_zoom_width;
	}

	var min_width = image_thumb_width + image_thumb_x;
	var min_height = image_thumb_height + image_thumb_y;

	document.getElementById('image_thumb_block_top').style.height = (image_thumb_y) + 'px';
	document.getElementById('image_thumb_block_left').style.width = (image_thumb_x) + 'px';
	document.getElementById('image_thumb_block_right').style.left = (image_thumb_x + image_thumb_width) + 'px';
	document.getElementById('image_thumb_block_bottom').style.top = (image_thumb_y + image_thumb_height) + 'px';
	document.getElementById('image').style.width = (image_thumb_zoom_width) + 'px';
	document.getElementById('image_box').style.minHeight = (min_height) + 'px';
}

function set_zoom_width(new_width_raw)
{
	// image_thumb_zoom_width = parseFloat(document.getElementById('image_thumb_w').value);
	var new_width = parseFloat(new_width_raw);
	
	if(!new_width)
	{
		var image_thumb_size = document.getElementById('image_size').value;
		var image_thumb_size_parts = image_thumb_size.match(/^([0-9]+)x([0-9]+)$/);
		var image_thumb_width = parseInt(image_thumb_size_parts[1]);
		var image_thumb_height = parseInt(image_thumb_size_parts[2]);
		var image_offset = parseFloat(document.getElementById('image_thumb_x').value);
		var min_width = image_offset + image_thumb_width;
		//var prefered_width = image_offset*2 + image_thumb_width;
		if(image_thumb_zoom_width < min_width)
		{
			//image_thumb_zoom_width = prefered_width;
			image_thumb_zoom_width = min_width;
		}
		else
		{
			return false;			
		}
	}
	else if (new_width < 0)
	{
		return false;
	}
	else if(!image_thumb_zoom_width)
	{
		image_thumb_zoom_width = new_width;
	}
	else
	{
		var scale = new_width / image_thumb_zoom_width;
		image_thumb_zoom_width = new_width;
		
		document.getElementById('image_thumb_x').value = scale * parseFloat(document.getElementById('image_thumb_x').value);
		document.getElementById('image_thumb_y').value = scale * parseFloat(document.getElementById('image_thumb_y').value);
	}
	document.getElementById('image_thumb_w').value = image_thumb_zoom_width;
	update_step2();
	return true;
}

function change_zoom_width(diff)
{
	return set_zoom_width(parseFloat(diff) + image_thumb_zoom_width);
}

function image_scroll(scroll_object)
{
	scroll_object.preventDefault();
	if (scroll_object.detail > 0)
	{
		change_zoom_width(-10);
	}
	else if (scroll_object.detail < 0)
	{
		change_zoom_width(10);
	}
}

var image_draging = false;
var image_draging_last_x = false;
var image_draging_last_y = false;
function image_drag_start(mouse_event)
{
	if(mouse_event.buttons == 1)
	{
		//console.log("mouse down");
		//console.log(mouse_event);
		image_draging_last_x = false;
		image_draging_last_y = false;
		image_draging = true;
		image_drag(mouse_event);
	}
}
function image_drag_stop(mouse_event)
{
	//console.log("mouse up");
	//console.log(mouse_event);
	image_draging = false;
}
function image_drag(mouse_event)
{
	if(image_draging)
	{
		//console.log(mouse_event);
		mouse_event.preventDefault();
		var delta_x = 0;
		var delta_y = 0;
		if(image_draging_last_x)
		{
			delta_x = mouse_event.clientX - image_draging_last_x;
		}
		if(image_draging_last_y)
		{
			delta_y = mouse_event.clientY - image_draging_last_y;
		}
		if(delta_x)
		{
			document.getElementById('image_thumb_x').value = Math.max(0, delta_x + parseInt(document.getElementById('image_thumb_x').value));
		}
		if(delta_y)
		{
			document.getElementById('image_thumb_y').value = Math.max(0, delta_y + parseInt(document.getElementById('image_thumb_y').value));			
		}
		image_draging_last_x = mouse_event.clientX;
		image_draging_last_y = mouse_event.clientY;
		update_step2();
	}
}
