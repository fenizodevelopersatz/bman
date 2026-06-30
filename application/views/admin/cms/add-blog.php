<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <style>
        .h-md-40{
            min-height:42%;
        }
        .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
        }
        .verified {
        border-color: green;
        }
    </style>

    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <!--  Header   -->
                <?php 
                //************************** SIDE BAR ADMIN PANEL */
                $this->load->view('admin/Layout/admin_topbar');
                //************************** SIDE BAR ADMIN PANEL */
                ?>


                    <!--begin::Wrapper-->
                    <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                        <?php $this->load->view('admin/Layout/admin_sidebar');?>

                            <!--begin::Main-->
                            <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                                <div class="d-flex flex-column flex-column-fluid">

                                    <!--begin::Toolbar-->
                                    <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                                <?php echo $title; ?>
                                                </h1>

                                                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        Settings                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Toolbar-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                        <div id="kt_app_content_container" class="app-container  container-xxl ">


                        <div class="card mb-5 mb-xl-10 ">
                        <div class="card-header border-0 cursor-pointer p-3" 
                        role="button" data-bs-toggle="collapse" 
                        data-bs-target="#kt_account_addagent_form_details" 
                        aria-expanded="true" aria-controls="kt_account_addagent_form_details">
                        <div class="card-title m-0">

                        <div class="me-3 d-flex justify-content-between text-center align-items-center gap-4">
                        <div class="d-flex flex-center w-60px h-60px rounded-3 bg-light-danger bg-opacity-90">
                        <i class="ki-duotone ki-abstract-26 text-danger fs-3x"><span class="path1"></span><span class="path2"></span></i>               
                        </div>
                        <h3 class="fw-bold m-0"><?php echo $card_title; ?></h3>
                        </div>

                        </div>
                        </div>


                        <div id="kt_account_addagent_form_details" class="collapse show">
                        <div class="card-body border-top p-9">
                        
                        <?php $action = base_url() . "admin/save_blog"; ?>
                            <?= form_open_multipart($action, [
                                'class' => 'form-validate',
                                'method' => 'post',
                                'autocomplete' => 'off',
                                'id' => 'kt_account_meta_details_form',
                                'data-kt-redirect-url' => base_url() . "admin/blog-list"
                            ]) ?>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />

                            <input type="hidden" name="blog_id" value="<?= isset($blog->id) ? $blog->id : '' ?>">

                            <!-- Blog Title -->
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Title <span class="text-danger">*</span></label>
                                <div class="col-lg-8 fv-row">
                                    <div class="input-group mb-5">
                                        <span class="input-group-text border-transparent" id="basic-addon1"><i class="fa fa-heading"></i></span>
                                        <input type="text" name="title"
                                            class="form-control form-control-lg form-control-solid"
                                            placeholder="Enter Blog Title"
                                            value="<?= isset($blog->title) ? $blog->title : '' ?>" required>
                                    </div>
                                </div>
                            </div>

                             <div class="row mb-6">
                                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Blog thumbnail <span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <div class="fv-row">
                                                    <div class="image-input image-input-outline" data-kt-image-input="true" 
                                                        style="background-image: url('<?= base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                        <?php
                                                        $imagePath = !empty($blog->image) ? base_url($blog->image) : base_url('assets/images/default.png');
                                                        ?>
                                                        <div class="image-input-wrapper w-325px h-225px"
                                                        style="background-image: url('<?= $imagePath ?>')"
                                                        alt="Category Image">
                                                        </div>
                                                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                                            data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change Brand Image">
                                                            <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                                            <input type="file" name="brand_image" accept=".png, .jpg, .jpeg">
                                                            <input type="hidden" name="brand_image_remover">
                                                        </label>
                                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                                            <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                        </span>
                                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                                                            <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                                        </span>
                                                    </div>
                                                    <div class="form-text">Upload a Blog image.</div>
                                                </div>
                                            </div>
                                </div>

                            <!-- Blog Category -->
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Category</label>
                                <div class="col-lg-8 fv-row">
                                    <select name="category" class="form-select form-select-lg form-select-solid" required>
                                        <option value="">Select Category</option>
                                        <?php if (!empty($category_info)): ?>
                                            <?php foreach ($category_info as $category_row): ?>
                                                <option value="<?= $category_row->id; ?>" <?= (isset($blog->category) && $blog->category == $category_row->id) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($category_row->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>


                            <!-- Blog Content -->
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Content <span class="text-danger">*</span></label>
                                <div class="col-lg-12 fv-row">
                                    <textarea class="form-control form-control-lg form-control-solid" name="content"
                                            id="kt_docs_ckeditor_classic" rows="8"
                                            placeholder="Enter blog content here"><?= isset($blog->content) ? $blog->content : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Blog Status -->
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Status</label>
                                <div class="col-lg-8 fv-row">
                                    <select name="status" class="form-select form-select-lg form-select-solid">
                                        <option value="1" <?= (isset($blog->status) && $blog->status == 1) ? 'selected' : '' ?>>Published</option>
                                        <option value="0" <?= (isset($blog->status) && $blog->status == 0) ? 'selected' : '' ?>>Draft</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-md-12">
                                <div class="form-group text-end">
                                    <button type="submit" id="kt_account_meta_details_submit" class="btn btn-lg btn-primary">Save Blog</button>
                                </div>
                            </div>

                            <?= form_close(); ?>
                    </div>
                    </div>

                    </div>
                    </div>




                    </div>
                    </div>
                                </div>

                                <!--begin::Footer-->
                                <?php $this->load->view('admin/Layout/admin_footer');?>

                            </div>
                    </div>
                    <!--end::Wrapper-->

            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-duotone ki-arrow-up">
              <span class="path1"></span>
              <span class="path2"></span>
              </i>
        </div>

        <?php $this->load->view('admin/Layout/common_script');?>

            <script src="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/widgets.bundle.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/widgets.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/apps/chat/chat.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
            <script src="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.js"></script>
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
            <link href="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
            <script src="<?php echo base_url();?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

            <script>
                const base_url = '<?php echo base_url();?>';
            </script>
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/add-blog.js?ver=2.9"></script>


            <script src="https://cdn.ckeditor.com/4.14.0/full-all/ckeditor.js"></script>
            <script>
            var editor = CKEDITOR.replace('kt_docs_ckeditor_classic', {
            height: 300,
            extraPlugins: 'mentions',
            allowedContent: true, 
            allowedContent: true,
            removeFormatAttributes: '',
            stylesSet: [
            { name: 'Bold Red', element: 'span', styles: { 'font-weight': 'bold', 'color': 'red' } },
            { name: 'Custom Style', element: 'div', styles: { 'background-color': '#f0f0f0', 'padding': '10px' } }
            ]
            });
            </script>
            
            <script>
            </script>
    </body>

    </html>