


<div class="col-lg-12">

<?php if($this->session->flashdata('success')): ?>

<div class="alert alert-pro alert-success">    
<div class="alert-text">        
<h6>Success</h6>       
<p><?php echo $this->session->flashdata('success'); ?></p>    
</div>
</div>

<?php endif; ?>

<?php if($this->session->flashdata('danger')): ?>

<div class="alert alert-pro alert-danger">    
<div class="alert-text">        
<h6>Error</h6>       
<p><?php echo $this->session->flashdata('danger'); ?></p>    
</div>
</div>

<?php endif; ?>

<?php if(validation_errors() != null): ?>

<?php echo '<div class="alert alert-pro alert-warning icons-alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
<i class="icofont icofont-close-line-circled"></i>
</button>
<p><strong>Alert! &nbsp;&nbsp;</strong>'.validation_errors().'</p></div>'; ?>

<?php endif; ?>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Scoped to .alert-pro (this partial's own flash-message markup) —
        // NOT the bare .alert class, which pages elsewhere reuse for their
        // own persistent status/result banners (e.g. admin_wallet.php's
        // #aw-result). A bare .alert selector here was silently deleting
        // those from the DOM ~4.5s after load, long before a user had a
        // chance to trigger the action that fills them in.
        const alerts = document.querySelectorAll('.alert-pro');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = "all 0.5s ease";
                alert.style.opacity = "0";
                alert.style.height = "0";
                setTimeout(() => alert.remove(), 500);
            }, 4000);
        });
    });
</script>
