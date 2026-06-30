$(document).ready(function () {


    function setCheckRow(selector, ok, okIcon = "ph-check-circle", warnIcon = "ph-warning-circle") {
        const el = document.querySelector(selector);
        if (!el) return;

        const icon = el.querySelector("i");
        if (!icon) return;

        // reset
        icon.classList.remove("ph", okIcon, warnIcon);
        icon.classList.add("ph", ok ? okIcon : warnIcon);

        // optional: add classes for styling
        el.classList.toggle("ok", !!ok);
        el.classList.toggle("warn", !ok);
    }


    axios.get(base_url + 'balance-info-user/' + agent_id)
        .then(function (response) {
            const data = response.data.data;
            const data_result = response.data.result;

            if (data_result) {



                $('#left_leg_bv').html(parseFloat(data.left_leg_bv || 0).toFixed(2));
                $('#right_leg_bv').html(parseFloat(data.right_leg_bv || 0).toFixed(2));

                $('#left_leg_strength').html((data.left_leg_strength || 'STRONG').toUpperCase());
                $('#right_leg_strength').html((data.right_leg_strength || 'WEAK').toUpperCase());

                // If you have single carry forward value
                $('#carry_forward_bv').html(parseFloat(data.carry_forward_bv || 0).toFixed(0));

                // If you have left & right carry forward separately (recommended)
                $('#left_carry_forward_bv').html(parseFloat(data.left_carry_forward_bv || 0).toFixed(0));
                $('#right_carry_forward_bv').html(parseFloat(data.right_carry_forward_bv || 0).toFixed(0));

                $('#need_bv').html(parseFloat(data.need_bv || 0).toFixed(0));
                $('#pairs_today').html(parseInt(data.pairs_today || 0));

                // Optional progress
                if (data.weekly_progress != null) {
                    const p = Math.max(0, Math.min(100, parseFloat(data.weekly_progress)));
                    $('#weekly_progress').html(p.toFixed(0) + '%');
                    $('#weekly_progress_bar').css('width', p + '%');
                }


                // ✅ Checklist statuses
                const kycOk = parseInt(data.kyc_status || 0) === 1;
                const bankOk = parseInt(data.bank_verification_status || 0) === 1;
                const actOk = parseInt(data.account_active_status || 0) === 1;

                // For weak-leg line: if weak leg is OK -> show check, else warning.
                // Note: Your label is "Weak Leg Needs BV" (warning when NOT ok)
                const weakOk = parseInt(data.weak_leg_ok_status || 0) === 1;

                setCheckRow("#chk_kyc", kycOk);
                setCheckRow("#chk_bank", bankOk);
                setCheckRow("#chk_active", actOk);


                $('#user_usd_balance').html(data.user_usd_balance);
                $('#user_token_balance').html(data.user_token_balance);
                $('#direct_site_currency').html(data.direct_site_currency);
                $('#direct_token_currency').html(data.direct_token_currency);
                $('#level_site_currency').html(data.level_site_currency);
                $('#level_token_currency').html(data.level_token_currency);

                if (data.team_snapshot) {
                    $('#left_team_count').html(data.team_snapshot.left_team);
                    $('#right_team_count').html(data.team_snapshot.right_team);
                    $('#active_members_count').html(data.team_snapshot.active_members);
                    $('#new_joins_count').html('+' + data.team_snapshot.new_joins);
                }

                $('#left_leg_count').html(data.left_leg_count);
                $('#right_leg_count').html(data.right_leg_count);
                $('#left_leg_investment').html(data.left_leg_investment);
                $('#right_leg_investment').html(data.right_leg_investment);
                $('#user_pending_commission').html(data.user_pending_commission);
                $('#user_total_withdrawn').html(data.user_total_withdrawn);
            }

        })
        .catch(function (error) {
            console.error('Error fetching data:', error);
        });


});

$(document).ready(function () {
    var KTDatatablesExample = function () {
        var table;
        var datatable;

        var initDatatable = function () {
            table = document.querySelector('#kt-client-follow-table');
            if (!table) return;

            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "transaction-list",
                    type: "GET",
                    data: function (d) {
                        d.from_date = $('#cl_from_date').val();
                        d.to_date = $('#cl_to_date').val();
                        d.client_filter = agent_id;
                        d.call_status = $('#call_status').val();
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'UserInfo' },
                    { data: 'TransactionInfo' },
                    { data: 'CurrencyInfo' },
                    { data: 'Status' },
                ]
            });
        }

        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter, #call_status').on('change', function () {
                datatable.ajax.reload(null, false);
                loadData();
            });
        }

        return {
            init: function () {
                table = document.querySelector('#kt-client-follow-table');

                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }

                if (!table) {
                    return;
                }

                initDatatable();
                handleFilterChange();
            }
        };
    }();

    KTDatatablesExample.init();
    loadData();

    function loadData() {

        var from_date = $('#cl_from_date').val();
        var to_date = $('#cl_to_date').val();
        var client_filter = $('#client_filter').val();
        var call_status = $('#call_status').val();

        $.ajax({
            url: base_url + 'all-transaction-get',
            type: 'POST',
            data: {
                from_date: from_date,
                to_date: to_date,
                client_filter: client_filter,
                call_status: call_status,
            },
            success: function (response) {
                var data = JSON.parse(response);
                let totalAmount = typeof data.total_amount === "string"
                    ? data.total_amount.replace(/,/g, '')
                    : data.total_amount;

                let totalTokenAmount = typeof data.total_token_amount === "string"
                    ? data.total_token_amount.replace(/,/g, '')
                    : data.total_token_amount;

                const count1 = new countUp.CountUp("icd", parseFloat(totalAmount) || 0);
                const count2 = new countUp.CountUp("tcd", parseFloat(totalTokenAmount) || 0);

                count1.start();
                count2.start();
            },
            error: function (xhr, status, error) {
                console.error('Error fetching chart data:', error);
            }
        });
    };


});
