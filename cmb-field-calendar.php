<?php

/*
Plugin Name: CMB2 Field Type: Calendar
Plugin URI: https://wordpress.org/plugins/commons-booking
GitHub Plugin URI: https://wordpress.org/plugins/commons-booking
Description: Calendar field type for CMB2.
Version: 0.1.3
Author: Annesley Newholm
License: MIT
*/

define('CMB2_CALENDAR_FIELD_NAME', 'calendar');

class CMB2_Field_Calendar {

    /**
     * @var string Version
     */
    const VERSION = '0.1.0';

    /**
     * CMB2_Field_Calendar constructor.
     */
    public function __construct() {
        add_filter( 'cmb2_render_calendar',   [ $this, 'render_calendar' ], 10, 5 );
        add_filter( 'cmb2_sanitize_calendar', [ $this, 'sanitize_calendar' ], 10, 4 );
    }

    /**
     * Render the field
     *
     * @param $field
     * @param $field_escaped_value
     * @param $object_id
     * @param $object_type
     * @param $field_type_object
     */
    public function render_calendar(
        CMB2_Field $field,
        $field_escaped_value,
        $object_id,
        $object_type,
        CMB2_Types $field_type_object
    ) {
				global $post, $wp_query;

        $outer_wp_query = $wp_query;
        $this->enqueue_scripts();

        if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
            $field_type_object->type = new CMB2_Type_Text( $field_type_object );
        }

        $yesterday        = (new DateTime())->sub(   new DateInterval( 'P2D' ) );
        $next2month       = (clone $yesterday)->add( new DateInterval( 'P2M' ) );

        // Inputs
        $url              = $_SERVER['REQUEST_URI'];
        $options          = $field->options();
				$startdate_string = ( isset( $_GET['startdate'] )   ? $_GET['startdate'] : $yesterday->format(  CB2_Query::$date_format ) );
				$enddate_string   = ( isset( $_GET['enddate']   )   ? $_GET['enddate']   : $next2month->format( CB2_Query::$date_format ) );
        $view             = ( isset( $_GET['view'] ) ? $_GET['view'] : CB2_Week::$static_post_type );

        // Defaults
        $default_query    = array(
					'post_status'    => CB2_Post::$PUBLISH,
					'post_type'      => CB2_PeriodItem::$all_post_types,
					'posts_per_page' => -1,
					'order'          => 'ASC',          // defaults to post_date
					'date_query'     => array(
						'after'   => $startdate_string,
						'before'  => $enddate_string,
						'compare' => $view,
					),
        );

        // Analyse options
        $context       = ( isset( $options[ 'context' ] )  ? $options[ 'context' ]  : 'list' );
        $template      = ( isset( $options[ 'template' ] ) ? $options[ 'template' ] : NULL );
        $query_options = ( isset( $options[ 'query' ] )    ? $options[ 'query' ]    : array() );
        $query_args    = array_merge( $default_query, $query_options );

        // Request period items
        $wp_query = new WP_Query( $query_args );
        // Context Menu Actions
				$wp_query->actions = ( isset( $options[ 'actions' ] ) ? $options[ 'actions' ] : array() );

        // Debug
        if ( WP_DEBUG ) {
					$post_types = array();
					$post_count = count( $wp_query->posts );
					foreach ( $wp_query->posts as $post )
						$post_types[$post->post_type] = $post->post_type;
					print( "<div class='cb2-WP_DEBUG' style='border:1px solid #000;padding:3px;font-size:10px;background-color:#fff;margin:1em 0em;'>" );
					print( "<b>$post_count</b> posts returned" );
					print( ' containing only <b>[' . implode( ', ', $post_types ) . "]</b> post_types" );
					print( ' <a class="cb2-calendar-krumo-show">more...</a><div class="cb2-calendar-krumo" style="display:none;">' );
					krumo( $wp_query );
					print( '</div></div>' );
				}

        // Date handling
        $startdate      = new DateTime( $startdate_string );
        $enddate        = new DateTime( $enddate_string );
        $pagesize       = $startdate->diff( $enddate );
        $timeless_url   = preg_replace( '/&(start|end)date=[^&]*/', '', $url );

        $nextpage_start = (clone $enddate);
        $nextpage_end   = (clone $nextpage_start);
        $nextpage_end->add( $pagesize );
        $nextpage_start_string = $nextpage_start->format( CB2_Query::$date_format );
        $nextpage_end_string   = $nextpage_end->format( CB2_Query::$date_format );

        $prevpage_start = (clone $startdate);
        $prevpage_end   = (clone $prevpage_start);
        $prevpage_start->sub( $pagesize );
        $prevpage_start_string = $prevpage_start->format( CB2_Query::$date_format );
        $prevpage_end_string   = $prevpage_end->format( CB2_Query::$date_format );

        // View handling
        $view_is_calendar_class = ( $view == CB2_Week::$static_post_type ? 'selected' : 'unselected' );
        $view_is_list_class     = ( $view == '' ? 'selected' : 'unselected' );
        $viewless_url = preg_replace( '/&view=[^&]*/', '', $url );

        // Render
        // TODO: the wp_cb2_view_sequence_date is limited to 1000 days at the moment
        // can we auto-extend this where necessary?
				print( "
				<div class='cb2-javascript-form cb2-calendar'>
					<div class='entry-header'>
						<div class='hide-if-no-js alignright actions bulkactions'>
							<label for='cb2-calendar-bulk-action-selector-top' class='screen-reader-text'>Select bulk action</label>
							<!-- no @name on these form elements because it is a *nested* form
								it is submitted only with JavaScript
								@js-name => @name during submission
							-->
							<select class='hide-if-no-js' id='cb2-calendar-bulk-action-selector-top' js-name='do_action'>
								<option value=''>Bulk Actions</option>
								<option value='CB2_PeriodEntity::block'>Block</option>
								<option value='CB2_PeriodEntity::unblock'>UnBlock</option>
							</select>
							<input type='button' class='hide-if-no-js button action' value='Apply'>
						</div>

						<div class='cb2-view-selector'>View:
							<a class='cb2-$view_is_calendar_class' href='$viewless_url&view=week'>calendar</a>
							| <a class='cb2-$view_is_list_class' href='$viewless_url&view='>list</a></div>
						<div class='cb2-calendar-pager'>
							<a href='$timeless_url&startdate=$prevpage_start_string&enddate=$prevpage_end_string'>&lt;&lt; previous page</a>
							| <a href='$timeless_url&startdate=$nextpage_start_string&enddate=$nextpage_end_string'>next page &gt;&gt;</a>
						</div>
					</div>
					<div class='cb2-calendar'>
						<div class='entry-content clear'>
							<table class='cb2-subposts'>" );
				CB2::the_calendar_header( $wp_query );
				print( '<tbody>' );
				CB2::the_inner_loop( $wp_query, $context, $template );
				print( '</tbody>' );
				CB2::the_calendar_footer( $wp_query );
				print( "</table>
						</div>
					</div>
					<div class='entry-footer'>
						<div class='cb2-calendar-pager'>
							<a href='$timeless_url&startdate=$prevpage_start_string&enddate=$prevpage_end_string'>&lt;&lt; previous page</a>
							| <a href='$timeless_url&startdate=$nextpage_start_string&enddate=$nextpage_end_string'>next page &gt;&gt;</a>
						</div>
					</div>
				</div>" );

        $wp_query = $outer_wp_query;
        $field_type_object->_desc( true, true );
    }

    /**
     * Sanitize values
     */
    public function sanitize_calendar( $override_value, $value, $object_id, $field_args ) {
        return $value;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'cmb2-calendar-main', plugins_url( 'assets/js/main.js',    __FILE__ ), NULL, self::VERSION );
        wp_enqueue_style(  'cmb2-calendar-main', plugins_url( 'assets/css/style.css', __FILE__ ), NULL, self::VERSION );
    }
}

new CMB2_Field_Calendar();
