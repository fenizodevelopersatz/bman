<?php
/**
 * One export template for all six reports.
 *   $print = false → sent as .xls (Excel opens this HTML table natively)
 *   $print = true  → print-ready page; browser print dialog → Save as PDF
 * Expects: $rows, $cols (key => label), $label, $print
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo html_escape($label); ?> — BMAN</title>
    <style>
        body { font-family: system-ui, 'Segoe UI', Arial, sans-serif; color: #222; margin: 24px; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #777; font-size: 11px; margin-bottom: 18px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cfd6df; padding: 6px 9px; text-align: left; vertical-align: top; }
        th { background: #f2f5f9; font-weight: 600; white-space: nowrap; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:nth-child(even) td { background: #fafbfd; }
        .toolbar { margin-bottom: 18px; }
        .toolbar button { font: inherit; padding: 8px 18px; border-radius: 6px;
                          border: 1px solid #1b84ff; background: #1b84ff; color: #fff; cursor: pointer; }
        .empty { color: #888; padding: 24px; text-align: center; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>

<?php if (!empty($print)): ?>
    <div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
<?php endif; ?>

<h1><?php echo html_escape($label); ?></h1>
<div class="meta">
    BMAN Rank Management · generated <?php echo date('d M Y H:i'); ?> · <?php echo count($rows); ?> row(s)
</div>

<?php if (empty($rows)): ?>
    <div class="empty">No data for this report.</div>
<?php else: ?>
<table>
    <thead>
        <tr><?php foreach ($cols as $label_col): ?><th><?php echo html_escape($label_col); ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
        <?php foreach (array_keys($cols) as $k):
            $v = $r[$k] ?? '';
            $num = is_numeric($v) && preg_match('/volume|paid|amount|incentive|shortfall|percent|members|required|rewards/i', $k);
        ?>
            <td class="<?php echo $num ? 'num' : ''; ?>">
                <?php
                // Excel guesses types from raw text; leading apostrophe is not
                // needed here because every value is either a plain number or a
                // short label. Long decimals are rounded for readability.
                echo $num ? number_format((float)$v, 2) : html_escape((string)$v);
                ?>
            </td>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>
