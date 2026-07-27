<?php
/**
 * Printable rank certificate. Standalone page — deliberately NOT wrapped in the
 * admin chrome so the browser print dialog produces a clean artefact.
 *
 * Rendered as print-ready HTML rather than a generated PDF binary: this project
 * vendors no PDF library, and "Print → Save as PDF" produces the same file with
 * no new dependency. If you later archive PDFs, store the path in
 * rank_certificates.certificate_pdf — the column is already there.
 *
 * Expects: $c (row from Rankreward_model::certificate)
 */
$name  = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
if ($name === '') $name = $c['username'] ?: ('Member #' . $c['user_id']);
$colour = $c['badge_color'] ?: '#c8a24a';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo html_escape($c['certificate_no']); ?> — <?php echo html_escape($c['rank_name']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f5; font-family: Georgia, 'Times New Roman', serif; color: #1a1a1a; }
        .sheet {
            width: 297mm; height: 210mm; margin: 24px auto; background: #fff; position: relative;
            padding: 18mm; box-shadow: 0 8px 32px rgba(0,0,0,.14);
        }
        .frame { position: absolute; inset: 10mm; border: 2px solid <?php echo html_escape($colour); ?>; }
        .frame:after { content: ''; position: absolute; inset: 4mm; border: 1px solid <?php echo html_escape($colour); ?>66; }
        .inner { position: relative; height: 100%; display: flex; flex-direction: column;
                 align-items: center; justify-content: center; text-align: center; padding: 0 18mm; }
        .brand { letter-spacing: .5em; font-size: 13px; text-transform: uppercase; color: #666; margin-bottom: 6mm; }
        .kicker { font-size: 15px; letter-spacing: .28em; text-transform: uppercase; color: #888; }
        .name { font-size: 44px; margin: 6mm 0 4mm; font-weight: 700; }
        .rule { width: 90mm; height: 1px; background: <?php echo html_escape($colour); ?>; margin: 0 auto 5mm; }
        .body { font-size: 15px; color: #444; line-height: 1.7; max-width: 190mm; }
        .rank { font-size: 40px; font-weight: 700; letter-spacing: .06em;
                color: <?php echo html_escape($colour); ?>; margin: 5mm 0; text-transform: uppercase; }
        .badge-img { height: 26mm; margin-bottom: 4mm; }
        .dot { width: 18mm; height: 18mm; border-radius: 50%; margin: 0 auto 4mm;
               background: <?php echo html_escape($colour); ?>; opacity: .9; }
        .feet { position: absolute; bottom: 14mm; left: 18mm; right: 18mm;
                display: flex; justify-content: space-between; align-items: flex-end; }
        .foot { font-family: system-ui, sans-serif; font-size: 11px; color: #777; text-align: left; }
        .foot b { color: #333; display: block; font-size: 12px; letter-spacing: .06em; }
        .sig { text-align: right; font-family: system-ui, sans-serif; font-size: 11px; color: #777; }
        .sig-line { width: 55mm; border-top: 1px solid #999; margin-bottom: 4px; }
        .bar { position: absolute; top: 0; left: 0; right: 0; height: 6mm; background: <?php echo html_escape($colour); ?>; }
        .toolbar { text-align: center; margin: 18px 0; font-family: system-ui, sans-serif; }
        .toolbar button, .toolbar a {
            font: inherit; font-size: 13px; padding: 8px 18px; margin: 0 4px; border-radius: 6px;
            border: 1px solid #c3ccd8; background: #fff; color: #222; cursor: pointer; text-decoration: none;
        }
        .toolbar button { background: #1b84ff; border-color: #1b84ff; color: #fff; }
        @page { size: A4 landscape; margin: 0; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; width: 100%; height: 100vh; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <a href="<?php echo base_url('admin/staking/rank-certificates'); ?>">Back to certificates</a>
</div>

<div class="sheet">
    <div class="bar"></div>
    <div class="frame"></div>
    <div class="inner">
        <div class="brand">BMAN</div>
        <?php if (!empty($c['badge_image'])): ?>
            <img class="badge-img" src="<?php echo base_url($c['badge_image']); ?>" alt="">
        <?php else: ?>
            <div class="dot"></div>
        <?php endif; ?>

        <div class="kicker">Certificate of Achievement</div>
        <div class="name"><?php echo html_escape($name); ?></div>
        <div class="rule"></div>
        <div class="body">has permanently achieved the rank of</div>
        <div class="rank"><?php echo html_escape($c['rank_name']); ?></div>
        <div class="body">
            in recognition of sustained achievement and rank volume earned.<br>
            This rank is permanent and is never withdrawn.
        </div>
    </div>

    <div class="feet">
        <div class="foot">
            <b><?php echo html_escape($c['certificate_no']); ?></b>
            Issued <?php echo date('d F Y', strtotime($c['generated_date'])); ?>
        </div>
        <div class="sig">
            <div class="sig-line"></div>
            Authorised signatory
        </div>
    </div>
</div>

</body>
</html>
