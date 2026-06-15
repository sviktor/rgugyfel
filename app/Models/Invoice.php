<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice - READ-ONLY view of the shared `invoices` table (owned by rgadmin,
 * written by its `invoices:sync`). The portal shows a logged-in customer their
 * own invoices and streams the PDF; it never writes this table.
 *
 * Files live on the PRIVATE `invoices` disk (the same INVOICES_STORAGE_PATH the
 * admin writes to); the PDF is served only through the gated portal download
 * route, after an ownership check against the authenticated customer.
 *
 * @property int         $id
 * @property int|null    $customers_id
 * @property string|null $invoice_number
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string|null $gross
 * @property int         $paid
 * @property int         $has_pdf
 * @property string|null $pdf_path
 */
class Invoice extends Model
{
	protected $table   = 'invoices';
	public $timestamps = false;

	/** The private filesystem disk invoice files live on (shared with rgadmin). */
	public const DISK = 'invoices';

	protected $casts = [
		'customers_id'   => 'integer',
		'issue_date'     => 'date',
		'due_date'       => 'date',
		'period_start'   => 'date',
		'period_end'     => 'date',
		'gross'          => 'decimal:2',
		'paid'           => 'integer',
		'has_pdf'        => 'integer',
		'has_xml'        => 'integer',
	];

	/**
	 * Scope: a customer's own, non-deleted, matched invoices (newest first).
	 *
	 * @example  Invoice::forCustomer(42)->get()
	 */
	public function scopeForCustomer(Builder $q, int $customerId): Builder
	{
		return $q->where('customers_id', $customerId)
			->where('deleted', 0)
			->orderByDesc('issue_date')
			->orderByDesc('id');
	}
}
