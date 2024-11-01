<?php
/*
Plugin Name: TemplateHelp jQuery Popup Banner
Description: Displays popup banner with Featured Template from TemplateHelp.com collection using jQuery Toolkit
Author: TemplateHelp.com
Version: 1.2
*/

add_action('wp_ajax_get_popup_banner', 'get_popup_banner');
add_action('wp_ajax_nopriv_get_popup_banner', 'get_popup_banner');
define('PB_DEFAULT_AFF', 'wpincome');
register_activation_hook(__FILE__,'pb_install');
register_deactivation_hook(__FILE__,'pb_uninstall');

function pb_install() {
	$options = get_option('widget_popup_banner');
	$options['aff'] = '';
	$options['cat'] = 0;
	$options['type'] = 0;
	$options['is_roll_up'] = 0;
	$options['is_close'] = 1;
	$options['is_header'] = 1;
	$options['is_footer'] = 1;
	$options['header_image_url'] = plugin_dir_url(__FILE__).'images/header_image.png';
	$options['header_hover_image_url'] = plugin_dir_url(__FILE__).'images/header_hover_image.png';
	$options['header_dest_url'] = '#';
	$options['footer_image_url'] = plugin_dir_url(__FILE__).'images/footer_image.png';
	$options['footer_hover_image_url'] = plugin_dir_url(__FILE__).'images/footer_hover_image.png';
	$options['footer_dest_url'] = '#';
	update_option('widget_popup_banner', $options);
}

function pb_uninstall() {
	pb_install();
}

function pb_get_categories_list() {
	$cats = array();
	$file = @fopen("http://api.templatemonster.com/wpinc/categories.txt", "r");
	if ($file) {
		while ($fr = fgets($file, 1024)) {
			$fr = explode("\t", trim($fr));
			$cats[$fr[0]] = $fr[1];
		}
	}
	return $cats;
}
function pb_get_types_list() {
	$types = array();
	$file = @fopen("http://api.templatemonster.com/wpinc/types.txt", "r");
	if ($file) {
		while ($fr = fgets($file, 1024)) {
			$fr = explode("\t", trim($fr));
			$types[$fr[0]] = $fr[1];
		}
	}
	return $types;
}
// This gets called at the plugins_loaded action
function widget_popup_banner_init() {

	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_popup_banner_control() {
		$options = $newoptions = get_option('widget_popup_banner');
		if ( $_POST['popup_banner-submit'] ) {
			/*aff*/
			$newoptions['aff'] = trim(strip_tags(stripslashes($_POST['popup_banner-aff'])));
			/*cat*/
			$newoptions['cat'] = intval($_POST['popup_banner-cat']);
			/*type*/
			$newoptions['type'] = intval($_POST['popup_banner-type']);
			/*is_close*/
			$newoptions['is_roll_up'] = intval($_POST['popup_banner-roll_up']);
			$newoptions['is_close'] = 1-$newoptions['is_roll_up'];
			$newoptions['is_header'] = $newoptions['is_close'];
			$newoptions['is_footer'] = 1;

			/*header image url*/
			$hiu = trim(strip_tags(stripslashes($_POST['popup_banner-header_image_url'])));
			if (!empty($hiu)) {
				if (substr($hiu, 0, 7)!=='http://')
					$hiu = 'http://'.$hiu;
				$headers = get_headers($hiu);
				if (strpos($headers[0], '200')!==false) {
					$size = getimagesize($hiu);
					if ($size['mime'] == 'image/png' || $size['mime'] == 'image/gif') {
						$ext = substr($size['mime'], strpos($size['mime'], '/')+1);
						$time = time();
						$header_path = plugin_dir_path(__FILE__).'images/header_image_'.$time.'.'.$ext;
						$header_hover_path = plugin_dir_path(__FILE__).'images/header_hover_image_'.$time.'.'.$ext;
						copy($hiu, $header_path);
						$im = $size['mime'] == 'image/png' ? imagecreatefrompng($header_path) : imagecreatefromgif($header_path);
						imagesavealpha($im, true);
						if($im && imagefilter($im, IMG_FILTER_BRIGHTNESS, 20)) {
							imagepng($im, $header_hover_path);
							@chmod($header_hover_path, 0666);
							imagedestroy($im);
						}
						$newoptions['header_image_url'] = plugin_dir_url(__FILE__).'images/header_image_'.$time.'.'.$ext;
						$newoptions['header_hover_image_url'] = plugin_dir_url(__FILE__).'images/header_hover_image_'.$time.'.'.$ext;
					} else {
						?><script>alert('Invalid header image format (PNG or GIF only!)');</script><?php
					}
				}
			}
			/*header dest url*/
			$newoptions['header_dest_url'] = strip_tags(stripslashes($_POST['popup_banner-header_dest_url']));
			/*footer image url*/
			$fiu = strip_tags(stripslashes($_POST['popup_banner-footer_image_url']));
			if (!empty($fiu)) {
				if (substr($fiu, 0, 7)!=='http://')
					$fiu = 'http://'.$fiu;
				$headers = get_headers($fiu);
				if (strpos($headers[0], '200')!==false) {
					$size = getimagesize($fiu);
					if ($size['mime'] == 'image/png' || $size['mime'] == 'image/gif') {
						$ext = substr($size['mime'], strpos($size['mime'], '/')+1);
						$time = time();
						$footer_path = plugin_dir_path(__FILE__).'images/footer_image_'.$time.'.'.$ext;
						$footer_hover_path = plugin_dir_path(__FILE__).'images/footer_hover_image_'.$time.'.'.$ext;
						copy($fiu, $footer_path);
						$im = $size['mime'] == 'image/png' ? imagecreatefrompng($footer_path) : imagecreatefromgif($footer_path);
						imagesavealpha($im, true);
						if($im && imagefilter($im, IMG_FILTER_BRIGHTNESS, 20)) {
							imagepng($im, $footer_hover_path);
							@chmod($footer_hover_path, 0666);
							imagedestroy($im);
						}
						$newoptions['footer_image_url'] = plugin_dir_url(__FILE__).'images/footer_image_'.$time.'.'.$ext;
						$newoptions['footer_hover_image_url'] = plugin_dir_url(__FILE__).'images/footer_hover_image_'.$time.'.'.$ext;
					} else {
						?><script>alert('Invalid footer image format (PNG or GIF only!)');</script><?php
					}
				}
			}
			/*footer dest url*/
			$newoptions['footer_dest_url'] = strip_tags(stripslashes($_POST['popup_banner-footer_dest_url']));
		}
		if ($options['aff'] == '') {
			$newoptions['aff'] = PB_DEFAULT_AFF;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_popup_banner', $options);
		}

		if ($options['header_image_url'] == '') {
			$options['header_image_url'] = plugin_dir_url(__FILE__).'images/header_image.png';
			$options['header_hover_image_url'] = plugin_dir_url(__FILE__).'images/header_hover_image.png';
		}
		if ($options['footer_image_url'] == '') {
			$options['footer_image_url'] = plugin_dir_url(__FILE__).'images/footer_image.png';
			$options['footer_hover_image_url'] = plugin_dir_url(__FILE__).'images/footer_hover_image.png';
		}
		echo '		<div style="text-align:right">
		<label for="popup_banner-aff" style="line-height:35px;display:block;">';
		_e('Affiliate:', 'widgets');
		echo '<input type="text" id="popup_banner-aff" name="popup_banner-aff" value="'.wp_specialchars($options['aff'], true).'" />
		</label>';

		'<label for="popup_banner-close_button" style="line-height:35px;display:block;">';
		_e('Close button :', 'widgets');
		$is_roll_up = wp_specialchars($options['is_roll_up'], true);
		echo '<br/>
		<input type="radio"	name="popup_banner-roll_up" value="0"'.($is_roll_up == 0 ? " checked" : "").'/> Close me
		<input type="radio"	name="popup_banner-roll_up" value="1"'.($is_roll_up == 1 ? " checked" : "").'/> Roll up me
		</label>';

		echo '<label for="popup_banner-header_image_url" style="line-height:35px;display:block;">';
		_e('Header Image URL:', 'widgets');
		echo '<img src="'.wp_specialchars($options['header_image_url'], true).'" /><br/>';
		echo '<input type="text" id="popup_banner-header_image_url" name="popup_banner-header_image_url" value="" /></label>';

		echo '<label for="popup_banner-header_dest_url" style="line-height:35px;display:block;">';
		_e('Header Dest URL:', 'widgets');
		echo '<input type="text" id="popup_banner-header_dest_url" name="popup_banner-header_dest_url" value="'.wp_specialchars($options['header_dest_url'], true).'" />
		</label>';

		echo '<label for="popup_banner-footer_image_url" style="line-height:35px;display:block;">';
		_e('Footer Image URL:', 'widgets');
		echo '<img src="'.wp_specialchars($options['footer_image_url'], true).'" /><br/>';
		echo '<input type="text" id="popup_banner-footer_image_url" name="popup_banner-footer_image_url" value="" />
		</label>';

		echo '<label for="popup_banner-footer_dest_url" style="line-height:35px;display:block;">';
		_e('Footer Dest URL:', 'widgets');
		echo '<input type="text" id="popup_banner-footer_dest_url" name="popup_banner-footer_dest_url" value="'.wp_specialchars($options['footer_dest_url'], true).'" />
		</label>';

		echo '<label for="popup_banner-cats" style="line-height:35px;display:block;">';
		_e('Categories:', 'widgets');
		echo '</label>
		<select style="width:170px;font-size:11px;" id="popup_banner-cats" name="popup_banner-cat">
				<option value="All" '.("All" == $options['cat'] ? "selected=true" : "" ).'>Show all</option>';
      $cats = pb_get_categories_list();
			foreach ($cats as $id => $name) {
				echo '<option value="'.$id.'" '.($id == $options['cat'] ? "selected=true" : "" ).'>'.$name.'</option>';
			}
   	echo '</select>

		<label for="popup_banner-types" style="line-height:35px;display:block;">';
		_e('Types:', 'widgets');
		echo '</label>
		<select style="width:170px;font-size:11px;" id="popup_banner-types" name="popup_banner-type">
			<option value="All" '.("All" == $options['type'] ? "selected=true" : "" ).'>Show all</option>';
      $types = pb_get_types_list();
			foreach ($types as $id => $name) {
				echo '<option value="'.$id.'" '.($id == $options['type'] ? "selected=true" : "" ).'>'.$name.'</option>';
			}
		echo '
   	</select>

		<input type="hidden" name="popup_banner-submit" id="popup_banner-submit" value="1" />
		</div>';
	}

	if (!function_exists('get_plugin_path')) {
		function get_plugin_path() {
			return get_option('home').'/wp-content/plugins/'.plugin_basename(dirname(__FILE__));
		}
	}

	// This prints the widget
	function widget_popup_banner($args) {
		extract($args);
		$options = (array) get_option('widget_popup_banner');
		if ($options['header_dest_url']=='')
			$options['header_dest_url'] = '#';
		if ($options['footer_dest_url']=='')
			$options['footer_dest_url'] = '#';
		if ($options['header_image_url']=='') {
			$options['header_image_url'] = plugin_dir_url(__FILE__).'images/header_image.png';
			$options['header_hover_image_url'] = plugin_dir_url(__FILE__).'images/header_hover_image.png';
		}
		if ($options['footer_image_url']=='') {
			$options['footer_image_url'] = plugin_dir_url(__FILE__).'images/footer_image.png';
			$options['footer_hover_image_url'] = plugin_dir_url(__FILE__).'images/footer_hover_image.png';
		}
		$banner_height = 0;
		if ($options['is_header']) {
			list($width, $banner_height) = @getimagesize($options['header_image_url']);
		}
		if ($options['is_footer']) {
			list($width, $height) = @getimagesize($options['footer_image_url']);
			$banner_height += $height;
		}
		?>
		<script>
		if (typeof(jQuery) == 'undefined')
			document.write('<scr' + 'ipt type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></scr' + 'ipt>');
		</script>
		<div id="popup_banner"></div>
		<link rel="stylesheet" type="text/css" href="<?php echo get_plugin_path()?>/css/popup-banner.css">
		<script src="<?php echo get_plugin_path()?>/js/functions.js"></script>
		<script src="<?php echo get_plugin_path()?>/js/popup-banner.js"></script>
		<script>
	  $(function(){
  		$.getJSON("<?php echo get_option('home')?>/wp-admin/admin-ajax.php",
			{action:"get_popup_banner"},
			function(data){
				banner_height = <?php echo $banner_height?>+data.image_height;
			  $("#popup_banner").popupBanner({
					headerDestURL: "<?php echo $options['header_dest_url']?>",
  				headerURL: "<?php echo $options['header_image_url']?>",
  				headerHoverURL: "<?php echo $options['header_hover_image_url']?>",
  				footerDestURL: "<?php echo $options['footer_dest_url']?>",
  				footerURL: "<?php echo $options['footer_image_url']?>",
  				footerHoverURL: "<?php echo $options['footer_hover_image_url']?>",
					isHeader: <?php echo $options['is_header']?>,
					isFooter: <?php echo $options['is_footer']?>,
					isClose: <?php echo $options['is_close']?>,
					isRollUp: <?php echo $options['is_roll_up']?>,
					imageURL: data.imageURL,
					banner_height: banner_height,
					banner_width: data.image_width,
					destURL: data.destURL+"?aff=<?php echo $options['aff']?>"
				});
			});
	  });
	</script><?php
	}

	// Tell Dynamic Sidebar about our new widget and its control
	register_sidebar_widget(array('TemplateHelp jQuery Popup Banner', 'widgets'), 'widget_popup_banner');
	register_widget_control(array('TemplateHelp jQuery Popup Banner', 'widgets'), 'widget_popup_banner_control');
}

function get_popup_banner() {
	header('Cache-control: no-cache');
	$options = (array) get_option('widget_popup_banner');
	$type = intval($options['type']);
	$cat = intval($options['cat']);
	echo file_get_contents('http://templatehelp.com/codes/edu_banner.php?type='.$type.'&cat='.$cat);
	exit;
}

function pb_admin_warnings() {
	$options = (array) get_option('widget_popup_banner');
	if ($options['aff'] == '' || $options['aff'] == PB_DEFAULT_AFF) {
		function pb_warning() {
			echo '
			<div id="pb-warning" class="updated fade"><p><strong>Popup Banner Widget is almost ready.</strong> You must <a href="'.get_option('home').'/wp-admin/widgets.php">configure Affiliate</a> for it to work.</p></div>
			';
		}
		add_action('admin_notices', 'pb_warning');
	}
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_popup_banner_init');
pb_admin_warnings();
?>