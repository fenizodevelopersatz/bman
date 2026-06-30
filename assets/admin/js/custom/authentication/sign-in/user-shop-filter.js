$(function () {

    $('.filter-checkbox').on('change', applyAllFilters);

    const slider = document.getElementById('mn-sliderPrice');
    if (slider) {
        const rangeMin = parseInt(slider.dataset.min);
        const rangeMax = parseInt(slider.dataset.max);
        const step = parseInt(slider.dataset.step);
        const filterInputs = document.querySelectorAll('input.filter__input');

        noUiSlider.create(slider, {
            start: [rangeMin, rangeMax],
            connect: true,
            step: step,
            range: {
                'min': rangeMin,
                'max': rangeMax
            },
            format: {
                to: value => Math.round(value),
                from: value => parseInt(value)
            }
        });

        slider.noUiSlider.on('update', (values, handle) => {
            filterInputs[handle].value = values[handle];
        });

        slider.noUiSlider.on('change', (values) => {
            const min = parseInt(values[0]);
            const max = parseInt(values[1]);
            applyAllFilters();
        });

        filterInputs.forEach((input, indexInput) => {
            input.addEventListener('change', () => {
                const value = parseInt(input.value);
                slider.noUiSlider.setHandle(indexInput, value);

                const min = parseInt(filterInputs[0].value);
                const max = parseInt(filterInputs[1].value);
                applyAllFilters();
            });
        });
    }
});


    $(document).on('click', '.mn-select-cancel', function () {
        const tag = $(this).closest('.mn-select-btn');
        const type = tag.data('type');
        const value = tag.data('value');

        $(`.filter-checkbox[data-type="${type}"][value="${value}"]`).prop('checked', false);
        applyAllFilters();
    });

    $(document).on('click', '.mn-select-clear', function () {
        $('.filter-checkbox').prop('checked', false);
        applyAllFilters();
    });


    function applyAllFilters() {
        const min = $('#price-min').val();
        const max = $('#price-max').val();

        const brands = [];
        $('.brand-filter:checked').each(function () {
            brands.push($(this).val());
        });

        const sizes = [];
        $('.size-filter:checked').each(function () {
            sizes.push($(this).val());
        });

        const categories = [];

        $('.category-filter:checked').each(function () {
            categories.push($(this).val());
        });

        const sortBy = $('#mn-select').val();

        $.ajax({
            url: base_url + "user/shop/filter_price", 
            type: "POST",
            data: {
                min_price: min,
                max_price: max,
                brands: brands,
                sizes: sizes,
                categories:categories,
                sort: sortBy
            },
            beforeSend: function () {
                $("#product-listing").html('<p>Loading...</p>');
            },
            success: function (response) {
                $("#product-listing").html(response);
            },
            error: function () {
                alert("Failed to load products.");
            }
        });

        updateSelectedFiltersUI();

    }

    $('#mn-select').on('change', applyAllFilters);

    function updateSelectedFiltersUI() {
        const selectedFiltersContainer = $('#active-filters');
        selectedFiltersContainer.empty(); 

        $('.filter-checkbox:checked').each(function () {
            const filterType = $(this).data('type');
            const label = $(this).data('label');
            const value = $(this).val();

            const tag = $(`
                <span class="mn-select-btn" data-type="${filterType}" data-value="${value}">
                    ${label}
                    <a class="mn-select-cancel" href="javascript:void(0)">×</a>
                </span>
            `);
            selectedFiltersContainer.append(tag);
        });

        if ($('.filter-checkbox:checked').length > 0) {
            const clearAll = $(`
                <span class="mn-select-btn mn-select-btn-clear">
                    <a class="mn-select-clear" href="javascript:void(0)">Clear All</a>
                </span>
            `);
            selectedFiltersContainer.append(clearAll);
        }
    }





