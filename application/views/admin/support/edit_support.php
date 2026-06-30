<?php $this->load->view('admin/Layout/common_style'); ?>

<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet"
    type="text/css">
<link href="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet"
    type="text/css" />

<style>
    .h-md-40 {
        min-height: 42%;
    }

    [data-kt-element="messages"] {
        display: flex;
        flex-direction: column;
    }

    .message {
        display: flex;
        margin-bottom: 10px;
        max-width: 80%;
        /* Prevents full-width messages */
    }

    .text-end {
        justify-content: flex-end;
        /* Aligns user messages to the right */
    }

    .text-start {
        justify-content: flex-start;
        /* Aligns bot messages to the left */
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
                                                Admin
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




                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content  flex-column-fluid mt-10">

                            <!--begin::Content container-->
                            <div id="kt_app_content_container" class="app-container  container-xxl ">


                                <?php $this->load->view('notification'); ?>


                                <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
                                    <!--begin::Messenger-->
                                    <div class="card" id="kt_chat_messenger">
                                        <!--begin::Card header-->


                                        <div class="card-header">
                                            <a href="#"
                                                class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1 border-0 pt-6">Ticket
                                                Information</a>
                                        </div>

                                        <div class="card-header" id="kt_chat_messenger_header">

                                            <div class="card-title">

                                                <div
                                                    class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold mt-4">

                                                    <div class="flex-root d-flex flex-column">
                                                        <span class="text-muted">Ticket ID</span>
                                                        <span class="fs-5">#<?= $history->ticket_id ?></span>
                                                    </div>

                                                    <div class="flex-root d-flex flex-column">
                                                        <span class="text-muted">Created Date</span>
                                                        <span class="fs-5"><?= $history->date ?></span>
                                                    </div>

                                                    <div class="flex-root d-flex flex-column">
                                                        <span class="text-muted">Ticket Status</span>
                                                        <span class="fs-5"><?= $status; ?></span>
                                                    </div>

                                                    <div class="flex-root d-flex flex-column">
                                                        <span class="text-muted">User Info</span>
                                                        <span class="fs-5">
                                                            <?= $user_info && $user_info->email ? $user_info->email : "Unkown User"; ?>
                                                            (
                                                            <?= $user_info && $user_info->referral_id ? $user_info->referral_id : "Unkown User"; ?>
                                                            ) </span>
                                                    </div>


                                                </div>

                                            </div>



                                            <div class="row col-lg-12 mb-7 mt-4"
                                                data-select2-id="select2-data-140-hoyg">

                                                <div class="col-sm-3 fv-row mb-3"
                                                    data-select2-id="select2-data-139-b0zf">
                                                    <label class="fs-6 fw-semibold mb-2">Status</label>
                                                    <select class="form-select form-select-solid "
                                                        data-control="select2" name="ticket_updated_status"
                                                        id="ticket_updated_status">
                                                        <option value="1" <?php echo $history->status == "1" ? "selected" : ""; ?> data-select2-id="select2-data-17-agka">
                                                            Open</option>
                                                        <option value="0" <?php echo $history->status == "0" ? "selected" : ""; ?> data-select2-id="select2-data-149-iaqp">
                                                            Pending</option>
                                                        <option value="2" <?php echo $history->status == "2" ? "selected" : ""; ?> data-select2-id="select2-data-151-x6fn">
                                                            Closed</option>
                                                    </select>
                                                </div>

                                            </div>
                                        </div>

                                        <!--begin::Card body-->
                                        <div class="card-body" id="kt_chat_messenger_body">
                                            <!--begin::Messages-->
                                            <div class="scroll-y me-n5 pe-5 h-300px h-lg-auto "
                                                data-kt-element="messages">

                                                <div class="d-flex justify-content-end mb-10 d-none"
                                                    data-kt-element="template-out">
                                                    <div class="d-flex flex-column align-items-end">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div
                                                                class="message-text p-5 rounded bg-light-primary text-primary-900 fw-semibold mw-lg-400px text-end">
                                                                <span data-kt-element="message-text"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="message d-none text-start" data-kt-element="template-in">
                                                    <div
                                                        class="message-text bg-primary text-white p-2 rounded d-inline-block">
                                                        <span data-kt-element="message-text"></span>
                                                    </div>
                                                </div>


                                                <?php
                                                $check_message = $this->db->query("SELECT * FROM support_message WHERE ticket_id = '" . $history->ticket_id . "' ")->result();

                                                if (count($check_message) > 0) {
                                                    foreach ($check_message as $check_message_row) {

                                                        if ($check_message_row->admin == '1' && $check_message_row->files == "") { ?>
                                                            <div class="d-flex justify-content-end mb-10">
                                                                <div class="d-flex flex-column align-items-end">
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        <div class="me-3">
                                                                            <span
                                                                                class="text-muted fs-7 mb-1"><?= $check_message_row->created_date; ?></span>
                                                                            <a href="#"
                                                                                class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">You</a>
                                                                        </div>
                                                                        <div class="symbol symbol-35px symbol-circle">
                                                                            <img
                                                                                src="<?= base_url(); ?>/assets/admin/media/avatars/300-1.jpg">
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end">
                                                                        <?= $check_message_row->message; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php }

                                                        if ($check_message_row->admin == '1' && $check_message_row->files) { ?>
                                                            <div class="d-flex justify-content-end mb-10">
                                                                <div class="d-flex flex-column align-items-end">
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        <div class="me-3">
                                                                            <span
                                                                                class="text-muted fs-7 mb-1"><?= $check_message_row->created_date; ?></span>
                                                                            <a href="#"
                                                                                class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">You</a>
                                                                        </div>
                                                                        <div class="symbol symbol-35px symbol-circle">
                                                                            <img
                                                                                src="<?= base_url(); ?>/assets/admin/media/avatars/300-1.jpg">
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end">
                                                                        <?= $check_message_row->message; ?>
                                                                    </div>
                                                                    <div
                                                                        class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end mt-4">
                                                                        <img src="<?= $check_message_row->files; ?>" width="300"
                                                                            height="300" />
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        <?php }

                                                    }
                                                } ?>

                                                <div data-kt-element="messages"></div>

                                            </div>
                                        </div>


                                        <!--begin::Card footer-->
                                        <form method="post" id="uploadticketimage" enctype="multipart/form-data"
                                            data-kt-redirect-url="<?php echo base_url(); ?>edit-ticket/<?= $tikcet_id; ?>"
                                            action="<?php echo base_url(); ?>update-ticket">

                                            <div class="card-footer pt-4" id="kt_chat_messenger_footer">

                                                <!-- <textarea class="form-control form-control-flush mb-3" 
                                        rows="1" 
                                        data-kt-element="input" 
                                        name="ticket_message"
                                        placeholder="Type a message"></textarea>

                                        <div class="d-flex flex-stack">
                                            <div class="d-flex align-items-center me-2">

                                                <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button" id="uploadBtn">
                                                    <i class="ki-duotone ki-paper-clip fs-3"></i>
                                                </button>

                                                <input type="file" id="fileInput" name="ticketimage" class="d-none" accept="image/png, image/jpg, image/jpeg">

                                            </div>  
                                            <input type="hidden" name="ticket_id" id="ticket_id" value="<?= $history->ticket_id; ?>" />
                                            <button class="btn btn-primary" type="submit" data-kt-element="send">Send</button>
                                        </div> -->

                                                <div class="row">
                                                    <div class="col-lg-8">
                                                        <textarea class="form-control form-control-flush mb-3" rows="1"
                                                            data-kt-element="input" name="ticket_message"
                                                            placeholder="Type a message"></textarea>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="d-flex flex-stack">
                                                            <div class="d-flex align-items-center me-2">

                                                                <button
                                                                    class="btn btn-sm btn-icon btn-active-light-primary me-1"
                                                                    type="button" id="uploadBtn">
                                                                    <i class="ki-duotone ki-paper-clip fs-3"></i>
                                                                </button>

                                                                <input type="file" id="fileInput" name="ticketimage"
                                                                    class="d-none"
                                                                    accept="image/png, image/jpg, image/jpeg">

                                                            </div>
                                                            <input type="hidden" name="ticket_id" id="ticket_id"
                                                                value="<?php echo $history->ticket_id; ?>">
                                                            <button class="btn btn-primary" type="submit"
                                                                data-kt-element="send">Send</button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>



                                        </form>

                                        <!--end::Card footer-->
                                    </div>
                                    <!--end::Messenger-->



                                </div>
                            </div>

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
        <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/upgrade-plan.js"></script>
        <script src="<?php echo base_url(); ?>/assets/admin/js/custom/utilities/modals/users-search.js"></script>
        <script src="<?php echo base_url(); ?>/assets/admin/plugins/global/plugins.bundle.js"></script>
        <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/datatables/datatables.bundle.js"></script>
        <link href="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.css"
            rel="stylesheet" type="text/css" />
        <script src="<?php echo base_url(); ?>assets/admin/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>

        <script>
            const base_url = '<?php echo base_url(); ?>';
            const ticket_id = '<?= $history->ticket_id ?>';
        </script>
        <script
            src="<?php echo base_url(); ?>/assets/admin/js/custom/authentication/sign-in/edit-support.js?ver=4.9"></script>
        <script>
        </script>

</body>

</html>