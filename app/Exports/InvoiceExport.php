<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class InvoiceExport implements FromArray
{
	protected $invoices;

	/**
	 * @param array $invoices
	 */
	public function __construct(array $invoices)
	{
		$this->invoices = $invoices;
	}

	/**
	 * @return array
	 */
	public function array(): array
	{
		return $this->invoices;
	}
}
