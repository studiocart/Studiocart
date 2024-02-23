<div id="rate-table-container">
	<h2>Custom Tax Rates</h2>
	<hr style="border-color: black;border-width: 1;"/>
	<div id="rates-search" class="txt_end">
		<input type="text" name="serach" class="ncs-tax-rates-search-input" id="">
	</div>
	<table class="widefat nsc_tax_rate_table">
		<thead>
			<tr>
				<th><a href="https://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank"><?php _e( 'Country code', 'ncs-cart' ); ?></a></th>
				<th><?php _e( 'State code', 'ncs-cart' ); ?></th>
				<th><?php _e( 'Postcode ', 'ncs-cart' ); ?></th>
				<th><?php _e( 'City', 'ncs-cart' ); ?></th>
				<th><?php _e( 'Rate %', 'ncs-cart' ); ?></th>
				<th><?php _e( 'Tax Title', 'ncs-cart' ); ?></th>
				<th><?php _e( 'Priority', 'ncs-cart' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
			<th colspan="7">
					<a href="javascript:void(0);" class="button sc_button add_new_tax_rate"><?php _e( 'Insert row', 'ncs-cart' ); ?></a>
					<a href="javascript:void(0);" class="button sc_button remove_selected_tax_rates"><?php _e( 'Remove selected row(s)', 'ncs-cart' ); ?></a>
					<input type="button" name="save" value="<?php _e( 'Save Tax Rates', 'ncs-cart' ); ?>" class="button button-primary save_table_rate">
					<a href="#" download="ncs_tax_rates.csv" class="button export"><?php _e( 'Export CSV', 'ncs-cart' ); ?></a>
					<a href="<?php echo admin_url( 'admin.php?import=ncs-cart_tax_rate_csv' ); ?>" class="button import"><?php _e( 'Import CSV', 'ncs-cart' ); ?></a>
				</th>
			</tr>
		</tfoot>
		<tbody id="rates">
			<tr>
				<th colspan="7" style="text-align: center;"><?php esc_html_e( 'Loading&hellip;', 'ncs-cart' ); ?></th>
			</tr>
		</tbody>
	</table>
</div>
<style>
	table.nsc_tax_rate_table{
		width: 100%;
	}
	table.nsc_tax_rate_table td, table.nsc_tax_rate_table th {
		display: table-cell !important;
	}
	table.nsc_tax_rate_table th{
		white-space: nowrap;
		padding: 10px;
	}
	table.nsc_tax_rate_table tfoot th{
		text-align: center;
	}
	table.nsc_tax_rate_table td{
		padding: 0;
		border-right: 1px solid #dfdfdf;
		border-bottom: 1px solid #dfdfdf;
		border-top: 0;
		background: #fff;
		cursor: default;
	}
	
	table.nsc_tax_rate_table td input[type=number], table.nsc_tax_rate_table td input[type=text] {
		width: 100% !important;
		min-width: 100px;
		padding: 8px 10px;
		margin: 0;
		border: 0;
		outline: 0;
		background: transparent none;
	}
	
	table.nsc_tax_rate_table tr:last-child td{
		border-bottom: 0;
	}

	table.nsc_tax_rate_table tr.current td{
		background-color: #fefbcc;
	}
	table.nsc_tax_rate_table .export, table.nsc_tax_rate_table .import{
		float: right;
		margin-right: 0;
		margin-left: 5px;
	}
	table.nsc_tax_rate_table .sc_button{
		float: left;
		margin-right: 5px;
	}
	table.nsc_tax_rate_table .save_table_rate{
		margin: 0 auto;
	}
	.txt_end{
		text-align: end;
	}
</style>
<script type="text/html" id="tmpl-ncs-tax-table-row">
	<tr class="tax-tips" data-tip="<?php printf( esc_attr__( 'Tax rate ID: %s', 'ncs-cart' ), '{{ data.tax_rate_id }}' ); ?>" data-id="{{ data.tax_rate_id }}">
		<td class="tax-country">
			<input type="text" value="{{ data.tax_rate_country }}" placeholder="*" name="tax_rate_country[{{ data.tax_rate_id }}]" class="wc_input_country_iso" data-attribute="tax_rate_country" style="text-transform:uppercase" />
		</td>

		<td class="tax-state">
			<input type="text" value="{{ data.tax_rate_state }}" placeholder="*" name="tax_rate_state[{{ data.tax_rate_id }}]" data-attribute="tax_rate_state" />
		</td>

		<td class="tax-postcode">
			<input type="text" value="{{ data.tax_rate_postcode }}" placeholder="*" data-name="tax_rate_postcode[{{ data.tax_rate_id }}]" data-attribute="tax_rate_postcode" />
		</td>

		<td class="tax-city">
			<input type="text" value="{{ data.tax_rate_city }}" placeholder="*" data-name="tax_rate_city[{{ data.tax_rate_id }}]" data-attribute="tax_rate_city" />
		</td>

		<td class="tax-rate">
			<input type="text" value="{{ data.tax_rate }}" placeholder="0" name="tax_rate[{{ data.tax_rate_id }}]" data-attribute="tax_rate" />
		</td>

		<td class="tax-title">
			<input type="text" value="{{ data.tax_rate_title }}" name="tax_rate_title[{{ data.tax_rate_id }}]" data-attribute="tax_rate_title" />
		</td>

		<td class="priority">
			<input type="number" step="1" min="1" value="{{ data.tax_rate_priority }}" name="tax_rate_priority[{{ data.tax_rate_id }}]" data-attribute="tax_rate_priority" />
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-ncs-tax-table-row-empty">
	<tr>
		<th colspan="9" style="text-align:center"><?php esc_html_e( 'No matching tax rates found.', 'ncs-cart' ); ?></th>
	</tr>
</script>

<script type="text/html" id="tmpl-ncs-tax-table-pagination">
	<div class="tablenav">
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: %s: number */
				printf(
					__( '%s items', 'ncs-cart' ), // %s will be a number eventually, but must be a string for now.
					'{{ data.qty_rates }}'
				);
				?>
			</span>
			<span class="pagination-links">

				<a class="tablenav-pages-navspan" data-goto="1">
					<span class="screen-reader-text"><?php esc_html_e( 'First page', 'ncs-cart' ); ?></span>
					<span aria-hidden="true">&laquo;</span>
				</a>
				<a class="tablenav-pages-navspan" data-goto="<# print( Math.max( 1, parseInt( data.current_page, 10 ) - 1 ) ) #>">
					<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'ncs-cart' ); ?></span>
					<span aria-hidden="true">&lsaquo;</span>
				</a>

				<span class="paging-input">
					<label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Current page', 'ncs-cart' ); ?></label>
					<?php
						/* translators: 1: current page 2: total pages */
						printf(
							esc_html_x( '%1$s of %2$s', 'Pagination', 'ncs-cart' ),
							'<input class="current-page" id="current-page-selector" type="text" name="paged" value="{{ data.current_page }}" size="<# print( data.qty_pages.toString().length ) #>" aria-describedby="table-paging">',
							'<span class="total-pages">{{ data.qty_pages }}</span>'
						);
					?>
				</span>

				<a class="tablenav-pages-navspan" data-goto="<# print( Math.min( data.qty_pages, parseInt( data.current_page, 10 ) + 1 ) ) #>">
					<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'ncs-cart' ); ?></span>
					<span aria-hidden="true">&rsaquo;</span>
				</a>
				<a class="tablenav-pages-navspan" data-goto="{{ data.qty_pages }}">
					<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'ncs-cart' ); ?></span>
					<span aria-hidden="true">&raquo;</span>
				</a>

			</span>
		</div>
	</div>
</script>
