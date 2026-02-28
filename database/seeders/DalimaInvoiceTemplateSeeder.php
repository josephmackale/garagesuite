<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceTemplate;

class DalimaInvoiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $garageId = 14;

        // Start by cloning your current template structure (simple example body).
        // You can paste a full HTML template later.
        $body = <<<HTML
<div style="font-family: Arial, sans-serif;">
  <h2 style="margin:0;">{{garage.name}}</h2>
  <div style="color:#555;">{{garage.phone}} {{garage.email}}</div>

  <hr style="margin:16px 0;"/>

  <h3 style="margin:0;">Invoice {{invoice.number}}</h3>
  <div>Issue: {{invoice.issue}}</div>
  <div>Status: {{invoice.status}}</div>

  <hr style="margin:16px 0;"/>

  <strong>Customer</strong><br/>
  {{customer.name}}<br/>
  {{customer.phone}}<br/>
  {{customer.email}}<br/>

  <hr style="margin:16px 0;"/>

  <strong>Totals</strong><br/>
  Subtotal: {{invoice.currency}} {{invoice.subtotal}}<br/>
  VAT: {{invoice.currency}} {{invoice.tax}}<br/>
  Total: {{invoice.currency}} {{invoice.total}}<br/>
</div>
HTML;

        InvoiceTemplate::updateOrCreate(
            ['garage_id' => $garageId, 'key' => 'default'],
            [
                'name'       => 'DALIMA Default Invoice',
                'is_active'  => true,
                'is_default' => true,
                'body_html'  => $body,
                'css'        => null,
            ]
        );
    }
}
