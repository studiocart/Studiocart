<?php
/**
 * Tax importer class file
 *
 * @version 2.3.0
 * @package NCS_Cart\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}

/**
 * Tax Rates importer - import tax rates and local tax rates into ncs-cart.
 *
 * @package     NCS_Cart\Admin\Importers
 * @version     2.3.0
 */
class NCS_Cart_Tax_Rate_Importer extends WP_Importer {

	/**
	 * The current file id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The current file url.
	 *
	 * @var string
	 */
	public $file_url;

	/**
	 * The current import page.
	 *
	 * @var string
	 */
	public $import_page;

	/**
	 * The current delimiter.
	 *
	 * @var string
	 */
	public $delimiter;

	/**
	 * Error message for import.
	 *
	 * @var string
	 */
	public $import_error_message;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->import_page = 'ncs-cart_tax_rate_csv';
		$this->delimiter   = empty( $_POST['delimiter'] ) ? ',' : (string) wc_clean( wp_unslash( $_POST['delimiter'] ) ); // WPCS: CSRF ok.
	}

	/**
	 * Registered callback function for the WordPress Importer.
	 *
	 * Manages the three separate stages of the CSV import process.
	 */
	public function dispatch() {

		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];

		switch ( $step ) {

			case 0:
				$this->greet();
				break;

			case 1:
				check_admin_referer( 'import-sc-upload' );

				if ( $this->handle_upload() ) {

					if ( $this->id ) {
						$file = get_attached_file( $this->id );
					} else {
						$file = ABSPATH . $this->file_url;
					}

					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

					$this->import( $file );
				} else {
					$this->import_error( $this->import_error_message );
				}
				break;
		}

		$this->footer();
	}

	/**
	 * Import is starting.
	 */
	private function import_start() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.gc_enableFound
		}
		sc_set_time_limit( 0 );
		@ob_flush();
		@flush();
		@ini_set( 'auto_detect_line_endings', '1' );
	}

	/**
	 * UTF-8 encode the data if `$enc` value isn't UTF-8.
	 *
	 * @param mixed  $data Data.
	 * @param string $enc Encoding.
	 * @return string
	 */
	public function format_data_from_csv( $data, $enc ) {
		return ( 'UTF-8' === $enc ) ? $data : utf8_encode( $data );
	}

	/**
	 * Import the file if it exists and is valid.
	 *
	 * @param mixed $file File.
	 */
	public function import( $file ) {
		if ( ! is_file( $file ) ) {
			$this->import_error( __( 'The file does not exist, please try again.', 'ncs-cart' ) );
		}

		$this->import_start();

		$loop   = 0;
		$handle = fopen( $file, 'r' );

		if ( false !== $handle ) {

			$header = fgetcsv( $handle, 0, $this->delimiter );

			if ( 7 === count( $header ) ) {

				$row = fgetcsv( $handle, 0, $this->delimiter );

				while ( false !== $row ) {

					list( $country, $state, $postcode, $city, $rate, $name, $priority ) = $row;

					$tax_rate = array(
						'tax_rate_country'  => $country,
						'tax_rate_state'    => $state,
						'tax_rate'          => $rate,
						'tax_rate_title'     => $name,
						'tax_rate_priority' => $priority,
						'tax_rate_postcode' => $postcode,
						'tax_rate_city' => $city,
					);
					
					NCS_Cart_Tax::insert_tax_rate( $tax_rate );
					$loop++;
					$row = fgetcsv( $handle, 0, $this->delimiter );
				}
			} else {
				$this->import_error( __( 'The CSV is invalid.', 'ncs-cart' ) );
			}

			fclose( $handle );
		}

		// Show Result.
		echo '<div class="updated settings-error"><p>';
		printf(
			/* translators: %s: tax rates count */
			esc_html__( 'Import complete - imported %s tax rates.', 'ncs-cart' ),
			'<strong>' . absint( $loop ) . '</strong>'
		);
		echo '</p></div>';

		$this->import_end();
	}

	/**
	 * Performs post-import cleanup of files and the cache.
	 */
	public function import_end() {
		echo '<p>' . esc_html__( 'All done!', 'ncs-cart' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=sc-admin#tax' ) ) . '">' . esc_html__( 'View tax rates', 'ncs-cart' ) . '</a></p>';

		do_action( 'import_end' );
	}

	/**
	 * Set the import error message.
	 *
	 * @param string $message Error message.
	 */
	protected function set_import_error_message( $message ) {
		$this->import_error_message = $message;
	}

	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for.
	 * displaying author import options.
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	public function handle_upload() {
		$file_url = isset( $_POST['file_url'] ) ? wc_clean( wp_unslash( $_POST['file_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified in WC_Tax_Rate_Importer::dispatch()

		if ( empty( $file_url ) ) {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				$this->set_import_error_message( $file['error'] );

				return false;
			}

			if ( ! sc_is_file_valid_csv( $file['file'], false ) ) {
				// Remove file if not valid.
				wp_delete_attachment( $file['id'], true );

				$this->set_import_error_message( __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'ncs-cart' ) );

				return false;
			}

			$this->id = absint( $file['id'] );
		} elseif (
			( 0 === stripos( realpath( ABSPATH . $file_url ), ABSPATH ) ) &&
			file_exists( ABSPATH . $file_url )
		) {
			if ( ! wc_is_file_valid_csv( ABSPATH . $file_url ) ) {
				$this->set_import_error_message( __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'ncs-cart' ) );

				return false;
			}

			$this->file_url = esc_attr( $file_url );
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Output header html.
	 */
	public function header() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Import tax rates', 'ncs-cart' ) . '</h1>';
	}

	/**
	 * Output footer html.
	 */
	public function footer() {
		echo '</div>';
	}

	/**
	 * Output information about the uploading process.
	 */
	public function greet() {

		echo '<div class="narrow">';
		echo '<p>' . esc_html__( 'Hi there! Upload a CSV file containing tax rates to import the contents into your settings. Choose a .csv file to upload, then click "Upload file and import".', 'ncs-cart' ) . '</p>';

		/* translators: 1: Link to tax rates sample file 2: Closing link. */
		echo '<p>' . sprintf( esc_html__( 'Your CSV needs to include columns in a specific order. %1$sClick here to download a sample%2$s.', 'ncs-cart' ), '<a target="_blanck" href="' .NCS_CART_BASE_URL. 'sample-data/sample_sc_tax_rates.csv">', '</a>' ) . '</p>';

		$action = 'admin.php?import=ncs-cart_tax_rate_csv&step=1';

		$bytes      = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size       = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?>
			<div class="error">
				<p><?php esc_html_e( 'Before you can upload your import file, you will need to fix the following error:', 'ncs-cart' ); ?></p>
				<p><strong><?php echo esc_html( $upload_dir['error'] ); ?></strong></p>
			</div>
		<?php else : ?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-sc-upload' ) ); ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="upload"><?php esc_html_e( 'Choose a file from your computer:', 'ncs-cart' ); ?></label>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo absint( $bytes ); ?>" />
								<small>
									<?php
									printf(
										/* translators: %s: maximum upload size */
										esc_html__( 'Maximum size: %s', 'ncs-cart' ),
										esc_attr( $size )
									);
									?>
								</small>
							</td>
						</tr>
						<tr>
							<th>
								<label for="file_url"><?php esc_html_e( 'OR enter path to file:', 'ncs-cart' ); ?></label>
							</th>
							<td>
								<?php echo ' ' . esc_html( ABSPATH ) . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
							</td>
						</tr>
						<tr>
							<th><label><?php esc_html_e( 'Delimiter', 'ncs-cart' ); ?></label><br/></th>
							<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'ncs-cart' ); ?>"><?php esc_html_e( 'Upload file and import', 'ncs-cart' ); ?></button>
				</p>
			</form>
			<?php
		endif;

		echo '</div>';
	}

	/**
	 * Show import error and quit.
	 *
	 * @param  string $message Error message.
	 */
	private function import_error( $message = '' ) {
		echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'ncs-cart' ) . '</strong><br />';
		if ( $message ) {
			echo esc_html( $message );
		}
		echo '</p>';
		$this->footer();
		die();
	}

	/**
	 * Added to http_request_timeout filter to force timeout at 60 seconds during import.
	 *
	 * @param  int $val Value.
	 * @return int 60
	 */
	public function bump_request_timeout( $val ) {
		return 60;
	}
}
