<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #0f172a; 
            margin: 0; 
            padding: 0; 
            font-size: 11px;
            background-color: #fff;
        }
        .header { padding: 40px 50px 20px; border-bottom: 2px solid #0f172a; position: relative; }
        .company-info { display: inline-block; width: 60%; vertical-align: top; }
        .invoice-type { display: inline-block; width: 38%; text-align: right; vertical-align: top; }
        
        .logo { width: 80px; height: auto; margin-right: 15px; float: left; }
        .company-details { float: left; }
        .company-name { font-size: 24px; font-weight: 900; letter-spacing: -1px; margin: 0; text-transform: uppercase; }
        .company-sub { font-size: 10px; font-weight: bold; color: #1e293b; margin-top: 5px; line-height: 1.4; }
        
        .status-badge { color: #f97316; font-weight: 900; font-size: 8px; text-transform: uppercase; margin-bottom: 5px; }
        .type-label { font-size: 32px; font-weight: 900; color: #cbd5e1; margin: 0; line-height: 1; }
        .ref-label { font-size: 18px; font-weight: 900; margin: 5px 0; }
        .date-label { font-size: 10px; font-weight: bold; color: #64748b; }

        .container { padding: 30px 50px; }
        
        .client-section { margin-bottom: 30px; }
        .section-title { font-size: 9px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; }
        .client-box { 
            display: inline-block; 
            width: 50%;
            border: 2px solid #0f172a; 
            border-radius: 15px; 
            padding: 20px;
            vertical-align: top;
        }
        .client-name { font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 5px; }
        .client-phone { font-size: 12px; font-weight: bold; color: #475569; }
        
        .meta-box { 
            display: inline-block; 
            width: 45%; 
            text-align: right; 
            vertical-align: top;
            padding-top: 10px;
        }
        .meta-row { margin-bottom: 10px; }
        .meta-label { font-size: 9px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-right: 15px; }
        .meta-value { font-size: 12px; font-weight: 900; color: #0f172a; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; color: #94a3b8; font-size: 9px; font-weight: 900; text-transform: uppercase; }
        td { padding: 15px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .col-num { width: 30px; color: #cbd5e1; font-weight: bold; font-size: 10px; }
        .col-desc { font-weight: 900; color: #0f172a; font-size: 11px; }
        .col-qty { font-weight: 900; text-align: center; }
        .col-price { font-weight: bold; color: #64748b; text-align: right; }
        .col-total { font-weight: 900; color: #0f172a; text-align: right; }

        .summary-section { margin-top: 40px; position: relative; }
        .modalites-box { 
            width: 50%; 
            background: #f8fafc; 
            border-radius: 20px; 
            padding: 20px; 
            display: inline-block;
            vertical-align: top;
        }
        .modalites-title { font-size: 8px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; }
        .modalites-text { font-size: 9px; font-weight: bold; color: #475569; line-height: 1.5; }
        
        .totals-box { 
            width: 40%; 
            float: right;
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); /* Shadow simulation for dompdf */
            border: 1px solid #f1f5f9;
        }
        .total-row { clear: both; padding: 5px 0; }
        .total-label { float: left; font-size: 9px; font-weight: 900; color: #cbd5e1; text-transform: uppercase; }
        .total-value { float: right; font-size: 12px; font-weight: 900; color: #0f172a; }
        
        .total-main-label { font-size: 10px; font-weight: 900; color: #94a3b8; }
        .total-main-value { font-size: 24px; font-weight: 900; color: #0f172a; }
        
        .avance-label { color: #10b981; }
        .avance-value { color: #10b981; }
        
        .reste-box { margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; }
        .reste-label { color: #f59e0b; font-size: 10px; font-weight: 900; }
        .reste-value { color: #f59e0b; font-size: 18px; font-weight: 900; }

        .footer { 
            position: absolute; 
            bottom: 40px; 
            width: 100%; 
            text-align: center; 
        }
        .footer-ice { font-size: 8px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; }
        .footer-thanks { font-size: 10px; font-weight: 900; color: #64748b; font-style: italic; }
        
        .clear { clear: both; }
    </style>
</head>
<body>
    @php
        $rb = 0;
        if (isset($remaining_balance)) {
            $rb = $remaining_balance;
        } elseif (method_exists($invoice, 'remainingBalance')) {
            $rb = $invoice->remainingBalance();
        } else {
            $rb = $invoice->remaining_balance ?? 0;
        }

        // Grouping Logic
        $groupedItems = [];
        foreach ($invoice->items as $item) {
            $desc = $item->description ?? $item->label ?? '';
            
            // Clean name
            $baseName = preg_replace('/(Fourniture:|Collage Chant:)\s*/i', '', $desc);
            $baseName = preg_replace('/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/i', 'Pose de Chant (Fourniture Client)', $baseName);
            $baseName = preg_replace('/Sel3a\s*(?:d|y|n)?\s*Client/i', 'Fourniture Client', $baseName);
            $baseName = trim($baseName);

            $qty = (float) $item->quantity;
            $key = $baseName . '_' . $qty;

            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'description' => $baseName,
                    'quantity' => $qty,
                    'unit' => $item->unit ?? 'unité',
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ];
            } else {
                $groupedItems[$key]['total'] += (float) $item->total;
                $groupedItems[$key]['unit_price'] = $groupedItems[$key]['total'] / $qty;
            }
        }
    @endphp

    <div class="header">
        <div class="company-info">
            <div class="logo">
                <!-- If logo exists, we can use it. Otherwise use a placeholder or text -->
                @if(isset($settings['company_logo']) && $settings['company_logo'])
                    <img src="{{ public_path('storage/' . $settings['company_logo']) }}" style="width: 80px;">
                @else
                    <div style="width: 80px; height: 80px; background: #0f172a; border-radius: 15px;"></div>
                @endif
            </div>
            <div class="company-details">
                <h1 class="company-name">{{ $settings['company_name'] ?? 'TAAOUATI' }}</h1>
                <div class="company-sub">
                    {{ $settings['company_phone'] ?? '+212 666-035411 / +212 610-182585' }}<br>
                    {{ $settings['company_address'] ?? 'TANGER JAMA3 ALRB3IN' }}
                </div>
            </div>
        </div>
        <div class="invoice-type">
            <div class="status-badge">
                {{ $rb <= 0.01 ? 'PAYÉ' : 'PAIEMENT EN ATTENTE' }}
            </div>
            <h2 class="type-label">{{ strtoupper($invoice->type === 'quote' ? 'DEVIS' : 'FACTURE') }}</h2>
            <div class="ref-label">{{ str_starts_with($invoice->invoice_number, '#') ? '' : '#' }}{{ $invoice->invoice_number }}</div>
            <div class="date-label">{{ $invoice->issue_date->format('d \m\a\i Y \à H:i') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="container">
        <div class="client-section">
            <div class="section-title">Client / Destinataire</div>
            <div class="client-box">
                <div class="client-name">{{ $invoice->client->name }}</div>
                <div class="client-phone">{{ $invoice->client->phone }}</div>
            </div>
            
            <div class="meta-box">
                <div class="meta-row">
                    <span class="meta-label">Réf.</span>
                    <span class="meta-value">{{ str_starts_with($invoice->invoice_number, '#') ? '' : '#' }}{{ $invoice->invoice_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Émis le</span>
                    <span class="meta-value">{{ $invoice->issue_date->format('d \m\a\i Y') }}</span>
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th>Désignation</th>
                    <th class="col-qty" width="60">Qté</th>
                    <th class="col-price" width="100">P. Unitaire</th>
                    <th class="col-total" width="120">Total TTC</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNum = 1; @endphp
                @foreach($groupedItems as $item)
                <tr>
                    <td class="col-num">{{ str_pad($rowNum++, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="col-desc">{{ strtoupper($item['description']) }}</td>
                    <td class="col-qty">x{{ (float)$item['quantity'] }}</td>
                    <td class="col-price">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="col-total">{{ number_format($item['total'], 2) }} DH</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="modalites-box">
                <div class="modalites-title">Modalités</div>
                <div class="modalites-text">
                    Paiement par Espèces.<br>
                    Toute facture impayée pourra donner lieu à des pénalités.
                </div>
            </div>

            <div class="totals-box">
                <div class="total-row">
                    <span class="total-label">Sous-Total HT</span>
                    <span class="total-value">{{ number_format($invoice->subtotal, 2) }} DH</span>
                </div>
                
                <div class="total-row" style="margin-top: 10px;">
                    <span class="total-main-label">TOTAL À PAYER</span>
                    <span class="total-main-value">{{ number_format($invoice->total, 2) }} DH</span>
                </div>
                
                <div class="total-row">
                    <span class="total-label avance-label">AVANCE</span>
                    <span class="total-value avance-value">- {{ number_format($invoice->amount_paid, 2) }} DH</span>
                </div>
                
                <div class="total-row reste-box">
                    <span class="reste-label">RESTE DÛ</span>
                    <span class="reste-value">{{ number_format($rb, 2) }} DH</span>
                </div>
                <div class="clear"></div>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-ice">ICE: {{ $settings['company_ice'] ?? '122-2333' }}</div>
        <div class="footer-thanks">MERCI POUR VOTRE CONFIANCE !</div>
    </div>
</body>
</html>
