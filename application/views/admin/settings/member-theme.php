<!DOCTYPE html>
<html lang="en">
<?php $this->load->view('admin/Layout/common_style'); ?>
<style>
    .mt-color{ display:flex; align-items:center; gap:10px; }
    .mt-color input[type=color]{ width:52px; height:42px; padding:2px; border-radius:8px; }
</style>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-toolbar-enabled="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?php $this->load->view('admin/Layout/admin_topbar'); ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?php $this->load->view('admin/Layout/admin_sidebar'); ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-xxl d-flex flex-stack">
                            <div class="page-title d-flex flex-column">
                                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Member Panel Theme</h1>
                                <ul class="breadcrumb fw-semibold fs-7 my-0 pt-1">
                                    <li class="breadcrumb-item text-muted">Settings</li>
                                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                    <li class="breadcrumb-item text-muted">Member Panel Theme</li>
                                </ul>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?php echo base_url('user/main'); ?>" target="_blank" class="btn btn-sm btn-light-primary"><i class="fa fa-eye"></i> Open Dashboard</a>
                                <button id="mtReset" class="btn btn-sm btn-light-danger"><i class="fa fa-rotate-left"></i> Reset to Default</button>
                            </div>
                        </div>
                    </div>

                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <div class="app-container container-xxl">
                            <form id="mtForm" class="card">
                                <div class="card-body">

                                    <h3 class="fw-bold mb-5">Dashboard Mode</h3>
                                    <div class="d-flex gap-6 mb-10">
                                        <?php $mode = $mt_mode ?: 'light'; foreach (array('light'=>'Light','dark'=>'Dark','auto'=>'Auto') as $val=>$lbl): ?>
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="mode" value="<?php echo $val; ?>" <?php echo $mode===$val?'checked':''; ?>>
                                            <span class="form-check-label fw-semibold"><?php echo $lbl; ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-muted fs-7 mb-6">Light / Dark / Auto for the member dashboard only — this does <b>not</b> affect the public landing page.</div>

                                    <div class="d-flex align-items-center gap-3 mb-8">
                                        <label class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                            <input class="form-check-input h-30px w-50px" type="checkbox" name="user_switch" value="1" <?php echo (isset($mt_user_switch) && $mt_user_switch !== '0') ? 'checked' : ''; ?>>
                                        </label>
                                        <span class="fw-semibold">Allow members to switch theme <span class="text-muted fs-7">(show the sun/moon toggle on the dashboard)</span></span>
                                    </div>

                                    <div class="separator my-8"></div>
                                    <h3 class="fw-bold mb-5">Brand Colors</h3>
                                    <div class="row g-6 mb-4">
                                        <?php
                                        $fields = array(
                                            'primary'=>'Primary','secondary'=>'Secondary','accent'=>'Accent',
                                            'highlight_primary'=>'Primary Highlight','highlight_accent'=>'Accent Highlight',
                                            'hover_highlight'=>'Hover Highlight','active_highlight'=>'Active Highlight',
                                            'gradient_start'=>'Gradient Start','gradient_end'=>'Gradient End',
                                            'success'=>'Success','warning'=>'Warning','danger'=>'Danger','info'=>'Info',
                                        );
                                        foreach ($fields as $key=>$label):
                                            $v = isset(${'mt_'.$key}) ? ${'mt_'.$key} : '';
                                            $hex = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : '#6D4AFF';
                                        ?>
                                        <div class="col-md-4 col-lg-3">
                                            <label class="form-label fw-semibold fs-7"><?php echo $label; ?></label>
                                            <div class="mt-color">
                                                <input type="color" class="form-control form-control-color mt-pick" value="<?php echo $hex; ?>">
                                                <input type="text" name="<?php echo $key; ?>" class="form-control form-control-solid mt-text" value="<?php echo html_escape($v); ?>" placeholder="#RRGGBB">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="text-muted fs-7 mt-4">
                                        Every highlighted dashboard element (active sidebar, buttons, progress rings, badges, links, charts)
                                        reads these via CSS variables — no per-component editing needed.
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Save Theme</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php $this->load->view('admin/Layout/admin_footer'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php $this->load->view('admin/Layout/common_script'); ?>
    <script>
    (function(){
        var base = "<?php echo base_url(); ?>";
        document.querySelectorAll('.mt-pick').forEach(function(p){
            var t = p.parentElement.querySelector('.mt-text');
            p.addEventListener('input', function(){ t.value = p.value; });
            t.addEventListener('input', function(){ if(/^#[0-9a-fA-F]{6}$/.test(t.value.trim())) p.value = t.value.trim(); });
        });
        function notify(ok,msg){ if(window.Swal){Swal.fire({icon:ok?'success':'error',title:msg,timer:1600,showConfirmButton:!ok});}else{alert(msg);} }
        document.getElementById('mtForm').addEventListener('submit', function(e){
            e.preventDefault();
            fetch(base+'member-theme-update',{method:'POST',body:new FormData(this),headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){return r.json();}).then(function(res){ notify(res.status,res.message); });
        });
        document.getElementById('mtReset').addEventListener('click', function(){
            var run=function(){ fetch(base+'member-theme-reset',{method:'POST',body:new FormData()})
                .then(function(r){return r.json();}).then(function(res){ notify(res.status,res.message); if(res.status) location.reload(); }); };
            if(window.Swal){ Swal.fire({icon:'warning',title:'Reset member theme to default?',showCancelButton:true}).then(function(r){ if(r.isConfirmed) run(); }); } else if(confirm('Reset?')) run();
        });
    })();
    </script>
</body>
</html>
