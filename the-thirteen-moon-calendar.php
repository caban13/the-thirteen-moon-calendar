<?php
/*
* Plugin Name: The Thirteen Moon Calendar
* Description: Adds a Thirteen Moon Calendar Widget and Shortcode
* Version: 2.2
* Author: Caban Oxlahun
* Text Domain: ttmc
* Domain Path: /languages/
*
*/

// Exit if accessed directly
if( !defined('ABSPATH') ) exit;

// Load ttmc class
require_once( plugin_dir_path( __FILE__ ) .'/includes/class.php' );
// Load ttmc widget
require_once( plugin_dir_path( __FILE__ ) .'/includes/widget.php' );
// Load ttmc shortcodes
require_once( plugin_dir_path( __FILE__ ) .'/includes/shortcodes.php' );
// Locate images
$img = plugin_dir_url( __FILE__ ) .'images/';


// Add JS & CSS
add_action('wp_enqueue_scripts', 'ttmc_scripts');
function ttmc_scripts() {
  // enqueue and localise scripts
  wp_enqueue_script( 'my-ajax-script', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ) );
  wp_localize_script( 'my-ajax-script', 'the_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
  wp_enqueue_style( 'ttmc-main-style', plugin_dir_url( __FILE__ ) .'css/style.css' );
}


// THE AJAX ADD ACTIONS
add_action( 'wp_ajax_the_ajax_action', 'ttmc_decoder_function' );
add_action( 'wp_ajax_nopriv_the_ajax_action', 'ttmc_decoder_function' ); // non logged in users


// Register languages
add_action( 'plugins_loaded', 'set_plugin_textdomain');
function set_plugin_textdomain(){
  // load_plugin_textdomain('ttmc', false, basename( dirname( __FILE__ ) ) .'/languages/');
  load_plugin_textdomain('ttmc', false, plugin_dir_url( __FILE__ ) .'languages/');
}

// Register Widget
add_action( 'widgets_init', 'register_the_thirteen_moon_calendar_widget' );
function register_the_thirteen_moon_calendar_widget(){
  register_widget( 'The_Thirteen_Moon_Calendar_Widget' );
}

// Register Dashboard Widget
add_action('wp_dashboard_setup', 'register_ttmc_dashboard_widgets');
function register_ttmc_dashboard_widgets() {
	global $wp_meta_boxes;
	wp_add_dashboard_widget('custom_help_widget', 'The Thirteen Moon Calendar', 'ttmc_dashboard_help');
}

// Register Dashboard Widget Help
function ttmc_dashboard_help() {
	global $current_user;
  $Calendar = new The13MoonCalendar();
  $myDate = $Calendar->NewCalculatedDate( (int) date('Y'), (int) date('m'), (int) date('d'));
  $output = '<p>'. sprintf( __('Olá %s, hoje é %s', 'ttmc'), $current_user->display_name, date_i18n("l, F d, Y", date('U')) ) .'</p>';
  $output .= '<p>'. __('A informação galáctica é:', 'ttmc') .'</p>';
  $output .= '<p>'. sprintf( __('Ano %s', 'ttmc'), $myDate->YearG->GetName() ) .'<br>';
  $output .= $Calendar->Draw("3DimensionalDateLong", $myDate) .'<br>';
  $output .= '<strong>'. __('Kin ', 'ttmc') . $myDate->Kin->Value .', '. $myDate->Kin->GetName() .'</strong></p>';
  $output .= '<p><a href="options-general.php?page=ttmc_admin_page">The Thirteen Moon Calendar</a></p>';
  echo $output;
}

// ADD A DECODER FORM TO A PAGE
add_shortcode("ttmc_decoder", "ttmc_ajax_decoder_form");
function ttmc_ajax_decoder_form( $args, $content = '' ){

  $day = (int) date('d');
  $month = (int) date('m');
  $year = (int) date('Y');
  $output = '';

  if ( strlen( $content ) ) { 
    $output .= '<div class="ttmc-content">'. wpautop( $content ) .'</div>'; 
  }

  $output .= '
  <form id="ttmc-decoder-form">
    <p class="ttmc-input-container">'. __('Data (dd/mm/aaaa):', 'ttmc') .'&nbsp;</p>
    <div>
      <label for="day" class="sr-only">'. __('Dia', 'ttmc') .'</label>
      <select name="day" id="day">';
        for ($i = 1; $i != 32; ++$i) {
          if ($day == $i) { $output .= '<option selected>'; } else { $output .= '<option>'; }
          if ($i < 10) { $output .= '0'. $i; } else { $output .= $i; }
          $output .= '</option>';
        }
        $output .= '
      </select>
      <label for="month" class="sr-only">'. __('Mes', 'ttmc') .'</label>
      <select name="month" id="month">';
        for ($i = 1; $i != 13; ++$i) {
          if ($month == $i) { $output .= '<option selected>'; } else { $output .= '<option>'; }
          if ($i < 10) { $output .= '0'. $i; } else { $output .= $i; }
          $output .= '</option>';
        }
        $output .= '
      </select>
      <label for="year" class="sr-only">'. __('Ano', 'ttmc') .'</label>
      <select name="year" id="year">';
        for ($i = 300; $i != 2600; ++$i) {
          if ($year == $i) { $output .= '<option selected>'; } else { $output .= '<option>'; }
          $output .= $i; 
          $output .= '</option>';
        }
        $output .= '
      </select>
      <input name="action" type="hidden" value="the_ajax_action" />
      <input type="submit" id="submit_button" value="'. __('Calcula o teu Kin!', 'ttmc') .'" />
    </div>
  </form>';

  $output .= '<div id="response-area"></div>';
  return $output;
}

// THE DECODER FUNCTION
function ttmc_decoder_function() {
  global $img;
  /* this area is very simple but being serverside it affords the possibility of retreiving data
  from the server and passing it back to the javascript function */
  // check_ajax_referer( 'my_ajax_nonce' );

	$day = (int) sanitize_text_field( $_POST['day'] );
	$month = (int) sanitize_text_field( $_POST['month'] );
	$year = (int) sanitize_text_field( $_POST['year'] );
  $date = mktime(0, 0, 0, $month, $day, $year);

  $Calendar = new The13MoonCalendar();
  $myDate = $Calendar->NewCalculatedDate($year, $month, $day);
  $myWavespell = $myDate->Kin->GetWavespell();

  $output .= '
    <p>'. __('Data Gregoriana: ', 'ttmc') . date_i18n( "l, F d, Y", date('U', $date) ) .'</p>
    <input type="hidden" id="currentDate" value="'. date_i18n( "d-m-Y", date('U', $date) ) .'" />';

  if ( ! empty( $Calendar->ErrorMessage ) ) {
    $output .= '
    <div class="alert alert-warning">
      <strong>'. __('Erro:', 'ttmc') .'</strong><br />'. $Calendar->ErrorMessage .'
    </div>';
    $Calendar->ErrorMessage = '';
  } else {
    require_once( plugin_dir_path( __FILE__ ) .'/includes/sincro.php' );
    require_once( plugin_dir_path( __FILE__ ) .'/includes/wavespell.php' );
    // require_once( plugin_dir_path( __FILE__ ) .'/includes/moon.php' );
  }

  echo $output; // this is passed back to the javascript function
  die(); // wordpress may print out a spurious zero without this - can be particularly bad if using json
}


// registers TTMC admin menus
add_action('admin_menu', 'ttmc_admin_menu_page');
function ttmc_admin_menu_page() {
  add_options_page( 'Thirteen Moon Admin Page', 'Thirteen Moon', 'manage_options', 'ttmc_admin_page', 'ttmc_admin_page' );
}
// TTMC admin page
function ttmc_admin_page() {
  $year = (int) date('Y');
  $month = (int) date('m');
  $day = (int) date('d');

  $calendar = new The13MoonCalendar();
  $calendarDate = $calendar->newCalculatedDate($year, $month, $day);

  ob_start();
  ?>
		<div class="wrap">
			<h1>The Thirteen Moon Calendar</h1>
      <p><?php echo date_i18n("l, F d, Y", date('U')) ?></p>
      <p><?php _e('A informação galáctica de hoje é:', 'ttmc') ?></p>
      <p><?php printf( __('Ano %s', 'ttmc'), $calendarDate->YearG->GetName() ) ?><br>
      <?php echo $calendar->Draw("3DimensionalDateLong", $calendarDate) ?><br>
      <strong><?php printf( __('Kin %1$s, %2$s', 'ttmc'), $calendarDate->Kin->Value, $calendarDate->Kin->GetName() ) ?></strong></p>
      <h3><?php _e('Shortcodes:', 'ttmc') ?></h3>
      <dl>
      <dt><strong><?php _e('Data: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe uma data das 13 Luas, usando uma data gregoriana.', 'ttmc') ?><br>
        <strong>[ttmc_date date="yyyy-mm-dd"]</strong> 
      </dd>
      <dt><strong><?php _e('Data Completa: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe uma data das 13 Luas, usando uma data gregoriana.', 'ttmc') ?><br>
        <strong>[ttmc_date format="year" date="yyyy-mm-dd"]</strong> 
      </dd>
      <dt><strong><?php _e('Kin: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe o nome e número do Kin, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_kin date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_kin kin="kkk"]</strong><br>
      </dd>
      <dt><strong><?php _e('Poema: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe o Poema do Kin, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_kin format="poem" date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_kin format="poem" kin="kkk"]</strong><br>
      </dd>
      <dt><strong><?php _e('Frase: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe a Frase do Kin, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_kin format="phrase" date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_kin format="phrase" kin="kkk"]</strong><br>
      </dd>
      <dt><strong><?php _e('Imagem: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe a Imagem do Kin e o Guia, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_kin format="image" date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_kin format="image" kin="kkk"]</strong><br>
      </dd>
      <dt><strong><?php _e('Completo: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe a Imagem, o Guia e a Frase do Kin, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_kin format="full" date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_kin format="full" kin="kkk"]</strong><br>
      </dd>
      <dt><strong><?php _e('Wavespell: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Calcula e exibe uma Onda Encantada, usando uma data gregoriana ou um número Kin (1-260).', 'ttmc') ?><br>
        <strong>[ttmc_ws date="yyyy-mm-dd"]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_ws kin="kkk"]</strong>
      </dd>
      <dt><strong><?php _e('Decoder: ', 'ttmc') ?></strong></dt>
      <dd><?php _e('Exibe um Calculador de Kin (Kin Decoder).', 'ttmc') ?><br>
        <strong>[ttmc_decoder]</strong> <?php _e('ou', 'ttmc') ?> <strong>[ttmc_decoder]</strong><?php _e('texto opcional a ser exibido', 'ttmc') ?><strong>[/ttmc_decoder]</strong>.</dd>
      <dt><?php _e('Se nenhuma data for dada, a data de hoje é usada.', 'ttmc') ?><br>
      <?php _e(' Kin "kkk" tem precedência sobre uma data.', 'ttmc') ?></dt></dl>
      <h3>Widget:</h3>
      <p><?php _e('<strong>The Thirteen Moon Calendar Widget</strong> pode ser inserido em qualquer área de "widgets", tem várias opções de exibição e <br>pode ligar para a página do Calculador de Kin (onde o "Decoder Shortcode" está localizado).', 'ttmc') ?></p>
      <!-- ttmc options page -->
		</div>
  <?php
  $output = ob_get_clean();
	ob_flush();
	echo $output;
}


// Date functions
function ttmc_day($datetime) {
	return $datetime->format("d");
}

function ttmc_month($datetime) {
	return $datetime->format("m");
}

function ttmc_year($datetime) {
	return $datetime->format("y");
}

function ttmc_year4($datetime) {
	return $datetime->format("Y");
}

function ttmc_weekday($datetime) {
	return $datetime->format("l");
}
function weekday_loc($datetime) {
  switch ( $datetime->format("N") ) {
    case '0':
      return __( 'Domingo', 'ttmc' );
      break;
    case '1':
      return __( 'Segunda', 'ttmc' );
      break;
    case '2':
      return __( 'Terça', 'ttmc' );
      break;
    case '3':
      return __( 'Quarta', 'ttmc' );
      break;
    case '4':
      return __( 'Quinta', 'ttmc' );
      break;
    case '5':
      return __( 'Sexta', 'ttmc' );
      break;
    case '6':
      return __( 'Sábado', 'ttmc' );
      break;
  }
}
function month_loc($datetime) {
  switch ( $datetime->format("n") ) {
    case '1':
      return __( 'Janeiro', 'ttmc' );
      break;
    case '2':
      return __( 'Fevereiro', 'ttmc' );
      break;
    case '3':
      return __( 'Março', 'ttmc' );
      break;
    case '4':
      return __( 'Abril', 'ttmc' );
      break;
    case '5':
      return __( 'Maio', 'ttmc' );
      break;
    case '6':
      return __( 'Junho', 'ttmc' );
      break;
    case '7':
      return __( 'Julho', 'ttmc' );
      break;
    case '8':
      return __( 'Agosto', 'ttmc' );
      break;
    case '9':
      return __( 'Setembro', 'ttmc' );
      break;
    case '10':
      return __( 'Outubro', 'ttmc' );
      break;
    case '11':
      return __( 'Novembro', 'ttmc' );
      break;
    case '12':
      return __( 'Dezembro', 'ttmc' );
      break;
  }
}

// plus & minus buttton functions
function ttmc_previouskinday( $day, $month, $year, $interval ) {
	$date = new DateTime( $year .'-'. $month .'-'. $day );
	$prevdate = $date->sub( new DateInterval('P'. (string) $interval .'D') );
	$prevday		= ttmc_day($prevdate);
	$prevmonth	= ttmc_month($prevdate);
	if ($prevday == 29 && $prevmonth == 2) {
		$prevday = 28;
		$prevmonth = 2;
	}
	$prevyear = ttmc_year4($prevdate);
  $output = '
  <input name="year" type="hidden" value="'. $prevyear .'" />
  <input name="month" type="hidden" value="'. $prevmonth .'" />
  <input name="day" type="hidden" value="'. $prevday .'" />
  <input name="action" type="hidden" value="the_ajax_action" />
  <input type="submit" id="submit_button" class="btn btn-default" value="-'. (int)$interval .'"  >';
  return $output;
}

function ttmc_nextkinday($day, $month, $year, $interval) {
	$date = new DateTime($year .'-'. $month .'-'. $day);
	$nextdate = $date->add(new DateInterval('P'. (string)$interval .'D'));
	$nextday		= ttmc_day($nextdate);
	$nextmonth	= ttmc_month($nextdate);
	if ($nextday == 29 && $nextmonth == 2) {
		$nextday = 1;
		$nextmonth = 3;
	}
	$nextyear = ttmc_year4($nextdate);
  $output = '
  <input name="year" type="hidden" value="'. $nextyear .'" />
  <input name="month" type="hidden" value="'. $nextmonth .'" />
  <input name="day" type="hidden" value="'. $nextday .'" />
  <input name="action" type="hidden" value="the_ajax_action" />
  <input type="submit" id="submit_button" class="btn btn-default" value="+'. (int)$interval .'"  >';
  return $output;
}

