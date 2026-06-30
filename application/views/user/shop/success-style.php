<style>
    :root{
--theme-color: #a92525;
}
h3 {
    font-size: calc(16px + 4*(100vw - 320px)/1600);
    font-weight: 500;
    line-height: 1.2;
    margin: 0;
}
.breadcrumb-section {
background-color: #f8f8f8;
position: relative;
overflow: hidden;
}
.breadcrumb-section .breadcrumb-order {
    display: block;
}
.breadcrumb-section .breadcrumb-contain {
    padding: calc(26px + 14*(100vw - 320px)/1600) 0;
    text-align: center;
    color: #222;
    font-family: "Public Sans",sans-serif;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
.breadcrumb-section .breadcrumb-order .order-box .order-contain h3 {
    font-size: calc(21px + 3*(100vw - 320px)/1600);
    font-weight: 700;
    margin-bottom: 6px;
}
.breadcrumb-section .breadcrumb-order .order-box .order-contain h5 {
    margin-bottom: 8px;
    line-height: 1.4;
}
.g-sm-4, .gy-sm-4 {
    --bs-gutter-y: 1.5rem;
}
.cart-table {
    background-color: #b58989;
    padding: calc(18px + 17*(100vw - 320px)/1600) calc(12px + 13*(100vw - 320px)/1600);
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table {
    --bs-table-bg: transparent;
    --bs-table-accent-bg: transparent;
    --bs-table-striped-color: #212529;
    --bs-table-striped-bg: rgba(0, 0, 0, 0.05);
    --bs-table-active-color: #212529;
    --bs-table-active-bg: rgba(0, 0, 0, 0.1);
    --bs-table-hover-color: #212529;
    --bs-table-hover-bg: rgba(0, 0, 0, 0.075);
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    vertical-align: top;
    border-color: #dee2e6;
}
.cart-table table tbody tr td.product-detail .product {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    overflow: hidden;
}
.cart-table table tbody tr td.product-detail .product .product-image {
    width: 70px;
}
.cart-table table tbody tr td.product-detail .product .product-image img {
    -webkit-transition: all .3s ease-in-out;
    transition: all .3s ease-in-out;
}
.order-table tbody tr td:first-child {
    font-weight: 600;
}
.order-table tbody tr td:nth-child(2) {
    color: #4a5568;
}
.cart-table table tbody tr td.quantity {
    width: 20%;
}
.cart-table table tbody tr td {
    padding: calc(16px + 6*(100vw - 320px)/1600) 16px;
    min-width: calc(135px + 35*(100vw - 320px)/1600);
}
.summery-box {
    border-radius: 5px;
    background-color: #f8f8f8;
}
.summery-box .summery-header {
    padding: calc(12px + 4*(100vw - 320px)/1600) calc(16px + 6*(100vw - 320px)/1600);
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    border-bottom: 1px solid #ececec;
}
.summery-box .summery-contain {
    padding: calc(11px + 5*(100vw - 320px)/1600) calc(11px + 11*(100vw - 320px)/1600);
    border-bottom: 1px solid #ececec;
}
.summery-box .summery-contain li {
    padding: calc(6px + 4*(100vw - 320px)/1600) 0;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
.summery-box .summery-contain li h4 {
    font-size: 15px;
    color: #4a5568;
}
.summery-box .summery-contain li h4.price {
    color: #4a5568;
    margin-left: auto;
}
.summery-box .summery-total {
    padding: 0 calc(16px + 6*(100vw - 320px)/1600);
}
.section-b-space {
    padding-bottom: calc(30px + 20*(100vw - 320px)/1600);
}
.summery-box .summery-total li:last-child {
    border-top: 1px solid #ececec;
    padding: calc(12px + 4*(100vw - 320px)/1600) 0;
}
.summery-box .summery-total li:last-child h4 {
    font-weight: 600;
    font-size: calc(16px + 4*(100vw - 320px)/1600);
}
.summery-box .summery-total li h4 {
    font-size: 17px;
    color: #222;
}
.summery-box .summery-total li {
    padding-top: 12px;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
.list-total{
display: flex;
justify-content: space-between;
}
.breadcrumb-section .breadcrumb-contain {
    padding: calc(26px + 14*(100vw - 320px)/1600) 0;
    text-align: center;
    color: #222;
    font-family: "Public Sans",sans-serif;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}


.breadcrumb-section .breadcrumb-order .order-box .order-image{
    width:calc(170px + 80*(100vw - 320px)/1600);
    height:auto;
    margin:0 auto calc(16px + 12*(100vw - 320px)/1600)
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark{
    position:relative;
    padding:30px;
    -webkit-animation:checkmark 5m cubic-bezier(0.42, 0, 0.275, 1.155) both;
    animation:checkmark 5m cubic-bezier(0.42, 0, 0.275, 1.155) both;
    display:inline-block;
    -webkit-transform:scale(0.8);
    transform:scale(0.8);
    margin:-20px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark__check{
    position:absolute;
    top:50%;
    left:50%;
    z-index:10;
    -webkit-transform:translate3d(-50%, -50%, 0);
    transform:translate3d(-50%, -50%, 0);
    fill:#fff
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark__background{
    fill:var(--theme-color);
    -webkit-animation:rotate 35s linear both infinite;
    animation:rotate 35s linear both infinite
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star{
    position:absolute;
    -webkit-animation:grow 3s infinite;
    animation:grow 3s infinite;
    fill:var(--theme-color);
    opacity:0
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(1){
    width:12px;
    height:12px;
    left:12px;
    top:16px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(2){
    width:18px;
    height:18px;
    left:168px;
    top:84px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(3){
    width:10px;
    height:10px;
    left:32px;
    top:162px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(4){
    height:20px;
    width:20px;
    left:82px;
    top:-12px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(5){
    width:14px;
    height:14px;
    left:125px;
    top:162px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(6){
    width:10px;
    height:10px;
    left:16px;
    top:16px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(1){
    -webkit-animation-delay:1.5s;
    animation-delay:1.5s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(2){
    -webkit-animation-delay:3s;
    animation-delay:3s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(3){
    -webkit-animation-delay:4.5s;
    animation-delay:4.5s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(4){
    -webkit-animation-delay:6s;
    animation-delay:6s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(5){
    -webkit-animation-delay:7.5s;
    animation-delay:7.5s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .star:nth-child(6){
    -webkit-animation-delay:9s;
    animation-delay:9s
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark{
    position:relative;
    padding:30px;
    -webkit-animation:checkmark 5m cubic-bezier(0.42, 0, 0.275, 1.155) both;
    animation:checkmark 5m cubic-bezier(0.42, 0, 0.275, 1.155) both;
    display:inline-block;
    -webkit-transform:scale(0.8);
    transform:scale(0.8);
    margin:-20px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark__check{
    position:absolute;
    top:50%;
    left:50%;
    z-index:10;
    -webkit-transform:translate3d(-50%, -50%, 0);
    transform:translate3d(-50%, -50%, 0);
    fill:#fff
}
.breadcrumb-section .breadcrumb-order .order-box .order-image .checkmark__background{
    fill:var(--theme-color);
    -webkit-animation:rotate 35s linear both infinite;
    animation:rotate 35s linear both infinite
}
.breadcrumb-section .breadcrumb-order .order-box .order-image i{
    font-size:50px;
    color:#4ead4e
}
.breadcrumb-section .breadcrumb-order .order-box .order-image h2{
    margin-top:10px;
    margin-bottom:15px
}
.breadcrumb-section .breadcrumb-order .order-box .order-image p{
    font-size:18px;
    text-transform:capitalize
}
.breadcrumb-section .breadcrumb-order .order-box .order-image.order-fail i{
    color:var(--theme-color)
}
.breadcrumb-section .breadcrumb-order .order-box .order-contain h3{
    font-size:calc(21px + 3*(100vw - 320px)/1600);
    font-weight:700;
    margin-bottom:6px
}
.breadcrumb-section .breadcrumb-order .order-box .order-contain h5{
    margin-bottom:8px;
    line-height:1.4
}

@-webkit-keyframes scaleUpDown{
    0%,100%{
        -webkit-transform:scaleY(1) scaleX(1);
        transform:scaleY(1) scaleX(1)
    }
    50%,90%{
        -webkit-transform:scaleY(1.1);
        transform:scaleY(1.1)
    }
    75%{
        -webkit-transform:scaleY(0.95);
        transform:scaleY(0.95)
    }
    80%{
        -webkit-transform:scaleX(0.95);
        transform:scaleX(0.95)
    }
}
@keyframes scaleUpDown{
    0%,100%{
        -webkit-transform:scaleY(1) scaleX(1);
        transform:scaleY(1) scaleX(1)
    }
    50%,90%{
        -webkit-transform:scaleY(1.1);
        transform:scaleY(1.1)
    }
    75%{
        -webkit-transform:scaleY(0.95);
        transform:scaleY(0.95)
    }
    80%{
        -webkit-transform:scaleX(0.95);
        transform:scaleX(0.95)
    }
}
@-webkit-keyframes shake{
    0%,100%{
        -webkit-transform:skewX(0) scale(1);
        transform:skewX(0) scale(1)
    }
    50%{
        -webkit-transform:skewX(5deg) scale(0.9);
        transform:skewX(5deg) scale(0.9)
    }
}
@keyframes shake{
    0%,100%{
        -webkit-transform:skewX(0) scale(1);
        transform:skewX(0) scale(1)
    }
    50%{
        -webkit-transform:skewX(5deg) scale(0.9);
        transform:skewX(5deg) scale(0.9)
    }
}
@-webkit-keyframes particleUp{
    0%{
        opacity:0
    }
    20%{
        opacity:1
    }
    80%{
        opacity:1
    }
    100%{
        opacity:0;
        top:-100%;
        -webkit-transform:scale(0.5);
        transform:scale(0.5)
    }
}
@keyframes particleUp{
    0%{
        opacity:0
    }
    20%{
        opacity:1
    }
    80%{
        opacity:1
    }
    100%{
        opacity:0;
        top:-100%;
        -webkit-transform:scale(0.5);
        transform:scale(0.5)
    }
}
@-webkit-keyframes shape{
    0%{
        background-position:100% 0
    }
    50%{
        background-position:50% 50%
    }
    100%{
        background-position:0 100%
    }
}
@keyframes shape{
    0%{
        background-position:100% 0
    }
    50%{
        background-position:50% 50%
    }
    100%{
        background-position:0 100%
    }
}
@-webkit-keyframes rounded{
    0%{
        -webkit-transform:rotate(0);
        transform:rotate(0)
    }
    50%{
        -webkit-transform:rotate(180deg);
        transform:rotate(180deg)
    }
    100%{
        -webkit-transform:rotate(360deg);
        transform:rotate(360deg)
    }
}
@keyframes rounded{
    0%{
        -webkit-transform:rotate(0);
        transform:rotate(0)
    }
    50%{
        -webkit-transform:rotate(180deg);
        transform:rotate(180deg)
    }
    100%{
        -webkit-transform:rotate(360deg);
        transform:rotate(360deg)
    }
}
@-webkit-keyframes move{
    0%{
        -webkit-transform:scale(1) rotate(0deg) translate3d(0, 0, 1px);
        transform:scale(1) rotate(0deg) translate3d(0, 0, 1px)
    }
    30%{
        opacity:1
    }
    100%{
        z-index:10;
        -webkit-transform:scale(0) rotate(360deg) translate3d(0, 0, 1px);
        transform:scale(0) rotate(360deg) translate3d(0, 0, 1px)
    }
}
@keyframes move{
    0%{
        -webkit-transform:scale(1) rotate(0deg) translate3d(0, 0, 1px);
        transform:scale(1) rotate(0deg) translate3d(0, 0, 1px)
    }
    30%{
        opacity:1
    }
    100%{
        z-index:10;
        -webkit-transform:scale(0) rotate(360deg) translate3d(0, 0, 1px);
        transform:scale(0) rotate(360deg) translate3d(0, 0, 1px)
    }
}
@-webkit-keyframes mover{
    0%{
        -webkit-transform:translateY(0);
        transform:translateY(0)
    }
    100%{
        -webkit-transform:translateY(-10px);
        transform:translateY(-10px)
    }
}
@keyframes mover{
    0%{
        -webkit-transform:translateY(0);
        transform:translateY(0)
    }
    100%{
        -webkit-transform:translateY(-10px);
        transform:translateY(-10px)
    }
}
@-webkit-keyframes flash{
    0%{
        opacity:.4;
        -webkit-transition:.3s ease-in-out;
        transition:.3s ease-in-out
    }
    100%{
        opacity:1;
        -webkit-transition:.3s ease-in-out;
        transition:.3s ease-in-out
    }
}
@keyframes flash{
    0%{
        opacity:.4;
        -webkit-transition:.3s ease-in-out;
        transition:.3s ease-in-out
    }
    100%{
        opacity:1;
        -webkit-transition:.3s ease-in-out;
        transition:.3s ease-in-out
    }
}
@keyframes shake{
    0%{
        -webkit-transform:translate(3px, 0);
        transform:translate(3px, 0)
    }
    50%{
        -webkit-transform:translate(-3px, 0);
        transform:translate(-3px, 0)
    }
    100%{
        -webkit-transform:translate(0, 0);
        transform:translate(0, 0)
    }
}
@-webkit-keyframes grow{
    0%,100%{
        -webkit-transform:scale(0);
        transform:scale(0);
        opacity:0
    }
    50%{
        -webkit-transform:scale(1);
        transform:scale(1);
        opacity:1
    }
}
@keyframes grow{
    0%,100%{
        -webkit-transform:scale(0);
        transform:scale(0);
        opacity:0
    }
    50%{
        -webkit-transform:scale(1);
        transform:scale(1);
        opacity:1
    }
}
@-webkit-keyframes blink{
    0%{
        opacity:1
    }
    50%{
        opacity:0
    }
    100%{
        opacity:1
    }
}
@keyframes blink{
    0%{
        opacity:1
    }
    50%{
        opacity:0
    }
    100%{
        opacity:1
    }
}
@-webkit-keyframes product-fade{
    0%{
        opacity:0;
        -webkit-transform:translate3d(100%, 0, 0);
        transform:translate3d(100%, 0, 0)
    }
    100%{
        opacity:1;
        -webkit-transform:none;
        transform:none
    }
}
@keyframes product-fade{
    0%{
        opacity:0;
        -webkit-transform:translate3d(100%, 0, 0);
        transform:translate3d(100%, 0, 0)
    }
    100%{
        opacity:1;
        -webkit-transform:none;
        transform:none
    }
}

.breadcrumb-section .breadcrumb-contain{
    padding:calc(26px + 14*(100vw - 320px)/1600) 0;
    text-align:center;
    color:#222;
    font-family:"Public Sans",sans-serif;
    display:-webkit-box;
    display:-ms-flexbox;
    display:flex;
    -webkit-box-align:center;
    -ms-flex-align:center;
    align-items:center
}
@media(max-width: 480px){
    .breadcrumb-section .breadcrumb-contain{
        display:block
    }
}
.breadcrumb-section .breadcrumb-contain h2{
    font-weight:700;
    font-size:calc(16px + 6*(100vw - 320px)/1600);
    margin-bottom:0
}
@media(max-width: 480px){
    .breadcrumb-section .breadcrumb-contain h2{
        text-align:center;
        margin-bottom:8px
    }
}
.breadcrumb-section .breadcrumb-contain .search-box-breadcrumb{
    position:relative;
    width:70%;
    margin:0 auto
}
@media(max-width: 575px){
    .breadcrumb-section .breadcrumb-contain .search-box-breadcrumb{
        width:90%
    }
}
@media(max-width: 360px){
    .breadcrumb-section .breadcrumb-contain .search-box-breadcrumb{
        width:100%
    }
}
.breadcrumb-section .breadcrumb-contain .search-box-breadcrumb input{
    width:100%;
    border:none;
    border-radius:6px;
    font-size:15px
}
.breadcrumb-section .breadcrumb-contain .search-box-breadcrumb i{
    top:50%;
    -webkit-transform:translateY(-50%);
    transform:translateY(-50%);
    position:absolute;
    right:calc(14px + 6*(100vw - 320px)/1600);
    color:#4a5568;
    font-size:calc(15px + 3*(100vw - 320px)/1600)
}
.breadcrumb-section .breadcrumb-contain nav{
    margin-left:auto
}
[dir=rtl] .breadcrumb-section .breadcrumb-contain nav{
    margin-left:unset;
    margin-right:auto
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb{
    display:-webkit-box;
    display:-ms-flexbox;
    display:flex;
    -webkit-box-align:center;
    -ms-flex-align:center;
    align-items:center;
    -webkit-box-pack:center;
    -ms-flex-pack:center;
    justify-content:center
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item{
    font-weight:500
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item i{
    color:#4a5568
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item.active{
    color:#000;
    margin-top:2px
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item+.breadcrumb-item{
    position:relative
}
[dir=rtl] .breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item+.breadcrumb-item{
    padding-left:0;
    padding-right:8px
}
.breadcrumb-section .breadcrumb-contain nav .breadcrumb .breadcrumb-item+.breadcrumb-item::before{
    font-family:"Font Awesome 6 Free";
    font-weight:900;
    content:"";
    color:#4a5568
}
.table th, .table td {
    vertical-align: middle;
}

.table td img.img-thumbnail {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

</style>