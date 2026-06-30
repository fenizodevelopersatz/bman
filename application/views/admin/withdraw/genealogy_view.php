<?php $this->load->view('admin/Layout/common_style');?>

    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url();?>/assets/admin/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />

    <script src="https://balkan.app/js/familytree.js"></script>
    <style>
        #tree {
            width: 100%;
            height: 800px;
            border: 1px solid #ccc;
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
                                                        Network  Management                    
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


                     <!-- INSEIDE FORM ENTER -->
                                    <div id="kt_account_settings_profile_details" class="collapse show">



                                                <div class="container">
                                                <h2 class="text-center">Genealogy Tree</h2>
                                                <div id="tree"></div>
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
        

        <script src="<?php echo base_url();?>/assets/admin/js/familytree.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    fetch("<?= base_url('tree-data/'.$user_id) ?>")
                        .then(response => response.json())
                        .then(data => {
                            let parentMap = {};

                            data.forEach(member => {
                                parentMap[member.id] = member;
                                member.color = (member.position === "Left") ? "#3498db" : "#e74c3c"; 
                            });

                            data.forEach(member => {
                                if (member.pid !== null && parentMap[member.pid]) {
                                    let parent = parentMap[member.pid];

                                    if (member.position === "Left") {
                                        parent.leftChild = member.id;
                                    } else if (member.position === "Right") {
                                        parent.rightChild = member.id;
                                    }
                                }
                            });

                            var options = getOptions();
                            var family = new FamilyTree(document.getElementById('tree'), {
                                mouseScrool: FamilyTree.none,
                                scaleInitial: options.scaleInitial,
                                mode: 'dark',
                                template: 'hugo',
                                enableSearch: true,
                                nodeMenu: {
                                    details: { text: 'Details' }
                                },
                                nodeTreeMenu: true,
                                nodeBinding: {
                                field_0: 'email',    
                                field_1: 'position',    
                                field_2: 'register_date',  
                                img_0: 'photo',
                                background: 'color' 
                                },
                                editForm: {
                                    titleBinding: "name",
                                    photoBinding: "photo",
                                    generateElementsFromFields: false,
                                    elements: [
                                        { type: 'textbox', label: 'Full Name', binding: 'name' },
                                        { type: 'textbox', label: 'Email Address', binding: 'email' },
                                        [
                                            { type: 'textbox', label: 'Phone', binding: 'phone' },
                                            { type: 'date', label: 'Date Of Birth', binding: 'register_date' }
                                        ],
                                    ]
                                }
                            });

                            family.on('render-node', function (sender, args) {
                                if (args.node.position === "Left") {
                                    args.node.element.style.backgroundColor = "#3498db";
                                } else if (args.node.position === "Right") {
                                    args.node.element.style.backgroundColor = "#e74c3c"; 
                                }
                            });

                            family.load(data);
                        })
                        .catch(error => console.error("Error:", error));
                });

                function getOptions() {
                    const searchParams = new URLSearchParams(window.location.search);
                    var fit = searchParams.get('fit');
                    var enableSearch = true;
                    var scaleInitial = 1;
                    if (fit == 'yes') {
                        enableSearch = false;
                        scaleInitial = FamilyTree.match.boundary;
                    }
                    return { enableSearch, scaleInitial };
                }


        //JavaScript
            </script>

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
            <script src="<?php echo base_url();?>/assets/admin/js/custom/authentication/sign-in/create-user.js?ver=2.9"></script>
            <script>
            </script>
    </body>

    </html>