


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

<?php echo '<div class="alert alert-warning icons-alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
<i class="icofont icofont-close-line-circled"></i>
</button>
<p><strong>Alert! &nbsp;&nbsp;</strong>'.validation_errors().'</p></div>'; ?>

<?php endif; ?>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const alerts = document.querySelectorAll('.alert');
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
