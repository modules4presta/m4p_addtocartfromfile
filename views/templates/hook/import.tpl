{**
 * m4p_addtocartfromfile
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}

<div class="m4p-atcff">
	<button type="button" class="btn btn-secondary m4p-atcff__open" data-m4p-atcff-open>
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 14 14" aria-hidden="true"><g stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 12.5a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1H9L12.5 4v8.5Z"/><path d="m9 6.5-2-2-2 2M7 4.5V10"/></g></svg>
		{l s='Fill the cart from a file' d='Modules.M4paddtocartfromfile.Shop'}
	</button>

	<div class="m4p-atcff__overlay" data-m4p-atcff-modal hidden>
		<div class="m4p-atcff__dialog" role="dialog" aria-modal="true" aria-labelledby="m4p-atcff-title">
			<div class="m4p-atcff__head">
				<h3 class="m4p-atcff__title" id="m4p-atcff-title">{l s='Fill the cart from a file' d='Modules.M4paddtocartfromfile.Shop'}</h3>
				<button type="button" class="m4p-atcff__close" data-m4p-atcff-close aria-label="{l s='Close' d='Modules.M4paddtocartfromfile.Shop'}">&times;</button>
			</div>

			<div class="m4p-atcff__body">
				<div class="m4p-atcff__drop" data-m4p-atcff-drop>
					<label class="m4p-atcff__label" for="m4p-atcff-file">
						<svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" fill="none" stroke="currentColor" viewBox="0 0 14 14" aria-hidden="true"><g stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 12.5a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1H9L12.5 4v8.5Z"/><path d="m9 6.5-2-2-2 2M7 4.5V10"/></g></svg>
						<span class="m4p-atcff__labeltext" data-m4p-atcff-filename>
							<strong>{l s='Click here to pick a file' d='Modules.M4paddtocartfromfile.Shop'}</strong>
							<span>{l s='or drag one onto this field.' d='Modules.M4paddtocartfromfile.Shop'}</span>
						</span>
					</label>
					<input type="file" id="m4p-atcff-file" class="m4p-atcff__input" accept="{$m4p_atcff_accept|escape:'html':'UTF-8'}" data-m4p-atcff-input>
				</div>

				<p class="m4p-atcff__hint">
					{l s='One row per product: reference, EAN-13, quantity. The first row is skipped as a header.' d='Modules.M4paddtocartfromfile.Shop'}
					{if $m4p_atcff_csv && $m4p_atcff_xls}
						{l s='CSV and spreadsheet files are accepted.' d='Modules.M4paddtocartfromfile.Shop'}
					{elseif $m4p_atcff_xls}
						{l s='Spreadsheet files are accepted.' d='Modules.M4paddtocartfromfile.Shop'}
					{else}
						{l s='CSV files are accepted.' d='Modules.M4paddtocartfromfile.Shop'}
					{/if}
					<a href="{$m4p_atcff_example|escape:'html':'UTF-8'}" download>{l s='Download an example file' d='Modules.M4paddtocartfromfile.Shop'}</a>
				</p>

				<p class="m4p-atcff__alert" data-m4p-atcff-alert hidden></p>

				<ul class="m4p-atcff__summary" data-m4p-atcff-summary hidden>
					<li>{l s='Rows in the file:' d='Modules.M4paddtocartfromfile.Shop'} <strong data-m4p-atcff-count-total>0</strong></li>
					<li>{l s='Added to the cart:' d='Modules.M4paddtocartfromfile.Shop'} <strong data-m4p-atcff-count-added>0</strong></li>
					<li>{l s='Reduced to available stock:' d='Modules.M4paddtocartfromfile.Shop'} <strong data-m4p-atcff-count-reduced>0</strong></li>
					<li>{l s='Not added:' d='Modules.M4paddtocartfromfile.Shop'} <strong data-m4p-atcff-count-failed>0</strong></li>
				</ul>

				<div class="m4p-atcff__tablewrap" data-m4p-atcff-tablewrap hidden>
					<table class="m4p-atcff__table">
						<thead>
							<tr>
								<th>{l s='Reference / EAN' d='Modules.M4paddtocartfromfile.Shop'}</th>
								<th>{l s='In the file' d='Modules.M4paddtocartfromfile.Shop'}</th>
								<th>{l s='In the cart' d='Modules.M4paddtocartfromfile.Shop'}</th>
								<th>{l s='Result' d='Modules.M4paddtocartfromfile.Shop'}</th>
							</tr>
						</thead>
						<tbody data-m4p-atcff-rows></tbody>
					</table>
				</div>
			</div>

			<div class="m4p-atcff__foot">
				<button type="button" class="btn btn-primary" data-m4p-atcff-submit>{l s='Import' d='Modules.M4paddtocartfromfile.Shop'}</button>
				<button type="button" class="btn btn-secondary" data-m4p-atcff-close>{l s='Cancel' d='Modules.M4paddtocartfromfile.Shop'}</button>
				<button type="button" class="btn btn-secondary" data-m4p-atcff-again hidden>{l s='Import another file' d='Modules.M4paddtocartfromfile.Shop'}</button>
				<button type="button" class="btn btn-secondary" data-m4p-atcff-report hidden>{l s='Download the report' d='Modules.M4paddtocartfromfile.Shop'}</button>
				<button type="button" class="btn btn-primary" data-m4p-atcff-done hidden>{l s='Back to the cart' d='Modules.M4paddtocartfromfile.Shop'}</button>
			</div>
		</div>
	</div>
</div>
