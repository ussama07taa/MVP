<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Mensuel - <?php echo e($monthName); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.5; }
        .page { padding: 30px 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0f172a; padding-bottom: 15px; margin-bottom: 25px; }
        .company-name { font-size: 22px; font-weight: 900; color: #0f172a; }
        .company-sub { font-size: 9px; color: #64748b; margin-top: 2px; }
        .report-title { text-align: right; }
        .report-title h2 { font-size: 16px; color: #0f172a; font-weight: 900; }
        .report-title p { font-size: 9px; color: #64748b; }
        
        .section { margin-bottom: 22px; }
        .section-title { font-size: 13px; font-weight: 900; color: #0f172a; border-left: 4px solid #3b82f6; padding-left: 10px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        
        .cards { display: table; width: 100%; margin-bottom: 15px; }
        .card { display: table-cell; width: 25%; padding: 10px; text-align: center; border: 1px solid #e2e8f0; border-radius: 8px; }
        .card-value { font-size: 18px; font-weight: 900; color: #0f172a; }
        .card-value.green { color: #059669; }
        .card-value.red { color: #dc2626; }
        .card-value.blue { color: #2563eb; }
        .card-label { font-size: 8px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-top: 2px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { text-align: left; padding: 8px 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .badge-critical { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-low { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-ok { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        
        .two-col { display: table; width: 100%; }
        .two-col .col { display: table-cell; width: 48%; vertical-align: top; }
        .two-col .col-spacer { display: table-cell; width: 4%; }
        
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 9px; color: #94a3b8; }
        .page-break { page-break-before: always; }
        
        .summary-table td { padding: 6px 10px; }
        .summary-table .label { color: #64748b; font-weight: 600; }
        .summary-table .value { font-weight: 900; color: #0f172a; text-align: right; }
        .summary-table .value.positive { color: #059669; }
        .summary-table .value.negative { color: #dc2626; }
        .summary-table tr.total { border-top: 2px solid #0f172a; }
        .summary-table tr.total td { padding-top: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div>
                <div class="company-name"><?php echo e($settings['company_name'] ?? 'Mon Entreprise'); ?></div>
                <div class="company-sub">
                    <?php if(isset($settings['company_ice'])): ?> ICE: <?php echo e($settings['company_ice']); ?> <?php endif; ?>
                    <?php if(isset($settings['company_rc'])): ?> &nbsp;|&nbsp; RC: <?php echo e($settings['company_rc']); ?> <?php endif; ?>
                </div>
                <?php if(isset($settings['company_phone'])): ?>
                    <div class="company-sub">Tél: <?php echo e($settings['company_phone']); ?></div>
                <?php endif; ?>
            </div>
            <div class="report-title">
                <h2>Rapport Mensuel</h2>
                <p><?php echo e($monthName); ?></p>
                <p>Généré le <?php echo e($generatedAt); ?></p>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="section">
            <div class="section-title">Résumé Financier</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Chiffre d'Affaires Brut</td>
                    <td class="value"><?php echo e(number_format($financial['gross_revenue'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td class="label">Retours Clients</td>
                    <td class="value negative">- <?php echo e(number_format($financial['customer_returns'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td class="label">Chiffre d'Affaires Net</td>
                    <td class="value"><?php echo e(number_format($financial['revenue'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td class="label">Coût des Marchandises (COGS)</td>
                    <td class="value negative">- <?php echo e(number_format($financial['cogs'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td class="label">Marge Brute</td>
                    <td class="value <?php echo e($financial['gross_margin'] >= 0 ? 'positive' : 'negative'); ?>"><?php echo e(number_format($financial['gross_margin'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td class="label">Charges Opérationnelles (OPEX)</td>
                    <td class="value negative">- <?php echo e(number_format($financial['opex'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr class="total">
                    <td class="label font-black">Bénéfice Net</td>
                    <td class="value font-black <?php echo e($financial['net_profit'] >= 0 ? 'positive' : 'negative'); ?>"><?php echo e(number_format($financial['net_profit'], 2, ',', ' ')); ?> DH</td>
                </tr>
            </table>
        </div>

        <!-- KPIs -->
        <div class="section">
            <div class="section-title">Indicateurs Clés</div>
            <table>
                <tr>
                    <th>Indicateur</th>
                    <th class="text-right">Valeur</th>
                </tr>
                <tr>
                    <td>Nombre de Commandes</td>
                    <td class="text-right font-bold"><?php echo e($financial['order_count']); ?></td>
                </tr>
                <tr>
                    <td>Panier Moyen</td>
                    <td class="text-right font-bold"><?php echo e(number_format($financial['avg_order_value'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td>Marge Nette</td>
                    <td class="text-right font-bold"><?php echo e($financial['margin_percentage']); ?>%</td>
                </tr>
                <tr>
                    <td>Encaissé</td>
                    <td class="text-right font-bold"><?php echo e(number_format($financial['cash_collected'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td>Impayés</td>
                    <td class="text-right font-bold" style="color: #dc2626;"><?php echo e(number_format($financial['unpaid_revenue'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td>Achats du Mois</td>
                    <td class="text-right font-bold"><?php echo e(number_format($financial['total_purchases'], 2, ',', ' ')); ?> DH</td>
                </tr>
                <tr>
                    <td>Trésorerie Nette</td>
                    <td class="text-right font-bold" style="color: <?php echo e($financial['net_cash_flow'] >= 0 ? '#059669' : '#dc2626'); ?>;"><?php echo e(number_format($financial['net_cash_flow'], 2, ',', ' ')); ?> DH</td>
                </tr>
            </table>
        </div>

        <!-- Top Clients + Expenses side by side -->
        <div class="two-col">
            <div class="col">
                <div class="section">
                    <div class="section-title">Top 5 Clients</div>
                    <?php if($topClients->count() > 0): ?>
                    <table>
                        <tr>
                            <th>Client</th>
                            <th class="text-center">Cmd</th>
                            <th class="text-right">CA</th>
                        </tr>
                        <?php $__currentLoopData = $topClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="font-bold"><?php echo e($client['name']); ?></td>
                            <td class="text-center"><?php echo e($client['order_count']); ?></td>
                            <td class="text-right font-bold"><?php echo e(number_format($client['total_revenue'], 2, ',', ' ')); ?> DH</td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </table>
                    <?php else: ?>
                    <p style="color: #94a3b8; font-style: italic;">Aucune commande ce mois.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-spacer"></div>
            <div class="col">
                <div class="section">
                    <div class="section-title">Charges par Catégorie</div>
                    <?php if($expensesByCategory->count() > 0): ?>
                    <table>
                        <tr>
                            <th>Catégorie</th>
                            <th class="text-right">Montant</th>
                        </tr>
                        <?php $__currentLoopData = $expensesByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e(ucfirst($exp->category)); ?></td>
                            <td class="text-right font-bold"><?php echo e(number_format($exp->total, 2, ',', ' ')); ?> DH</td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </table>
                    <?php else: ?>
                    <p style="color: #94a3b8; font-style: italic;">Aucune charge ce mois.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Page 2 -->
        <div class="page-break"></div>

        <!-- Top Services -->
        <div class="section">
            <div class="section-title">Services les plus demandés</div>
            <?php if($topServices->count() > 0): ?>
            <table>
                <tr>
                    <th>Service</th>
                    <th class="text-center">Nombre</th>
                </tr>
                <?php $__currentLoopData = $topServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="font-bold"><?php echo e($svc->label); ?></td>
                    <td class="text-center"><?php echo e($svc->count); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
            <?php else: ?>
            <p style="color: #94a3b8; font-style: italic;">Aucun service ce mois.</p>
            <?php endif; ?>
        </div>

        <!-- Worker Performance -->
        <div class="section">
            <div class="section-title">Performance des Employés</div>
            <?php if($workerPerformance->count() > 0): ?>
            <table>
                <tr>
                    <th>Employé</th>
                    <th class="text-center">Services Complétés</th>
                </tr>
                <?php $__currentLoopData = $workerPerformance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $worker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="font-bold"><?php echo e($worker->worker_name); ?></td>
                    <td class="text-center font-bold"><?php echo e($worker->services_done); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
            <?php else: ?>
            <p style="color: #94a3b8; font-style: italic;">Aucune donnée ce mois.</p>
            <?php endif; ?>
        </div>

        <!-- Stock Status - Panels -->
        <div class="section">
            <div class="section-title">État du Stock — Panneaux</div>
            <?php if($stockPanels->count() > 0): ?>
            <table>
                <tr>
                    <th>Panneau</th>
                    <th class="text-center">Qté</th>
                    <th class="text-right">Valeur</th>
                    <th class="text-center">État</th>
                </tr>
                <?php $__currentLoopData = $stockPanels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $panel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($panel['name']); ?></td>
                    <td class="text-center font-bold"><?php echo e($panel['quantity']); ?></td>
                    <td class="text-right"><?php echo e(number_format($panel['value'], 2, ',', ' ')); ?> DH</td>
                    <td class="text-center">
                        <span class="badge badge-<?php echo e($panel['status']); ?>">
                            <?php echo e($panel['status'] === 'critical' ? 'Critique' : ($panel['status'] === 'low' ? 'Bas' : 'OK')); ?>

                        </span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
            <?php else: ?>
            <p style="color: #94a3b8; font-style: italic;">Aucun panneau en stock.</p>
            <?php endif; ?>
        </div>

        <!-- Stock Status - Cantos -->
        <div class="section">
            <div class="section-title">État du Stock — Bandchant</div>
            <?php if($stockCantos->count() > 0): ?>
            <table>
                <tr>
                    <th>Bandchant</th>
                    <th class="text-center">Mètres</th>
                    <th class="text-right">Valeur</th>
                    <th class="text-center">État</th>
                </tr>
                <?php $__currentLoopData = $stockCantos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $canto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($canto['name']); ?></td>
                    <td class="text-center font-bold"><?php echo e(number_format($canto['quantity'], 1)); ?> m</td>
                    <td class="text-right"><?php echo e(number_format($canto['value'], 2, ',', ' ')); ?> DH</td>
                    <td class="text-center">
                        <span class="badge badge-<?php echo e($canto['status']); ?>">
                            <?php echo e($canto['status'] === 'critical' ? 'Critique' : ($canto['status'] === 'low' ? 'Bas' : 'OK')); ?>

                        </span>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
            <?php else: ?>
            <p style="color: #94a3b8; font-style: italic;">Aucun bandchant en stock.</p>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><?php echo e($settings['company_name'] ?? 'Mon Entreprise'); ?> — Rapport généré automatiquement le <?php echo e($generatedAt); ?></p>
            <p style="margin-top: 4px;">Ce document est confidentiel et réservé à un usage interne.</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\Taaouati\Downloads\MVP\resources\views/reports/monthly.blade.php ENDPATH**/ ?>