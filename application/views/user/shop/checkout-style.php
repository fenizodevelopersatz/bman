
<style>
.section-b-space {
    padding-bottom: calc(30px + 20*(100vw - 320px)/1600);
}
.g-sm-4, .gy-sm-4 {
    --bs-gutter-y: 1.5rem;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    gap: calc(17px + 28*(100vw - 320px)/1600);
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li {
    position: relative;
    width: 100%;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-icon {
    position: absolute;
    top: 0;
    left: 0;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    padding: 6px;
    background-color: #f8f8f8;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-icon {
    position: absolute;
    top: 0;
    left: 0;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    padding: 6px;
    background-color: #f8f8f8;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box {
    padding: calc(14px + 15*(100vw - 320px)/1600);
    background-color: #fff;
    border-radius: 8px;
    -webkit-box-shadow: 0 0 8px #eee;
    box-shadow: 0 0 8px #eee;
    margin-left: 66px;
    position: relative;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box::before {
    content: "";
    position: absolute;
    top: 25px;
    left: -42px;
    width: 0;
    height: 115%;
    border-left: 1px dashed rgba(34,34,34,.18);
    z-index: -1;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-title {
    margin-bottom: calc(9px + 8*(100vw - 320px)/1600);
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: justify;
    -ms-flex-pack: justify;
    justify-content: space-between;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-title h4 {
    font-weight: 600;
    font-size: calc(16px + 3*(100vw - 320px)/1600);
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .delivery-address-box {
    border-radius: 8px;
    padding: calc(12px + 12*(100vw - 320px)/1600);
    background-color: #f8f9fa;
    height: 100%;
    -webkit-box-shadow: 0 0 9px rgba(0,0,0,.07);
    box-shadow: 0 0 9px rgba(0,0,0,.07);
}
.form-check {
    display: block;
    min-height: 1.5rem;
    padding-left: 1.5em;
    margin-bottom: .125rem;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .delivery-address-box>div .label {
    position: absolute;
    top: 0;
    right: 0;
    background-color:#181e28;
    padding: 2px 8px;
    border-radius: 4px;
    color: #fff;
    font-size: 12px;
    letter-spacing: .8px;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .delivery-address-box>div .delivery-address-detail {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    gap: 10px;
    margin-left: 10px;
    width: calc(85% + -10*(100vw - 320px)/1600);
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .delivery-address-box>div .delivery-address-detail li {
    display: block;
    width: 100%;
}
.form-check-input:checked[type=radio] {
    --bs-form-check-bg-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
    margin-right: 10px;
}
input[type=radio]{
    padding: 10px !important;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .custom-accordion .accordion-item .accordion-header .accordion-button .form-check .form-check-label {
    font-weight: 500;
    color: #222;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    width: 100%;
    font-size: calc(15px + 3*(100vw - 320px)/1600);
    padding: 16px 20px;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .custom-accordion .accordion-item .accordion-collapse .accordion-body {
    padding-top: 0;
}
.checkout-section-2 .right-side-summery-box {
    position: sticky;
    top: 110px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 {
    border-radius: 7px;
    background-color: #fff;
    padding: calc(14px + 15*(100vw - 320px)/1600);
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-header {
    padding-bottom: calc(12px + 4*(100vw - 320px)/1600);
    border-bottom: 1px solid #ececec;
    margin-bottom: 10px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain {
    border-bottom: 1px solid #ececec;
    padding-bottom: 10px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain li:last-child {
    border-bottom: none;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain .checkout-image {
    width: calc(41px + 11*(100vw - 320px)/1600);
    height: calc(41px + 11*(100vw - 320px)/1600);
    -o-object-fit: contain;
    object-fit: contain;
    margin-right: 10px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain li h4 {
    font-size: 15px;
    color: #4a5568;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain li h4.price {
    color: #4a5568;
    margin-left: auto;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-total {
    margin-top: 0px;
    padding-top: 5px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-contain li {
    padding: calc(6px + 2*(100vw - 320px)/1600) 0;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-total li {
    padding-top: 8px;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
li {
    display: inline-block;
    font-size: 14px;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-total li h4 {
    font-size: 17px;
    color: #222;
}
.checkout-section-2 .right-side-summery-box .summery-box-2 .summery-total li h4.price {
    margin-left: auto;
}
.checkout-section-2 .right-side-summery-box .checkout-offer {
    margin-top: 24px;
    border-radius: 7px;
    background-color: #f8f8f8;
    padding: calc(14px + 15*(100vw - 320px)/1600);
}
.checkout-section-2 .right-side-summery-box .checkout-offer .offer-title {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    gap: calc(9px + 3*(100vw - 320px)/1600);
    margin-bottom: calc(13px + 7*(100vw - 320px)/1600);
}
.checkout-section-2 .right-side-summery-box .checkout-offer .offer-title .offer-icon {
    width: 20px;
}
.checkout-section-2 .right-side-summery-box .checkout-offer .offer-detail li p {
    color: #4a5568;
    line-height: 1.5;
    position: relative;
    padding-left: 23px;
    font-size: calc(13px + 0*(100vw - 320px)/1600);
    margin: 0;
}
.checkout-section-2 .left-sidebar-checkout .checkout-detail-box>ul>li .checkout-box .checkout-detail .delivery-address-box>div {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    position: relative;
}

.card-header {
    padding: calc(12px + 4*(100vw - 320px)/1600) calc(16px + 6*(100vw - 320px)/1600);
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    border-bottom: 1px solid #ececec;
}
</style>