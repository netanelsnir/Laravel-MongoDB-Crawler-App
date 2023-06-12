<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel MongoDB Crawler App</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        #REFRESH-BTN {
            display: none;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
        }

        .loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">

        <h2>Crawler App</h2>

        <form id="crawlerForm" class="row row-cols-lg-auto g-3 align-items-center mt-2">
            <div class="col-12">
                <label class="visually-hidden" for="url">URL:</label>
                <input type="text" class="form-control" id="url" placeholder="Enter URL">
            </div>

            <div class="col-12">
                <label class="visually-hidden" for="depth">Depth:</label>
                <input type="number" class="form-control" id="depth" placeholder="Enter Depth">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>

        <div class="d-grid gap-2 mt-3">
            <button type="submit" id="REFRESH-BTN" class="btn btn-warning">Refresh</button>
        </div>

        <div id="errorContainer" class="mt-2"></div>
        <table id="resultsTable" class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>URL:</th>
                    <th>Depth:</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="overlay">
            <div class="loader"></div>
        </div>

    </div>



    <script>
        $(document).ready(function() {

            var crawlerForm = $('#crawlerForm');
            var submitButton = crawlerForm.find('button[type="submit"]');
            var overlay = $('.overlay');
            var refreshButton = $('#REFRESH-BTN');

            crawlerForm.submit(function(event) {
                event.preventDefault();
                // Disable the submit button
                submitButton.prop('disabled', true);
                // Clear Table
                clearTable();
                // Clear Errors
                clearErrors();
                // Show overlay and loader
                overlay.show();

                var url = $('#url').val();
                var depth = $('#depth').val();

                $.ajax({
                    //url: '/api/crawler',
                    url: '/api/crawler?url=' + encodeURIComponent(url) + '&depth=' + encodeURIComponent(depth),
                    type: 'GET',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        url: url,
                        depth: depth
                    }),
                    success: function(response) {
                        if (response.length > 0) {
                            refreshButton.show();
                            renderResults(response);
                        }

                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            displayErrors(errors);
                        }

                        refreshButton.hide();
                    },
                    complete: function() {
                        // Hide overlay and loader
                        overlay.hide();
                        // Re-enable the submit button
                        submitButton.prop('disabled', false);
                    }
                });
            });

            refreshButton.click(function() {
                // Clear Table
                clearTable();
                // Clear Errors
                clearErrors();
                // Show overlay and loader
                overlay.show();

                var url = $('#url').val();
                var depth = $('#depth').val();

                $.ajax({
                    url: '/api/refresh',
                    type: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        url: url,
                        depth: depth
                    }),
                    success: function(response) {
                        overlay.hide();
                        if (response.length > 0) {
                            refreshButton.show();
                            renderResults(response);
                        }

                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            displayErrors(errors);
                        }

                        refreshButton.hide();
                        overlay.hide();
                    },
                    complete: function() {
                        // Hide overlay and loader
                        overlay.hide();
                        // Re-enable the submit button
                        //submitButton.prop('disabled', false);
                    }
                });
            });

            function renderResults(pages) {
                var tbody = $('#resultsTable tbody');
                tbody.empty();

                $.each(pages, function(index, page) {
                    var row = $('<tr>');
                    var urlCell = $('<td>').text(page.url);
                    var depthCell = $('<td>').text(page.depth);

                    row.append(urlCell, depthCell);
                    tbody.append(row);
                });
            }

            function clearTable() {
                $('#resultsTable tbody').empty();
            }

            function displayErrors(errors) {
                var errorContainer = $('#errorContainer');
                clearErrors();

                var errorList = $('<ul>').addClass('list-group');

                $.each(errors, function(key, value) {
                    var errorItem = $('<li>').addClass('list-group-item list-group-item-danger').text(value[0]);
                    errorList.append(errorItem);
                });

                errorContainer.append(errorList);
            }

            function clearErrors() {
                $('#errorContainer').empty();
            }
        });
    </script>
</body>

</html>