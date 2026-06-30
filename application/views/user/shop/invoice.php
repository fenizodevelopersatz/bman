<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order->id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; color: #4a5568; }
        .invoice-box { padding: 20px; border: 1px solid #eee; }
        .invoice-header h4 { font-weight: bold; }
        .print-btn { margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container mt-4">



    <div class="d-flex justify-content-between align-items-center print-btn mb-3">
        <img src="<?= base_url('assets/images/' . ($settings['logo'] ?? 'logo-whites.png')) ?>" alt="Company Logo" style="height: 60px;background:#313b50;padding:5px;" >
        <button class="btn btn-dark btn-sm" onclick="window.print()">🖨️ Print Invoice</button>
    </div>

    <div class="invoice-box bg-white p-4 rounded shadow-sm">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="mb-1"><?= $settings['site-name'] ?? 'Your Company' ?></h5>
                <p class="mb-0"><?= $settings['address'] ?? '' ?></p>
                <p>Email: <?= $settings['email'] ?? '' ?> | Phone: <?= $settings['contact_number'] ?? '' ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h6>Invoice #: <?= $order->id ?></h6>
                <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order->created_at)) ?></p>
                <p><strong>Payment:</strong> <?= ucfirst(str_replace('_', ' ', $order->payment_method)) ?> (<?= ucfirst($order->payment_status) ?>)</p>
            </div>
        </div>


    <div class="  p-4 rounded shadow-sm">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Billing To:</h6>
                <p>
                    <?= $shipping->firstname . ' ' . $shipping->lastname ?><br>
                    <?= $shipping->address ?>,<br>
                    <?= $shipping->city ?> <?= $shipping->postalcode ?><br>
                    <?= $shipping->state ?>, <?= $shipping->country ?>
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order->created_at)) ?></p>
            </div>
        </div>

        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th class="text-center">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $subtotal = 0; foreach ($order_items as $item): 
                    $total = $item->price * $item->quantity;
                    $subtotal += $total;
                ?>
                <tr>
                    <td><?= $item->name ?></td>
                    <td class="text-center"><?php echo currency_info()->currency_symbol; ?><?= number_format($item->price, 2) ?></td>
                    <td class="text-center"><?= $item->quantity ?></td>
                    <td class="text-end"><?php echo currency_info()->currency_symbol; ?><?= number_format($total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal</td>
                    <td class="text-end"><?php echo currency_info()->currency_symbol; ?><?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">VAT (20%)</td>
                    <td class="text-end"><?php echo currency_info()->currency_symbol; ?><?= number_format($subtotal * 0.2, 2) ?></td>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end"><?php echo currency_info()->currency_symbol; ?><?= number_format($order->total_amount, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</body>
</html>
