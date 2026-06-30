<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<link href="https://cdn.jsdelivr.net/npm/bootstrap-fileinput/css/fileinput.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css" />

<style>
    .h-md-40 {
        min-height: 42%;
    }

    .verify-symbol {
        color: green;
        font-weight: bold;
        margin-left: 5px;
    }

    .verified {
        border-color: green;
    }

    .ck.ck-editor {
        position: relative;
        border: 1px solid lightgray;
        border-radius: 12px;
    }
</style>

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
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

                <?php $this->load->view('admin/Layout/admin_sidebar'); ?>

                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        <!--begin::Toolbar-->
                        <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">
                            <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">

                                    <h1
                                        class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        <?php echo $title; ?>
                                    </h1>

                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?php echo base_url(); ?>" class="text-muted text-hover-primary">
                                                Network Management
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                        </li>
                                        <li class="breadcrumb-item text-muted">
                                            <?php echo $title; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Toolbar-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                            <div id="kt_app_content_container" class="app-container  container-xxl ">



                                <?= form_open_multipart($action, [
                                    'class' => 'form-validate form d-flex flex-column flex-lg-row fv-plugins-bootstrap5 fv-plugins-framework',
                                    'method' => 'post',
                                    'autocomplete' => 'off',
                                    'id' => 'kt_account_meta_details_form',
                                    'data-kt-redirect-url' => base_url() . "admin/product-list",
                                    'enctype' => 'multipart/form-data'
                                ]) ?>

                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                    value="<?= $this->security->get_csrf_hash(); ?>" />

                                <?php if (!empty($product_info->id)): ?>
                                    <input type="hidden" name="product_id" value="<?= $product_info->id ?>">
                                <?php endif; ?>

                                <!-- SIDE ROW START -->
                                <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-400px mb-7 me-lg-10">

                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <!--begin::Card title-->
                                            <div class="card-title">
                                                <h2>Product Thumbnail</h2>
                                            </div>
                                            <!--end::Card title-->
                                        </div>
                                        <!--end::Card header-->

                                        <!--begin::Card body-->
                                        <div class="card-body text-center pt-0">

                                            <div class="fv-row">
                                                <div class="image-input image-input-outline" data-kt-image-input="true"
                                                    style="background-image: url('<?php echo base_url(); ?>/assets/admin/media/svg/avatars/blank.svg')">
                                                    <div class="image-input-wrapper w-125px h-125px"
                                                        style="background-image: url('<?php echo base_url() . "assets/images/" . $product_info->product_image; ?>')"
                                                        alt="Logo">
                                                    </div>
                                                    <label
                                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                                        title="Change avatar">
                                                        <i class="ki-duotone ki-pencil fs-7"><span
                                                                class="path1"></span><span class="path2"></span></i>
                                                        <input type="file" name="product_log" accept=".png, .jpg, .jpeg"
                                                            required>
                                                        <input type="hidden" name="product_log_remover">
                                                    </label>
                                                    <span
                                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                                        title="Product Image">
                                                        <i class="ki-duotone ki-cross fs-2"><span
                                                                class="path1"></span><span class="path2"></span></i>
                                                    </span>
                                                    <span
                                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                                        title="Remove image">
                                                        <i class="ki-duotone ki-cross fs-2"><span
                                                                class="path1"></span><span class="path2"></span></i>
                                                    </span>
                                                </div>
                                                <div class="form-text"><?php echo lang('image_validation_label'); ?>
                                                </div>
                                            </div>

                                            <!--begin::Description-->
                                            <div class="text-muted fs-7">Set the category thumbnail image. Only *.png,
                                                *.jpg and *.jpeg image files are accepted</div>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>




                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <!--begin::Card title-->
                                            <div class="card-title">
                                                <h2>Product Images</h2>
                                            </div>
                                            <!--end::Card title-->
                                        </div>
                                        <!--end::Card header-->


                                        <!--begin::Card body-->
                                        <div class="card-body text-center pt-0">

                                            <div class="mb-10 fv-row fv-plugins-icon-container">
                                                <label class="form-label">Product Images</label>
                                                <input id="productImages" name="product_image[]" type="file" multiple>
                                                <div class="text-muted fs-7">upload product images.</div>
                                            </div>

                                            <?php if (!empty($product_images)): ?>
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="col-form-label fw-semibold fs-6">Existing Images</label>
                                                    <?php foreach ($product_images as $img): ?>
                                                        <div class="position-relative border rounded p-2" style="width: 100%;">
                                                            <img src="<?= base_url('assets/images/' . $img->image) ?>"
                                                                alt="Product Image" class="img-fluid rounded">
                                                            <a class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 px-1 py-0 delete_user"
                                                                data-payment="<?= $img->id ?>"
                                                                data-delete_user-url="<?= base_url('admin/delete-product-image/' . $img->id) ?>">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                            </img>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>


                                </div>
                                <!-- SIDE ROW END -->


                                <!-- MAIN ROW START  -->
                                <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10"
                                    data-select2-id="select2-data-138-oqua">


                                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10"
                                        data-select2-id="select2-data-138-oqua">

                                        <div class="card card-flush py-4">
                                            <!--begin::Card header-->
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Product General Info</h2>
                                                </div>
                                            </div>
                                            <!--end::Card header-->
                                            <div class="card-body pt-0">


                                                <div class="mb-10 fv-row fv-plugins-icon-container">

                                                    <label class="required form-label">Product Name</label>

                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa fa-box" aria-hidden="true"></i></span>
                                                        <input type="text" name="name"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Product Name" required
                                                            value="<?= set_value('name', $product_info->name ?? '') ?>">
                                                    </div>

                                                    <div class="text-muted fs-7">A product name is required and
                                                        recommended to be unique.</div>

                                                    <div
                                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                                    </div>
                                                </div>

                                                <div class="mb-10 fv-row fv-plugins-icon-container">


                                                    <label class="required form-label">Product Description</label>
                                                    <textarea name="description" id="description"
                                                        class="form-control form-control-solid mb-5">
                                                                    <?= set_value('description', $product_info->description ?? '') ?>
                                                                </textarea>

                                                    <div class="text-muted fs-7">Set a description to the category for
                                                        better visibility.</div>
                                                    <div
                                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                                    </div>
                                                </div>


                                            </div>
                                        </div>



                                        <div class="card card-flush py-4">
                                            <!--begin::Card header-->
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Pricing</h2>
                                                </div>
                                            </div>
                                            <!--end::Card header-->
                                            <div class="card-body pt-0">


                                                <div class="mb-10 fv-row fv-plugins-icon-container">

                                                    <label class="required form-label">Price (
                                                        <?php echo $currency_info->currency_symbol; ?>)</label>

                                                    <div class="input-group mb-5">
                                                        <span
                                                            class="input-group-text border-transparent"><?php echo $currency_info->currency_symbol; ?></span>
                                                        <input type="number" step="0.01" id="actual_price" name="price"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Price" required
                                                            value="<?= set_value('price', $product_info->price ?? '') ?>">
                                                    </div>

                                                    <div class="text-muted fs-7">Set the product price.</div>
                                                    <div
                                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                                    </div>
                                                </div>



                                                <!-- Offer Status -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Offer Status</label>
                                                    <select name="offer_status" class="form-select me-3"
                                                        id="offer_status">
                                                        <option value="0" <?= ($product_info->offer_status == 0) ? 'selected' : '' ?>>Inactive</option>
                                                        <option value="1" <?= ($product_info->offer_status == 1) ? 'selected' : '' ?>>Active</option>
                                                    </select>
                                                    <div class="text-muted fs-7">Set the product offer status.</div>
                                                </div>

                                                <!-- Discount Percentage Range -->
                                                <div class="mb-10 fv-row"
                                                    id="kt_ecommerce_add_product_discount_percentage">
                                                    <label class="form-label">Set Discount Percentage</label>
                                                    <div class="d-flex flex-column text-center mb-5">
                                                        <div
                                                            class="d-flex align-items-start justify-content-center mb-7">
                                                            <span class="fw-bold fs-3x"
                                                                id="kt_ecommerce_add_product_discount_label"><? echo $product_info->offer_percentage ? $product_info->offer_percentage : '0' ?></span>
                                                            <span class="fw-bold fs-4 mt-1 ms-2">%</span>
                                                        </div>
                                                        <div id="kt_ecommerce_add_product_discount_slider"
                                                            class="noUi-sm"></div>
                                                    </div>
                                                    <div class="text-muted fs-7">Set a percentage discount to be applied
                                                        on this product.</div>
                                                </div>

                                                <input type="hidden" id="offer_percentage" name="offer_percentage"
                                                    value="<?= $product_info->offer_percentage ? $product_info->offer_percentage : '0' ?>">

                                                <!-- Offer Price -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Offer Price</label>
                                                    <div class="input-group mb-5">
                                                        <span
                                                            class="input-group-text border-transparent"><?= $currency_info->currency_symbol; ?></span>
                                                        <input type="number" name="offer_price" id="offer_price"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Offer Price"
                                                            value="<?= set_value('offer_price', $product_info->offer_price ?? '') ?>"
                                                            readonly>
                                                    </div>
                                                    <div class="text-muted fs-7">Set the product offer price.</div>
                                                </div>


                                            </div>
                                        </div>


                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Inventory</h2>
                                                </div>
                                            </div>

                                            <div class="card-body pt-0">

                                                <!-- Brand -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Brand <span
                                                            class="text-danger">*</span></label>

                                                    <select name="product_brand" class="form-select form-select-solid"
                                                        data-control="select2" id="product_brand"
                                                        data-placeholder="Select Brand">
                                                        <option value=""></option>
                                                        <?php foreach ($brands as $brand): ?>
                                                            <option value="<?= $brand->id ?>"
                                                                <?= ($product_info->brand_id == $brand->id) ? 'selected' : '' ?>>
                                                                <?= $brand->name ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <div class="text-muted fs-7">select product brand.</div>
                                                </div>


                                                <!-- Category -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container ">
                                                    <label class="form-label">Category <span
                                                            class="text-danger">*</span></label>
                                                    <select name="product_category"
                                                        class="form-select form-select-solid" id="product_category"
                                                        data-control="select2" data-placeholder="Select Category">
                                                        <option value=""></option>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat->id ?>"
                                                                <?= ($product_info->category_id == $cat->id) ? 'selected' : '' ?>>
                                                                <?= $cat->name ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- Product Type -->
                                                <div class="row mb-6 d-none">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Product
                                                        Type</label>
                                                    <div class="col-lg-8">
                                                        <select name="product_type" class="form-select me-3"
                                                            id="product_type">
                                                            <option value="physical"
                                                                <?= ($product_info->product_type == 'physical') ? 'selected' : '' ?>>Physical</option>
                                                            <option value="digital"
                                                                <?= ($product_info->product_type == 'digital') ? 'selected' : '' ?>>Digital</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- SKU -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">SKU <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa fa-barcode" aria-hidden="true"></i></span>
                                                        <input type="text" name="sku"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter SKU" required
                                                            value="<?= set_value('sku', $product_info->sku ?? '') ?>">
                                                    </div>

                                                    <div class="text-muted fs-7">Enter the product SKU.</div>
                                                </div>

                                                <!-- Stock -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Stock <span
                                                            class="text-danger">*</span></label>

                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text border-transparent"><i
                                                                class="fa fa-warehouse" aria-hidden="true"></i></span>
                                                        <input type="number" name="stock"
                                                            class="form-control form-control-lg form-control-solid"
                                                            placeholder="Enter Stock Count"
                                                            value="<?= set_value('stock', $product_info->stock ?? '') ?>"
                                                            required>
                                                    </div>

                                                    <div class="text-muted fs-7">Enter the product Stock.</div>
                                                </div>

                                                <!-- Product Sizes -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Product Sizes </label>

                                                    <?php
                                                    $product_sizes = explode(',', $product_info->product_size ?? '');
                                                    ?>
                                                    <select name="product_sizes[]" data-control="select2"
                                                        class="form-select" multiple>
                                                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                                            <option value="<?= $size ?>" <?= in_array($size, $product_sizes) ? 'selected' : '' ?>>
                                                                <?= $size ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <div class="text-muted fs-7">Enter the product Sizes.</div>
                                                </div>

                                                <!-- Warranty -->
                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Warranty </label>

                                                    <input type="text" name="product_warranty" class="form-control"
                                                        placeholder="e.g. 1 Year, 6 Months"
                                                        value="<?= set_value('product_warranty', $product_info->product_warranty ?? '') ?>">

                                                    <div class="text-muted fs-7">Enter the product Warranty.</div>
                                                </div>

                                            </div>

                                        </div>



                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Variations</h2>
                                                </div>
                                            </div>

                                            <div class="card-body pt-0">

                                                <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">Additional Info </label>
                                                    <div id="additional-info-container">
                                                        <?php if (!empty($product_meta)): ?>
                                                            <?php foreach ($product_meta as $meta): ?>
                                                                <div class="row mb-2">
                                                                    <div class="col-5">
                                                                        <input type="text" name="info_key[]"
                                                                            class="form-control" placeholder="Key"
                                                                            value="<?= htmlspecialchars($meta->meta_key) ?>">
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <input type="text" name="info_value[]"
                                                                            class="form-control" placeholder="Value"
                                                                            value="<?= htmlspecialchars($meta->meta_value) ?>">
                                                                    </div>
                                                                    <div class="col-2">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-light-danger remove-row">✕</button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="row mb-2">
                                                                <div class="col-5"><input type="text" name="info_key[]"
                                                                        class="form-control" placeholder="Key"></div>
                                                                <div class="col-5"><input type="text" name="info_value[]"
                                                                        class="form-control" placeholder="Value"></div>
                                                                <div class="col-2"><button type="button"
                                                                        class="btn btn-sm btn-light-danger remove-row">✕</button>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-light-primary mt-2"
                                                        onclick="addInfoRow()">+ Add More</button>
                                                </div>

                                            </div>

                                        </div>

                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Shipping</h2>
                                                </div>
                                            </div>

                                            <div class="card-body pt-0">


                                                <div class="mb-10 fv-row">
                                                    <label
                                                        class="form-check form-switch form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="is_physical" id="is_physical" value="1"
                                                            <?= ($product_info->is_physical ?? false) ? 'checked' : '' ?>>
                                                        <span class="form-check-label">This is a physical product</span>
                                                    </label>
                                                    <div class="form-text">Set if the product is a physical or digital
                                                        item. Physical products may require shipping.</div>
                                                </div>

                                                <div id="shipping-fields"
                                                    style="<?= ($product_info->is_physical ?? false) ? '' : 'display:none;' ?>">
                                                    <div class="mb-5 fv-row">
                                                        <label class="form-label">Weight (kg)</label>
                                                        <input type="text" name="weight" class="form-control"
                                                            value="<?= $product_info->weight ?? '' ?>"
                                                            placeholder="e.g. 0.5">
                                                    </div>

                                                    <div class="mb-5 fv-row">
                                                        <label class="form-label">Dimensions (L × W × H in cm)</label>
                                                        <div class="row">
                                                            <div class="col"><input type="number" name="length"
                                                                    class="form-control"
                                                                    value="<?= $product_info->length ?? '' ?>"
                                                                    placeholder="Length"></div>
                                                            <div class="col"><input type="number" name="width"
                                                                    class="form-control"
                                                                    value="<?= $product_info->width ?? '' ?>"
                                                                    placeholder="Width"></div>
                                                            <div class="col"><input type="number" name="height"
                                                                    class="form-control"
                                                                    value="<?= $product_info->height ?? '' ?>"
                                                                    placeholder="Height"></div>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>

                                        </div>



                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>Product BV</h2>
                                                </div>
                                            </div>

                                            <div class="card-body pt-0">


                                                <!-- Warranty -->
                                                <!-- <div class="mb-10 fv-row fv-plugins-icon-container">
                                                    <label class="form-label">BV
                                                        (<?php echo $currency_info->currency_symbol; ?>) </label>

                                                    <input type="number" step="0.01" name="commission"
                                                        class="form-control form-control-lg form-control-solid"
                                                        placeholder="Enter Commission Per Unit"
                                                        value="<?= set_value('commission', $product_info->commission ?? '0') ?>"
                                                        required>

                                                    <div class="text-muted fs-7">Enter the product BV.</div>
                                                </div> -->
                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Product
                                                        PV</label>
                                                    <div class="col-lg-8 fv-row">
                                                        <input type="number" step="0.0001" name="pv"
                                                            value="<?= $product_info->pv ?? 0 ?>"
                                                            class="form-control form-control-lg form-control-solid">
                                                        <small class="text-muted">Personal Volume points credited to
                                                            buyer.</small>
                                                    </div>
                                                </div>

                                                <div class="row mb-6">
                                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Product
                                                        BV</label>
                                                    <div class="col-lg-8 fv-row">
                                                        <input type="number" step="0.0001" name="bv"
                                                            value="<?= $product_info->bv ?? 0 ?>"
                                                            class="form-control form-control-lg form-control-solid">
                                                        <small class="text-muted">Business Volume points propagated to
                                                            uplines (left/right).</small>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>


                                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                                            <button type="submit" class="btn btn-primary"
                                                id="kt_account_meta_details_submit">Save Product</button>
                                        </div>

                                    </div>

                                    <?= form_close(); ?>

                                </div>

                                <!--begin::Footer-->
                                <?php $this->load->view('admin/Layout/admin_footer'); ?>

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

                <?php $this->load->view('admin/Layout/common_script'); ?>
                <script
                    src="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js"></script>
                <script src="<?php echo base_url(); ?>/assets/admin/js/widgets.bundle.js"></script>
                <script src="<?php echo base_url(); ?>/assets/admin/js/custom/widgets.js"></script>
                <script src="<?php echo base_url(); ?>/assets/admin/js/custom/apps/chat/chat.js"></script>
                <script
                    src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
                <script
                    src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
                <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
                <script
                    src="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
                <link href="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css"
                    rel="stylesheet" type="text/css" />
                <script
                    src="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

                <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap-fileinput/js/fileinput.min.js"></script>


                <script>
                    function addInfoRow() {
                        let html = `
            <div class="row mb-2">
            <div class="col-5"><input type="text" name="info_key[]" class="form-control" placeholder="Key"></div>
            <div class="col-5"><input type="text" name="info_value[]" class="form-control" placeholder="Value"></div>
            <div class="col-2"><button type="button" class="btn btn-sm btn-light-danger remove-row">✕</button></div>
            </div>`;
                        document.getElementById("additional-info-container").insertAdjacentHTML("beforeend", html);
                    }
                    document.addEventListener('click', function (e) {
                        if (e.target && e.target.classList.contains('remove-row')) {
                            e.target.closest('.row').remove();
                        }
                    });

                    $("#productImages").fileinput({
                        showUpload: false, // no auto upload
                        allowedFileExtensions: ["jpg", "jpeg", "png"],
                        maxFileSize: 3000, // in KB
                        maxFileCount: 10,
                        browseOnZoneClick: true,
                        theme: "fa"
                    });

                </script>

                <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const discountSlider = document.getElementById('kt_ecommerce_add_product_discount_slider');
                        const discountLabel = document.getElementById('kt_ecommerce_add_product_discount_label');
                        const offerPriceInput = document.getElementById('offer_price');
                        const actualPrice = parseFloat(document.getElementById('actual_price').value || 0);

                        const offerPercentageInput = document.getElementById('offer_percentage');
                        const initialPercentage = parseFloat(offerPercentageInput.value || 0);


                        if (discountSlider && typeof noUiSlider !== 'undefined') {
                            noUiSlider.create(discountSlider, {
                                start: 0,
                                connect: [true, false],
                                step: 1,
                                range: {
                                    'min': 0,
                                    'max': 100
                                }
                            });

                            discountSlider.noUiSlider.set(initialPercentage);

                            discountSlider.noUiSlider.on('update', function (values, handle) {

                                var actualPriceGet = parseFloat(document.getElementById('actual_price').value || 0);

                                const discount = parseFloat(values[handle]);
                                discountLabel.textContent = discount;

                                const offerPrice = actualPriceGet - (actualPriceGet * discount / 100);
                                offerPriceInput.value = offerPrice.toFixed(2);
                                $('#offer_percentage').val(discount);

                            });
                        }

                        // Optional: toggle slider visibility based on offer status
                        document.getElementById('offer_status').addEventListener('change', function () {
                            const discountGroup = document.getElementById('kt_ecommerce_add_product_discount_percentage');
                            if (this.value === '1') {
                                discountGroup.classList.remove('d-none');
                            } else {
                                discountGroup.classList.add('d-none');
                                offerPriceInput.value = ''; // reset offer price
                                discountSlider.noUiSlider.set(0); // reset slider
                            }
                        });

                        // Trigger change on load if offer is active
                        if (document.getElementById('offer_status').value === '1') {
                            document.getElementById('kt_ecommerce_add_product_discount_percentage').classList.remove('d-none');
                        }
                    });

                    document.getElementById('is_physical').addEventListener('change', function () {
                        const shippingFields = document.getElementById('shipping-fields');
                        shippingFields.style.display = this.checked ? 'block' : 'none';
                    });

                </script>


                <script>
                    const base_url = '<?php echo base_url(); ?>';
                </script>
                <script
                    src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/create-product.js?ver=8.9"></script>

                </script>



</body>

</html>