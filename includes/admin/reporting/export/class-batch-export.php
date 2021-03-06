<?php
/**
 * Batch Export Class
 *
 * This is the base class for all batch export methods. Each data export type (customers, payments, etc) extend this class
 *
 * @package     EDD
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDD_Export Class
 *
 * @since 2.4
 */
class EDD_Batch_Export extends EDD_Export {

	/**
	 * The file the data is stored in
	 *
	 * @since 2.4
	 */
	private $file;

	/**
	 * The name of the file the data is stored in
	 *
	 * @since 2.4
	 */
	public $filename;

	/**
	 * The file type, typically .csv
	 *
	 * @since 2.4
	 */
	public $filetype;

	/**
	 * The current step being processed
	 *
	 * @since 2.4
	 */
	public $step;

	/**
	 * Start date, Y-m-d H:i:s
	 *
	 * @since 2.4
	 */
	public $start;

	/**
	 * End date, Y-m-d H:i:s
	 *
	 * @since 2.4
	 */
	public $end;

	/**
	 * Status to export
	 *
	 * @since 2.4
	 */
	public $status;

	/**
	 * Download to export data for
	 *
	 * @since 2.4
	 */
	public $download = null;

	/**
	 * Download Price ID to export data for
	 *
	 * @since 2.4
	 */
	public $price_id = null;

	/**
	 * Get things started
	 *
	 * @param $_step int The step to process
	 * @since 2.4
	 */
	public function __construct( $_step = 1 ) {

		$upload_dir       = wp_upload_dir();
		$this->filetype   = '.csv';
		$this->filename   = 'edd-' . $this->export_type . $this->filetype;
		$this->file       = trailingslashit( $upload_dir['basedir'] ) . $this->filename;
		$this->step       = $_step;
		$this->done       = false;
	}

	/**
	 * Process a step
	 *
	 * @since 2.4
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'edd' ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
		}

		if( $this->step < 2 ) {

			// Make sure we start with a fresh file on step 1
			@unlink( $this->file );
			$this->print_csv_cols();
		}

		$rows = $this->print_csv_rows();

		if( $rows ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Output the CSV columns
	 *
	 * @access public
	 * @since 2.4
	 * @uses EDD_Export::get_csv_cols()
	 * @return string
	 */
	public function print_csv_cols() {
		
		$col_data = '';
		$cols = $this->get_csv_cols();
		$i = 1;
		foreach( $cols as $col_id => $column ) {
			$col_data .= '"' . addslashes( $column ) . '"';
			$col_data .= $i == count( $cols ) ? '' : ',';
			$i++;
		}
		$col_data .= "\r\n";
	
		$this->stash_step_data( $col_data );

		return $col_data;

	}

	/**
	 * Print the CSV rows for the current step
	 *
	 * @access public
	 * @since 2.4
	 * @return string|false
	 */
	public function print_csv_rows() {
		
		$row_data = '';
		$data     = $this->get_data();
		$cols     = $this->get_csv_cols();

		if( $data ) {

			// Output each row
			foreach ( $data as $row ) {
				$i = 1;
				foreach ( $row as $col_id => $column ) {
					// Make sure the column is valid
					if ( array_key_exists( $col_id, $cols ) ) {
						$row_data .= '"' . addslashes( $column ) . '"';
						$row_data .= $i == count( $cols ) ? '' : ',';
						$i++;
					}
				}
				$row_data .= "\r\n";
			}

			$this->stash_step_data( $row_data );

			return $row_data;
		}

		return false;
	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 2.4
	 * @return int
	 */
	public function get_percentage_complete() {
		return 100;
	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @since 2.4
	 * @return string
	 */
	private function get_file() {
		$file = @file_get_contents( $this->file );
		if( ! $file ) {
			@file_put_contents( $this->file, '' );
		}
		return $file;
	}

	/**
	 * Append data to export file
	 *
	 * @since 2.4
	 * @param $data string The data to add to the file 
	 * @return void
	 */
	private function stash_step_data( $data = '' ) {

		$file = $this->get_file();
		$file .= $data;
		@file_put_contents( $this->file, $file );

	}

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since 2.4
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		$file = $this->get_file();

		@unlink( $this->file );

		echo $file;

		edd_die();
	}
}
