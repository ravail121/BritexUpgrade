@php
    $company = $data['order']->company ?? $invoice->customer->company;
    $invoice_account_summary_primary_color = $company->invoice_account_summary_primary_color;
    $invoice_account_summary_secondary_color = $company->invoice_account_summary_secondary_color;
    $invoice_background_text_color = $company->invoice_background_text_color;
    $invoice_normal_text_color = $company->invoice_normal_text_color;
    $invoice_solid_line_color = $company->invoice_solid_line_color;
@endphp
<style>
    h1, h2, h3, h4, h5, p {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }
    .account_info {
        background: {{ $invoice_account_summary_primary_color ?? '#4c00ac' }} !important;
    }
    .footer {
        background: {{ $invoice_account_summary_secondary_color ?? '#420590' }} !important;
    }

    .invoice td {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .invoice table .detail {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .head .bill_info h2 {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .head .bill_info h3 {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .info p a {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .billing_detail th, td {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .bill_detail table .detail {
        color: {{ $invoice_normal_text_color ?? '#373737' }} !important;
    }

    .account_info center {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .account_info th {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .account_info td {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .account_info p a {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .footer .center a {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .tables td a {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .tables table .right {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .feature table .right {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .taxes table .right {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .total table td {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .total table .right {
        color: {{ $invoice_background_text_color ?? '#FFFFFF' }} !important;
    }

    .account, .subscriber {
        border-bottom: 5px solid {{ $invoice_account_summary_primary_color ?? '#8b00da' }} !important;
    }
    .subscriber table td {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .subscriber table .nmbr {
        color: {{ $invoice_normal_text_color ?? '#000000' }} !important;
    }

    .sepratorline {
        background: {{ $invoice_solid_line_color ??  '#000000' }} !important;
    }

    .total {
        background: {{ $invoice_solid_line_color ?? '#000000' }} !important;
    }

    {{--.tables table td {--}}
    {{--    color: {{ $invoice_normal_text_color ?? '#000000' }} !important;--}}
    {{--}--}}
</style>