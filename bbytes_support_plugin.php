<?php
/*
 * Plugin Name: WP-Admin Live Chat Support
 * Author: Burlington Bytes
 * Description: Provides live technical support and troubleshooting via a chat module from the WP Admin interface for Burlington Bytes clients.
 * Version: 1.0.3
 */
defined( 'ABSPATH' ) or die(); // silence is golden

class BBytes_Support_Plugin {
	private static $_this;
	private static $_wp_start_time;
	private $bbytes_info = array();

	public static function init() {
		if(!isset(self::$_this)) {
			self::$_this = new BBytes_Support_Plugin();
		}
		return self::$_this;
	}

	function __construct() {

		// initialize constants
		$this->bbytes_info['olark_id']          = '7423-716-10-9745';
		$this->bbytes_info['phone']             = '(802) 448-4001';
		$this->bbytes_info['phone-bare']        = preg_replace('/[^\d]/', '', $this->bbytes_info['phone']);
		$this->bbytes_info['email-support']     = 'support@burlingtonbytes.com';
		$this->bbytes_info['display-url']		= 'BurlingtonBytes.com';
		$this->bbytes_info['url']               = 'https://BurlingtonBytes.com/';
		$this->bbytes_info['chat-url']          = 'http://www.burlingtonbytes.com/chatpopup/#';
		$this->bbytes_info['what-is-this-url']  = 'https://www.burlingtonbytes.com/wp-admin-live-chat-support-plugin/faq/';
		$this->bbytes_info['plugin-url']        = 'https://www.burlingtonbytes.com/wp-admin-live-chat-support-plugin/';
		$this->bbytes_info['address1']          = '2 Church St // Burlington, VT';
		$this->bbytes_info['map-url']			= 'https://www.google.com/maps/place/Burlington+Bytes,+2+Church+St,+Burlington,+VT+05401/@44.4802223,-73.2146712,17';
		// $this->bbytes_info['address1']          = '2 Church St // Burlington, VTSuite 101 (LL)';
		// $this->bbytes_info['address2']          = 'Burlington, VT 05401';
		$this->bbytes_info['rss-feed']          = 'http://www.burlingtonbytes.com/feed/';
		$this->bbytes_info['slogan']            = "Vermont's Web Design and Marketing Experts";

		$this->bbytes_info['check_online_poll_interval'] = 5*60; //seconds, how often to update olark status from the source

		add_action( 'wp_dashboard_setup', array( $this, "wp_dashboard_setup" ) );

		//if enable live site monitoring
			add_action( 'wp', function() {
				$this->_wp_start_time = microtime(true);
				return;
			}, PHP_INT_MIN );

			add_action( 'wp_footer', array( $this, 'wp_footer' ), PHP_INT_MAX );

		//if enable admin chat support
			add_action( 'wp_ajax_check_olark_status', array( $this, "wp_ajax_check_olark_status" ) );
			add_action( 'wp_ajax_nopriv_check_olark_status', array($this, "wp_ajax_check_olark_status") );

			add_action( 'admin_enqueue_scripts', function() {
				wp_enqueue_script( 'jquery' );

				wp_enqueue_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0' );
				wp_enqueue_style( 'bbytes-support-css', plugins_url('/assets/css/bbytes_support_plugin.css', __FILE__) );
				wp_enqueue_script( 'bbytes-support-js', plugins_url('/assets/js/bbytes_support_plugin.js', __FILE__) );
			});
			add_action( 'admin_footer', array( $this, 'admin_footer' ), PHP_INT_MAX );

	}

	private function bbytes_info($key) {
		return $this->bbytes_info[$key];
	}

	// this is how we detect whether olark is online or not, we proxy the request for the client to
	//  1) keep it as a transient and share status across multiple users/pages on the same site
	//  2) AJAXing to olark directly would require CORS headers, which we don't want to add (esp. with a plugin) to enable this feature
	function wp_ajax_check_olark_status() {
		echo json_encode(array(
			'status' => $this->_check_olark_status()
		));
		wp_die();
	}

	private function _check_olark_status() {
		$olark_id = $this->bbytes_info('olark_id');

		$bbytes_support_chat_status_transient = 'bbytes-support-chat-status';
		$olark_status = get_transient($bbytes_support_chat_status_transient);
		if( !$olark_status ) {
			$url = "http://images-async.olark.com/status/" . $olark_id . "/image.png";

			$headers = get_headers( $url, 1 );
			if( $headers && $headers['Location'] ) {
				$olark_status = strpos( $headers['Location'], "online" ) !== false ? "online" : "offline";
			}
			else {
				$olark_status = "unknown";
			}

			set_transient($bbytes_support_chat_status_transient, $status, $this->bbytes_info('check_online_poll_interval'));
		}
		// return 'offline';
		return $olark_status;
	}

	function admin_footer() {
		$bbytes_olark_id = $this->bbytes_info('olark_id');
		?>
		<script>
			if( typeof bbytes_support_cfg  === 'undefined' || !bbytes_support_cfg ) {
				bbytes_support_cfg = {};
				bbytes_support_cfg['chat-url'] = '<?php echo $this->bbytes_info('chat-url'); ?>';
				bbytes_support_cfg['check_online_poll_interval'] = <?php echo( ($this->bbytes_info('check_online_poll_interval') + 1) * 1000 ); ?>;
			}
			if( typeof ajaxurl === 'undefined' || !ajaxurl ) {
				ajaxurl = "<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>";
			}
			jQuery(document).ready( function() {
				bbytes_support.update_olark_online_status('<?php echo $this->_check_olark_status();  ?>');
			});
		</script>

		<div id="bbytes-support-footer">
			<h2 class="bbytes-lf-grabber"><span>Get Support</span><i class="fa fa-chevron-up"></i></h2>
			<div class="bbytes-lf-slider">
				<div class="bbytes-lf-header">
					Need Assistance?<br/>
					Contact us now!
				</div>
				<table>
					<tr>
						<th><i class="fa fa-user fa-fw"></i></th>
						<td>
							<a href="<?php echo $this->bbytes_info('chat-url'); ?>" class="bbytes-chat">
								<span class="chat-status"></span>
								<span>Chat With a Live Person in VT</span>
							</a>
							<div class="chat-typical"><small>
								Agent currently: <span class="chat-status-text"></span>
								<br/>
								Usually online: <b>M-F 9-5 EST</b></small>
							</div>
						</td>
					</tr><tr>
						<th><i class="fa fa-phone fa-fw"></i></th>
						<td><a href="tel:<?php echo $this->bbytes_info('phone-bare'); ?>"><?php echo $this->bbytes_info('phone'); ?></a></td>
					</tr><tr>
						<th><i class="fa fa-envelope fa-fw"></i></th>
						<td><a href="mailto:<?php echo $this->bbytes_info('email-support'); ?>"><?php echo $this->bbytes_info('email-support'); ?></a></td>
					</tr><tr>
						<th><i class="fa fa-globe fa-fw"></i></th>
						<td><a href="<?php echo $this->bbytes_info('url'); ?>" target="_blank"><?php echo $this->bbytes_info('display-url'); ?></a></td>
					</tr><tr>
						<th><i class="fa fa-map-marker fa-fw"></i></th>
						<td>
							<strong><a target="_blank" href="<?php echo $this->bbytes_info('map-url'); ?>">Burlington Bytes</strong></a><br/>
							<?php echo $this->bbytes_info('address1'); ?><br/>
							<?php echo $this->bbytes_info('address2'); ?>
						</td>
					</tr>
				</table>
				<div class="bbytes-lf-hideme"><a href="#">&minus; hide this for now</a></div>
				<div class="bbytes-lf-what-is"><a target="_blank" href="<?php echo $this->bbytes_info('what-is-this-url'); ?>">What is this?</a></div>
			</div>
		</div>
		<?php
	}

	function wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'bbytes_news_dashboard_widget',     // Widget slug
			'Burlington Bytes News',            // Title
			array( $this, 'bbytes_news_dashboard_widget' )
		);
	}

	function bbytes_news_dashboard_widget() {
		// we need to include feed to be able to parse an RSS feed
		include_once( ABSPATH . WPINC . '/feed.php' );

		$feed_url  = $this->bbytes_info('rss-feed');
		$max_items = 4;
		$rss       = fetch_feed( $feed_url );
		if( !is_wp_error( $rss ) ) {
			$max_items = $rss->get_item_quantity( $max_items );
			$rss_items = $rss->get_items( 0, $max_items );
			if( $max_items > 0 ) {
				?>
				<div class="rss-widget">
					<ul>
					<?php
					$first_post = true;
					foreach($rss_items as $item) {
						$title     = $item->get_title();
						$permalink = $item->get_permalink();
						if($first_post) {
							$date        = $item->get_date( 'j F Y' );
							$description = $item->get_description();
							//preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $description, $img);
							//$first_img   = "";
							//if(isset($img[1])) $first_img = $img[1];
							$excerpt = strip_tags( $description );
							$excerpt = preg_replace( '/Read more\.\.\.$/','',$excerpt );
						}
						?>
						<li>
							<a class="rsswidget" href="<?php echo esc_attr( $permalink ); ?>" target="_blank"><?php echo esc_html( $title ); ?></a>
							<?php
							if( $first_post && $excerpt ) {
								?>
								<span class="rss-date"><?php echo esc_html( $date ); ?></span>
								<div class="rssSummary">
									<?php echo esc_html( $excerpt ); ?>
								</div>
								<?php
							}
							?>
						</li>
						<?php
						$first_post = false;
					}
					?>
					</ul>
				</div>
				<?php
			}
		}
	}

	function wp_footer() {
		$timeend = microtime(true);
		$timetotal = sprintf('%0d', 1000 * ($timeend - $this->_wp_start_time));
		$ts = date('YmdHis');
		echo "<!-- Monitoring by Burlington Bytes :bbyteswwwmon:$ts:$timetotal: -->";
	}
}


BBytes_Support_Plugin::init();
