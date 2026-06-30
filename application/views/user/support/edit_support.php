<?php $this->load->view('user/layout/user_style');?>
    <!--end::Head-->

    <!--begin::Body-->

    <body id="kt_app_body" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <!--begin::App-->
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page  flex-column flex-column-fluid " id="kt_app_page">

                <div id="kt_app_header" class="app-header " data-kt-sticky="true" data-kt-sticky-activate-="true" data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <div class="app-container  container-xxl d-flex align-items-stretch justify-content-between " id="kt_app_header_container">
                        <div class="app-header-wrapper d-flex flex-grow-1 align-items-stretch justify-content-between" id="kt_app_header_wrapper">
                            <?php $this->load->view('user/layout/user_header'); ?>
                        </div>
                    </div>
                </div>
                <!--end::Header-->

                <!--begin::Wrapper-->
                <div class="app-wrapper  flex-column flex-row-fluid " id="kt_app_wrapper">

                    <!--begin::Wrapper container-->
                    <div class="app-container  container-xxl d-flex flex-row-fluid ">

                        <!--begin::Sidebar-->
                      <?php $this->load->view('user/layout/user_sidebar');?>
                        <!--end::Sidebar-->


                        <!--begin::Main-->
                        <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                            <!--begin::Content wrapper-->
                            <div class="d-flex flex-column flex-column-fluid">

                                <!--begin::Toolbar-->
                                <div id="kt_app_toolbar" class="app-toolbar  d-flex pb-3 pb-lg-5 ">

                                    <!--begin::Toolbar container-->
                                    <div class="d-flex flex-stack flex-row-fluid">
                                        <!--begin::Toolbar container-->
                                        <div class="d-flex flex-column flex-row-fluid">
                                            <!--begin::Toolbar wrapper-->

                                            <!--begin::Page title-->
                                            <div class="page-title d-flex align-items-center me-3">
                                                <!--begin::Title-->
                                                <h1 class="page-heading d-flex flex-column justify-content-center text-gray-900 fw-bold fs-lg-2x gap-2">
                                                     <span> <?php echo $title; ?></span>

                                                        <!--begin::Description-->
                                                <span class="page-desc text-gray-600 fs-base fw-semibold">
                                                  <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                    <li class="breadcrumb-item text-muted">
                                                        <a href="<?php echo base_url();?>" class="text-muted text-hover-primary">
                                                        Dashboard                         
                                                        </a>
                                                    </li>
                                                    <li class="breadcrumb-item">
                                                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                                    </li>
                                                    <li class="breadcrumb-item text-muted">
                                                    <?php echo $title; ?> </li>
                                                </ul>      
                                                </span>
                                                <!--end::Description-->
                                                </h1>
                                                                                    <!--end::Title-->
                                            </div>
                                            <!--end::Page title-->

                                        </div>
                                        <!--end::Toolbar container-->
                                    </div>
                                    <!--end::Toolbar container-->
                                </div>
                                <!--end::Toolbar-->

                             
                                <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
           <!--begin::Messenger-->
            <div class="card" id="kt_chat_messenger">
                <!--begin::Card header-->


                <div class="card-header">
                <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1 border-0 pt-6">Ticket Information</a>
                </div>

                <div class="card-header" id="kt_chat_messenger_header">

                    <div class="card-title">

                        <div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold mt-4">

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
                                <span class="fs-5"> <?= $user_info->email; ?> ( <?= $user_info->referral_id; ?> ) </span>
                            </div>

                        
                        </div>
                        
                    </div>

                   
                </div>

                <!--begin::Card body-->
                <div class="card-body" id="kt_chat_messenger_body">
                    <!--begin::Messages-->
                    <div class="scroll-y me-n5 pe-5 h-300px h-lg-auto " data-kt-element="messages">
                    
                    <div class="d-flex justify-content-end mb-10 d-none" data-kt-element="template-out">
                        <div class="d-flex flex-column align-items-end">
                            <div class="d-flex align-items-center mb-2">
                                <div class="message-text p-5 rounded bg-light-primary text-primary-900 fw-semibold mw-lg-400px text-end">
                                    <span data-kt-element="message-text"></span>
                                </div>
                            </div>
                        </div>
                    </div> 


                    <div class="message d-none text-start" data-kt-element="template-in">
                        <div class="message-text bg-primary text-white p-2 rounded d-inline-block">
                            <span data-kt-element="message-text"></span>
                        </div>
                    </div>


                                <?php 
                        $check_message = $this->db->query("SELECT * FROM support_message WHERE ticket_id = '".$history->ticket_id."' ")->result();

                        if(count($check_message) > 0){
                            foreach($check_message as $check_message_row){ 

                                if($check_message_row->admin == '1' && $check_message_row->files == ""){ ?>
                                    <div class="d-flex justify-content-end mb-10">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-3">
                                                    <span class="text-muted fs-7 mb-1"><?= $check_message_row->created_date; ?></span>
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">You</a>
                                                </div>
                                                <div class="symbol symbol-35px symbol-circle">
                                                    <img src="<?= base_url(); ?>/assets/admin/media/avatars/300-1.jpg">
                                                </div>
                                            </div>
                                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end">
                                                <?= $check_message_row->message; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php }

                                if($check_message_row->admin == '1' && $check_message_row->files){ ?>
                                    <div class="d-flex justify-content-end mb-10">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-3">
                                                    <span class="text-muted fs-7 mb-1"><?= $check_message_row->created_date; ?></span>
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">You</a>
                                                </div>
                                                <div class="symbol symbol-35px symbol-circle">
                                                    <img src="<?= base_url(); ?>/assets/admin/media/avatars/300-1.jpg">
                                                </div>
                                            </div>
                                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end">
                                                <?= $check_message_row->message; ?>
                                            </div>
                                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end mt-4">
                                                <img src="<?= $check_message_row->files; ?>" width="300" height="300"/>
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
                                <form method="post" id="uploadticketimage" 
                                enctype="multipart/form-data" 
                                data-kt-redirect-url="<?php echo base_url(); ?>user/view-ticket/<?php echo $history->id; ?>" 
                                action="<?php echo base_url();?>user/update-ticket">

                                    <div class="card-footer pt-4" id="kt_chat_messenger_footer">

                                        <div class="row">
                                        <div class="col-lg-8">
                                        <textarea class="form-control form-control-flush mb-3" rows="1" data-kt-element="input" name="ticket_message" placeholder="Type a message"></textarea>
                                        </div>
                                        <div class="col-lg-4">
                                        <div class="d-flex flex-stack">
                                        <div class="d-flex align-items-center me-2">
                                        <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button" id="uploadBtn">
                                        <i class="ki-duotone ki-paper-clip fs-3"></i>
                                        </button>

                                        <input type="file" id="fileInput" name="ticketimage" class="d-none" accept="image/png, image/jpg, image/jpeg">

                                        </div>  
                                        <input type="hidden" name="ticket_id" id="ticket_id" value="<?php echo $history->ticket_id; ?>">
                                        <button class="btn btn-primary" type="submit" data-kt-element="send">Send</button>
                                        </div>
                                        </div></div>

                                    </div>



                                </form>
                                        
                                        <!--end::Card footer-->
                                    </div>
                                    <!--end::Messenger-->

                                                    

                                                    </div>
                                                </div>

                                            </div>

                                            <!--begin::Footer-->

                                        </div>
                                </div>
                                <!--end::Wrapper-->

                        </div>
                        <!--end::Page-->
                    </div>
                    <!--end::App-->



                            </div>
                            <!--end::Content wrapper-->

                                <!--begin::Footer-->
                                <?php $this->load->view('user/layout/user_footer');?>
                                <!--end::Footer-->
                        </div>
                        <!--end:::Main-->


                    </div>
                    <!--end::Wrapper container-->
                </div>
                <!--end::Wrapper-->


            </div>
            <!--end::Page-->
        </div>
        <!--end::App-->

        <!--end::Engage modals-->
        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i></div>
        <!--end::Scrolltop-->

        <!--begin::Javascript-->
        <?php $this->load->view('user/layout/user_script');?>
        <script>
        const base_url = '<?php echo base_url();?>';
        const agent_id = '<?php echo $user_id;?>';
        </script>
        <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/user-edit-support.js?ver=3.1"></script>
    </body>

    </html>