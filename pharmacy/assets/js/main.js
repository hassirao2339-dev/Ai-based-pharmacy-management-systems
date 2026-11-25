/**
 * main.js
 *
 * Small scripts used on every page.
 * Loaded at the bottom of the page, after jQuery and Bootstrap.
 */

// $(function () { ... }) runs the code once the page has finished loading.
$(function () {

    // 1. Send the CSRF token with every jQuery AJAX request.
    //    The token is printed in a <meta> tag by main_header.php,
    //    and PHP checks it in verifyCsrf().
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-Token': csrfToken }
    });

    // 2. Add a soft shadow under the navigation bar once the page scrolls.
    const $navbar = $('#mainNavbar');

    function updateNavbarShadow() {
        $navbar.toggleClass('is-scrolled', $(window).scrollTop() > 10);
    }

    $(window).on('scroll', updateNavbarShadow);
    updateNavbarShadow();

    // 3. Close green success messages after 5 seconds.
    //    Warnings and errors stay until the user closes them, so they are not missed.
    setTimeout(function () {
        $('.alert-success.alert-dismissible').each(function () {
            bootstrap.Alert.getOrCreateInstance(this).close();
        });
    }, 5000);

    // 4. Show or hide a password when the eye button next to it is clicked.
    //    The button's data-password-toggle="..." holds the id of its password field.
    $('[data-password-toggle]').on('click', function () {
        const $button = $(this);
        const $input = $('#' + $button.data('password-toggle'));
        const isHidden = ($input.attr('type') === 'password');

        if (isHidden) {
            $input.attr('type', 'text');
            $button.attr('aria-label', 'Hide password');
        } else {
            $input.attr('type', 'password');
            $button.attr('aria-label', 'Show password');
        }

        $button.find('i').toggleClass('bi-eye bi-eye-slash');
    });

    // 5. Live catalogue search (SRS 4.4).
    //    Only runs on the catalogue page, where #catalogueResults exists.
    //    Without JavaScript the search button and category links still work.
    const $results = $('#catalogueResults');

    if ($results.length > 0) {
        const $searchInput = $('#catalogueSearch');
        const baseUrl = $('#catalogueForm').attr('action');
        const ajaxUrl = baseUrl.replace('catalogue.php', 'ajax/catalogue_search.php');

        let typingTimer = null;
        let currentRequest = null;

        function runSearch() {
            const searchText = $searchInput.val();
            const category = $results.data('category') || '';

            // Stop an older request, so a slow answer cannot replace a newer one.
            if (currentRequest !== null) {
                currentRequest.abort();
            }

            $results.addClass('is-loading');

            currentRequest = $.get(ajaxUrl, { search: searchText, category: category })
                .done(function (html) {
                    $results.html(html);

                    // Keep the address bar in step, so refreshing or sharing
                    // the page shows the same search.
                    const parameters = [];

                    if (searchText !== '') {
                        parameters.push('search=' + encodeURIComponent(searchText));
                    }

                    if (category !== '') {
                        parameters.push('category=' + encodeURIComponent(category));
                    }

                    const newUrl = baseUrl + (parameters.length > 0 ? '?' + parameters.join('&') : '');
                    history.replaceState(null, '', newUrl);
                })
                .fail(function (jqXHR, status) {
                    // "abort" is our own cancellation, not a real problem.
                    if (status !== 'abort') {
                        $results.html(
                            '<div class="alert alert-warning">Search is not available right now. ' +
                            'Please press the Search button instead.</div>'
                        );
                    }
                })
                .always(function () {
                    $results.removeClass('is-loading');
                    currentRequest = null;
                });
        }

        // Wait 350 ms after the last key press before searching (debouncing),
        // so one request is sent instead of one per letter.
        $searchInput.on('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(runSearch, 350);
        });

        // Pressing Enter searches at once instead of reloading the page.
        $('#catalogueForm').on('submit', function (event) {
            event.preventDefault();
            clearTimeout(typingTimer);
            runSearch();
        });
    }

});