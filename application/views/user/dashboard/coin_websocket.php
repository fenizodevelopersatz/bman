
<style>
.flash-change {
    transition: background-color 0.3s ease;
    background-color: #ffeeba !important; /* Light yellow flash */
}

.text-flash {
    animation: flashText 0.6s ease;
}

@keyframes flashText {
    0% { color: inherit; }
    50% { color: #ffc107; }  /* Amber */
    100% { color: inherit; }
}
</style>

<!--begin::List widget 23-->
<div class="card card-flush h-xl-100">
    <!--begin::Header-->
    <div class="card-header pt-7">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800"><?php echo lang('dash_trending_balance'); ?></span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-6"><?php echo lang('dash_top_crypto_balance'); ?></span>
                </h3>
        <!--end::Title-->

        <!--begin::Toolbar-->
        <div class="card-toolbar">

        </div>
        <!--end::Toolbar-->
    </div>
    <!--end::Header-->

    <!--begin::Body-->
    <div class="card-body pt-5">
        <!--begin::Items-->
        <div class="">
<div class="card-body pt-5">
<!--begin::Items-->
<div class="">

    <div class="d-flex flex-stack mb-4">
        <div class="d-flex align-items-center me-5">
            <img src="https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png?1696501400" class="me-4" width="30" style="border-radius: 4px" alt="Bitcoin">
            <div class="me-5">
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Bitcoin (BTC)</a>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-btc">$107,708.00</span>
           <div>
                    <span id="change-btc"> -1.78% </span>
            </div>
        </div>
    </div>
      <div class="d-flex flex-stack mb-4">
        <div class="d-flex align-items-center me-5">
            <img src="https://coin-images.coingecko.com/coins/images/279/large/ethereum.png?1696501628" class="me-4" width="30" style="border-radius: 4px" alt="Ethereum">
            <div class="me-5">
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Ethereum (ETH)</a>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-eth">$2,759.08</span>
                                                                            <div>
                  <span id="change-eth"> -1.78% </span>
            </div>
        </div>
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://coin-images.coingecko.com/coins/images/325/large/Tether.png?1696501661" class="me-4" width="30" style="border-radius: 4px" alt="Tether">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Tether (USDT)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-usdt">$1.00</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                   <span id="change-usdt"> -0.01% </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://assets.coingecko.com/coins/images/975/standard/cardano.png?1696502090" class="me-4" width="30" style="border-radius: 4px" alt="XRP">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Cardano (ADA)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-ada">$2.24</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                <span id="change-ada" >
                    -2.29%
                </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png?1696501970" class="me-4" width="30" style="border-radius: 4px" alt="BNB">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6" >BNB (BNB)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-bnb">$665.86</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                    <span id="change-bnb"> -0.01% </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://coin-images.coingecko.com/coins/images/4128/large/solana.png?1718769756" class="me-4" width="30" style="border-radius: 4px" alt="Solana">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Solana (SOL)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-sol">$160.11</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                <span id="change-sol">
                    -3.38%
                </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://coin-images.coingecko.com/coins/images/6319/large/usdc.png?1696506694" class="me-4" width="30" style="border-radius: 4px" alt="USDC">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">USDC (USDC)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-usdc">$1.00</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                <span id="change-usdc">
                    0.00%
                </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://assets.coingecko.com/coins/images/12559/standard/Avalanche_Circle_RedWhite_Trans.png?1696512369" class="me-4" width="30" style="border-radius: 4px" alt="Dogecoin">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Avalanche (AVAX)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-avax">$0.19</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                <span  id="change-avax" >
                    -3.65%
                </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>
                                                        <div class="d-flex flex-stack mb-4">
        <!--begin::Section-->
        <div class="d-flex align-items-center me-5">
            <!--begin::Coin Image-->
            <img src="https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png?1696502193" class="me-4" width="30" style="border-radius: 4px" alt="TRON">
            <!--end::Coin Image-->

            <!--begin::Content-->
            <div class="me-5">
                <!--begin::Title-->
                <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">TRON (TRX)</a>
                <!--end::Title-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Section-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-center">
            <!--begin::Number-->
            <span class="text-gray-800 fw-bold fs-6 me-3" id="price-trx">$0.27</span>
            <!--end::Number-->

            <!--begin::Change-->
                                                                            <div>
                <span id="change-trx" >
                    -5.40%
                </span>
            </div>
            <!--end::Change-->
        </div>
        <!--end::Wrapper-->
    </div>



</div>
<!--end::Items-->
</div>

  <!--begin::Separator-->
                                                        <div class="separator separator-dashed my-3"></div>
                                                        <!--end::Separator-->

        </div>
        <!--end::Items-->
    </div>
    <!--end: Card Body-->
</div>
<!--end::List widget 23-->
<!--end::Col-->



  <script>
    const ws = new WebSocket("wss://akcv1-d77babc9059c.herokuapp.com/");

    ws.onopen = () => {
        console.log("✅ Connected to WebSocket server");
    };

    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);

            Object.keys(data).forEach(symbol => {
                const coin = data[symbol];
                const price = parseFloat(coin.price);
                const change = parseFloat(coin.price_change_24h);

                // Update DOM elements
                const priceElem = document.getElementById(`price-${symbol}`);
                const changeElem = document.getElementById(`change-${symbol}`);

                // if (priceElem) {
                //     priceElem.textContent = "$" + price.toFixed(2);
                // }

                // if (changeElem) {
                //     const icon = change >= 0 ? "ki-arrow-up" : "ki-arrow-down";
                //     const badgeClass = change >= 0 ? "text-success" : "text-danger";

                //     changeElem.innerHTML = `
                //         <span class="badge badge-light ${badgeClass} fs-base">
                //             <i class="ki-outline ${icon} fs-5 ms-n1"></i>
                //             ${change.toFixed(2)}%
                //         </span>
                //     `;
                // }

                if (priceElem) {
                if (priceElem.textContent !== "$" + price.toFixed(2)) {
                    priceElem.textContent = "$" + price.toFixed(2);
                    priceElem.classList.add("flash-change", "text-flash");

                    setTimeout(() => {
                        priceElem.classList.remove("flash-change", "text-flash");
                    }, 600);
                }
            }

            if (changeElem) {
                const icon = change >= 0 ? "ki-arrow-up" : "ki-arrow-down";
                const badgeClass = change >= 0 ? "text-success" : "text-danger";

                const newChangeHTML = `
                    <span class="badge badge-light ${badgeClass} fs-base">
                        <i class="ki-outline ${icon} fs-5 ms-n1"></i>
                        ${change.toFixed(2)}%
                    </span>
                `;

                if (changeElem.innerHTML !== newChangeHTML) {
                    changeElem.innerHTML = newChangeHTML;
                    changeElem.classList.add("flash-change", "text-flash");

                    setTimeout(() => {
                        changeElem.classList.remove("flash-change", "text-flash");
                    }, 600);
                }
            }



            });
        } catch (e) {
            console.error("❌ Error parsing WebSocket data:", e);
        }
    };

    ws.onerror = (error) => {
        console.error("❌ WebSocket error:", error);
    };

    ws.onclose = () => {
        console.warn("⚠️ WebSocket connection closed");
    };
</script>

